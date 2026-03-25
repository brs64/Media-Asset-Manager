<?php

namespace App\Jobs;

use App\Models\Media;
use App\Services\FileExplorerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * @brief Job asynchrone de synchronisation des médias depuis un disque.
 *
 * Ce job permet de maintenir la cohérence entre les fichiers physiques présents
 * sur les différents stockages (NAS, FTP, local) et les enregistrements en base de données.
 *
 * Fonctionnalités principales :
 * - Scan récursif d'un disque pour détecter tous les fichiers vidéo
 * - Création automatique de médias pour les nouveaux fichiers détectés
 * - Mise à jour des chemins pour les médias existants
 * - Nettoyage des chemins devenus invalides (fichiers supprimés)
 * - Suppression optionnelle des médias orphelins (sans aucun fichier)
 *
 * Disques supportés :
 * - ftp_arch : NAS d'archivage (URI_NAS_ARCH)
 * - ftp_pad : NAS de production (URI_NAS_PAD)
 * - external_local : Stockage local (chemin_local)
 *
 * Sécurité : Par défaut, aucun fichier local n'est supprimé (deleteLocalFiles = false)
 */
class SyncMediaFromDiskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $disk;
    public string $path;

    public $timeout = 0;
    public $tries = 1;

    protected string $uriField;

    /** @var array<string, bool> */
    protected array $existingPaths = [];

    private FileExplorerService $fileExplorerService;

    /**
     * @brief Flag de sécurité pour autoriser la suppression de fichiers locaux.
     *
     * false par défaut → AUCUNE suppression de fichiers locaux
     * true → Autorise la suppression des fichiers locaux orphelins
     */
    public bool $deleteLocalFiles = false;

    /**
     * @brief Initialise le job de synchronisation pour un disque donné.
     *
     * Détermine automatiquement le champ de base de données correspondant
     * au disque spécifié (URI_NAS_ARCH, URI_NAS_PAD ou chemin_local).
     *
     * @param string $disk Nom du disque Laravel (ftp_arch, ftp_pad, external_local)
     * @param string $path Chemin de départ du scan (défaut : racine '/')
     *
     * @throws \InvalidArgumentException Si le disque n'est pas supporté
     */
    public function __construct(string $disk, string $path = '/')
    {
        $this->disk = $disk;
        $this->path = $path;

        $this->fileExplorerService = new FileExplorerService();

        $this->uriField = match ($disk) {
            'ftp_arch'       => 'URI_NAS_ARCH',
            'ftp_pad'        => 'URI_NAS_PAD',
            'external_local' => 'chemin_local',
            default => throw new \InvalidArgumentException("Disk non supporté: {$disk}"),
        };
    }

    /**
     * @brief Exécute la synchronisation complète du disque.
     *
     * Processus en deux phases :
     * 1. Scan du disque : parcourt récursivement tous les fichiers et met à jour la base
     * 2. Nettoyage : supprime les références aux fichiers qui n'existent plus
     *
     * Chaque fichier vidéo trouvé est traité par handleItem() pour création ou mise à jour.
     * Les fichiers disparus sont détectés et nettoyés par cleanupDatabase().
     *
     * @return void
     */
    public function handle(): void
    {
        Log::info('Sync Media démarrée', ['disk' => $this->disk]);

        /**
         * Scan disque → index mémoire + upsert media
         */
        $this->fileExplorerService->scanDiskRecursive(
            $this->disk,
            $this->path,
            function (array $item) {
                $this->handleItem($item);
            }
        );

        /**
         * Nettoyage BDD (orphans)
         */
        $this->cleanupDatabase();

        Log::info('Sync Media terminée', ['disk' => $this->disk]);
    }

    /**
     * @brief Traite un fichier vidéo trouvé lors du scan.
     *
     * Fonctionnalités :
     * - Indexe le fichier dans la liste des chemins existants
     * - Recherche un média existant avec un titre similaire (insensible à la casse)
     * - Crée un nouveau média si aucun n'existe
     * - Met à jour le champ URI approprié (ARCH, PAD ou local)
     *
     * Le matching des médias se fait sur le nom de fichier normalisé (sans extension, en minuscules).
     *
     * @param array $item Tableau associatif contenant les informations du fichier :
     *                    - 'type' : type de fichier (video, directory, etc.)
     *                    - 'name' : nom du fichier avec extension
     *                    - 'path' : chemin complet du fichier
     * @return void
     */
    protected function handleItem(array $item): void
    {
        if (($item['type'] ?? '') !== 'video') {
            return;
        }

        // Index des fichiers existants
        $this->existingPaths[$item['path']] = true;

        // Normalisation du titre
        $title = pathinfo($item['name'], PATHINFO_FILENAME);
        $normalizedTitle = mb_strtolower(trim($title));

        $media = Media::whereRaw(
            'LOWER(mtd_tech_titre) = ?',
            [$normalizedTitle]
        )->first();

        if (!$media) {
            $media = new Media([
                'mtd_tech_titre' => $normalizedTitle,
                'type' => 'video',
                'promotion' => null,
                'theme' => null,
                'description' => null,
                'professeur_id' => null,
            ]);
        }

        $media->{$this->uriField} = $item['path'];
        $media->save();
    }

    /**
     * @brief Nettoie la base de données en supprimant les références obsolètes.
     *
     * Cette méthode parcourt tous les médias ayant un chemin sur le disque en cours
     * de synchronisation et vérifie leur existence réelle.
     *
     * Actions effectuées :
     * - Réinitialise le champ URI si le fichier n'existe plus
     * - Supprime optionnellement le fichier local si tous les NAS sont vides (si deleteLocalFiles = true)
     * - Supprime complètement le média si aucun fichier ne reste disponible
     *
     * Traitement par lots de 500 médias pour optimiser les performances et la mémoire.
     *
     * @return void
     */
    protected function cleanupDatabase(): void
    {
        Media::whereNotNull($this->uriField)
            ->chunkById(500, function ($medias) {
                foreach ($medias as $media) {
                    $path = $media->{$this->uriField};

                    if (!isset($this->existingPaths[$path])) {
                        Log::warning('Chemin NAS inexistant, nettoyage', [
                            'media_id' => $media->id,
                            'disk' => $this->disk,
                            'path' => $path,
                        ]);

                        // Supprime uniquement le champ
                        $media->{$this->uriField} = null;
                        $media->save();

                        /**
                         * Suppression aussi le fichier local s’il n’existe plus
                         * aucun chemin NAS
                         */
                        if (
                            $this->deleteLocalFiles &&
                            !$media->URI_NAS_ARCH &&
                            !$media->URI_NAS_PAD &&
                            $media->chemin_local &&
                            Storage::disk('external_local')->exists($media->chemin_local)
                        ) {
                            Log::warning('Suppression du fichier local', [
                                'media_id' => $media->id,
                                'path' => $media->chemin_local,
                            ]);

                            Storage::disk('external_local')->delete($media->chemin_local);
                            $media->chemin_local = null;
                            $media->save();
                        }

                        // Si plus aucun chemin → suppression du media
                        if (
                            !$media->URI_NAS_ARCH &&
                            !$media->URI_NAS_PAD &&
                            !$media->chemin_local
                        ) {
                            Log::warning('Media supprimé (plus aucun fichier)', [
                                'media_id' => $media->id,
                            ]);

                            $media->delete();
                        }
                    }
                }
            });
    }
}
