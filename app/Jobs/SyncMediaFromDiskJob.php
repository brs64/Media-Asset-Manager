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

    /**
     * Sécurité :
     * false par défaut → AUCUNE suppression de fichiers locaux
     */
    public bool $deleteLocalFiles = false;

    public function __construct(string $disk, string $path = '/')
    {
        $this->disk = $disk;
        $this->path = $path;

        $this->uriField = match ($disk) {
            'ftp_arch'       => 'URI_NAS_ARCH',
            'ftp_pad'        => 'URI_NAS_PAD',
            'external_local' => 'chemin_local',
            default => throw new \InvalidArgumentException("Disk non supporté: {$disk}"),
        };
    }

    public function handle(): void
    {
        Log::info('Sync Media démarrée', ['disk' => $this->disk]);

        /**
         * Scan disque → index mémoire + upsert media
         */
        FileExplorerService::scanDiskRecursive(
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
     * Création / mise à jour + indexation des chemins existants
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
     * Supprime les chemins inexistants et les médias vides
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
