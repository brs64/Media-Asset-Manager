<?php

namespace App\Services;


use App\Models\Media;
use App\Models\Projet;
use App\Models\Professeur;
use App\Models\Eleve;
use App\Models\Role;
use App\Models\Participation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * @brief Service central de gestion des médias.
 *
 * Ce service contient toute la logique métier pour les opérations sur les médias.
 * Il sert d'interface unifiée entre les controllers et les modèles pour toutes
 * les opérations CRUD et de recherche.
 *
 * Responsabilités principales :
 * - Récupération et enrichissement des informations média
 * - Gestion des métadonnées (techniques, éditoriales, personnalisées)
 * - Recherche multicritères avec filtres
 * - Gestion des participations (élèves, rôles)
 * - Extraction de métadonnées vidéo via FFprobe
 * - Synchronisation fichiers/base de données
 * - Opérations de suppression avec nettoyage des fichiers
 *
 * Sources de métadonnées :
 * - Base de données : informations éditoriales
 * - FFprobe : métadonnées techniques (durée, résolution, codec, bitrate)
 * - Filesystem : existence et taille des fichiers
 */
class MediaService
{
    /**
     * @brief Récupère toutes les informations complètes d'un média.
     *
     * Cette méthode centralise la récupération de toutes les données associées
     * à un média pour affichage dans l'interface de détail.
     *
     * Informations récupérées :
     * - Données de base (titre, description, promotion, type, thème)
     * - Relations (professeur référent, projets, participations élèves)
     * - Métadonnées techniques (via FFprobe)
     * - Chemins de streaming et miniatures
     * - Métadonnées personnalisées (properties JSON)
     *
     * @param int $idMedia Identifiant du média
     * @return array|null Tableau associatif complet ou null si le média n'existe pas.
     *                    Contient les clés : media, idMedia, nomFichier, titreVideo, description,
     *                    promotion, type, theme, sourceVideo, chemins, URIS, mtdTech, mtdEdito,
     *                    mtdCustom, mtdRoles
     */
    public function getMediaInfo(int $idMedia): ?array
    {
        $media = Media::with([
            'projets',
            'professeur',
            'participations.eleve',
            'participations.role'
        ])->find($idMedia);

        if (!$media) {
            return null;
        }

        // Extract technical metadata via FTP
        $technicalMetadata = $this->getTechnicalMetadata($media);

        return [
            'media' => $media,
            'idMedia' => $media->id,
            'nomFichier' => $media->mtd_tech_titre,
            'titreVideo' => $this->sanitizeForDisplay($this->extractVideoTitle($media->mtd_tech_titre)),
            'description' => $media->description ?? '',
            'promotion' => $this->sanitizeForDisplay($media->promotion),
            'type' => $this->sanitizeForDisplay($media->type),
            'theme' => $this->sanitizeForDisplay($media->theme),

            // Source vidéo (priorité: local > arch > pad)
            'sourceVideo' => $media->chemin_local ? 'local' : ($media->URI_NAS_ARCH ? 'arch' : ($media->URI_NAS_PAD ? 'pad' : null)),

            // Chemins
            'cheminVideoComplet' => route('stream.video', $media->id),
            'cheminMiniatureComplet' => route('thumbnails.show', $media->id),
            'cheminCompletNAS_ARCH' => $media->URI_NAS_ARCH,
            'cheminCompletNAS_PAD' => $media->URI_NAS_PAD,

            // URIs
            'URIS' => [
                'URI_NAS_PAD' => $media->URI_NAS_PAD ?? 'N/A',
                'chemin_local' => $media->chemin_local ?? 'N/A',
                'URI_NAS_ARCH' => $media->URI_NAS_ARCH ?? 'N/A',
            ],

            // Technical metadata (from FTP)
            'mtdTech' => $technicalMetadata ? [
                'mtd_tech_duree' => $technicalMetadata['duree_format'] ?? 'N/A',
                'mtd_tech_fps' => $technicalMetadata['fps'] ?? 'N/A',
                'mtd_tech_resolution' => $technicalMetadata['resolution'] ?? 'N/A',
                'mtd_tech_format' => $technicalMetadata['codec_video'] ?? 'N/A',
                'mtd_tech_taille' => $technicalMetadata['taille_format'] ?? 'N/A',
                'mtd_tech_bitrate' => isset($technicalMetadata['bitrate']) ? round($technicalMetadata['bitrate'] / 1000) . ' kbps' : 'N/A',
            ] : [
                'mtd_tech_duree' => 'N/A',
                'mtd_tech_fps' => 'N/A',
                'mtd_tech_resolution' => 'N/A',
                'mtd_tech_format' => 'N/A',
                'mtd_tech_taille' => 'N/A',
                'mtd_tech_bitrate' => 'N/A',
            ],

            // Métadonnées éditoriales
            'mtdEdito' => [
                'projet' => $media->projets->pluck('libelle')->implode(', ') ?: 'N/A',
                'professeur' => $media->professeur ? ($media->professeur->prenom . ' ' . $media->professeur->nom) : 'N/A',
           // NOUVEAU : Récupération des élèves avec leurs rôles
                'eleves' => $media->participations->map(function($p) {
                    return $p->eleve->prenom . ' ' . $p->eleve->nom . ' (' . ($p->role->libelle ?? 'Rôle non défini') . ')';
                })->implode(', ') ?: 'N/A',
            ],

            // Métadonnées personnalisées
            'mtdCustom' => collect($media->properties ?? [])
                ->map(fn ($value, $key) => [
                    'label' => $this->sanitizeForDisplay($key),
                    'value' => $this->sanitizeForDisplay(
                        is_array($value) ? json_encode($value) : (string) $value
                    ),
                ])
                ->values()
                ->toArray(),


            // Roles
            'mtdRoles' => $this->formatParticipations($media->participations),
        ];
    }

    /**
     * @brief Met à jour les métadonnées d'un média.
     *
     * Cette méthode permet de modifier toutes les informations éditoriales
     * d'un média (professeur référent, promotion, projet, description, participations).
     *
     * Fonctionnement :
     * - Utilise une transaction DB pour garantir la cohérence
     * - Crée automatiquement les professeurs et projets s'ils n'existent pas
     * - Reconstruit complètement les participations (suppression + recréation)
     *
     * @param int $idMedia Identifiant du média à modifier
     * @param string $profReferent Nom complet du professeur référent ("Nom Prénom")
     * @param string|null $promotion Nom de la promotion/classe
     * @param string|null $projet Libellé du projet
     * @param string|null $description Description du média
     * @param array $roles Tableau associatif [nom_role => "Nom1, Nom2, Nom3"]
     * @return bool true si la mise à jour a réussi, false en cas d'erreur
     */
    public function updateMetadata(
        int $idMedia,
        string $profReferent,
        ?string $promotion,
        ?string $projet,
        ?string $description,
        array $roles
    ): bool {
        try {
            DB::beginTransaction();

            $media = Media::findOrFail($idMedia);

            // Update reference professor
            if ($profReferent) {
                $professeur = $this->findOrCreateProfessor($profReferent);
                $media->professeur_id = $professeur->id;
            }

            // Mise à jour des autres champs
            $media->promotion = $promotion;
            $media->description = $description;
            $media->save();

            // Update project (many-to-many via pivot)
            if ($projet) {
                $projetModel = $this->findOrCreateProject($projet);
                $media->projets()->sync([$projetModel->id]);
            }

            // Update participations (roles)
            $this->updateParticipations($idMedia, $roles);

            DB::commit();
            Log::info("Metadata updated for media #$idMedia");

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating metadata: " . $e->getMessage());
            return false;
        }
    }

    /**
     * @brief Assigne des participants avec leurs rôles à un média.
     *
     * Crée automatiquement les élèves et rôles s'ils n'existent pas encore en base.
     * Utilise updateOrCreate pour éviter les doublons de participations.
     *
     * @param int $idMedia Identifiant du média
     * @param string $nomRole Nom du rôle (ex: "Réalisateur", "Acteur", "Cadreur")
     * @param array $personnes Liste de noms complets ("Nom Prénom")
     * @return void
     */
    public function assignRoles(int $idMedia, string $nomRole, array $personnes): void
    {
        $role = Role::firstOrCreate(['libelle' => $nomRole]);

        foreach ($personnes as $personne) {
            $eleve = Eleve::firstOrCreate([
                'nom' => $this->extractLastName($personne),
                'prenom' => $this->extractFirstName($personne),
            ]);

            Participation::updateOrCreate([
                'media_id' => $idMedia,
                'eleve_id' => $eleve->id,
                'role_id' => $role->id,
            ]);
        }
    }

    /**
     * @brief Supprime un média et ses participations associées.
     *
     * Effectue une suppression complète en transaction pour garantir la cohérence.
     * Ne supprime PAS les fichiers physiques (voir clearLocalFiles pour cela).
     *
     * @param int $idMedia Identifiant du média à supprimer
     * @return bool true si la suppression a réussi, false en cas d'erreur
     */
    public function deleteMedia(int $idMedia): bool
    {
        try {
            DB::beginTransaction();

            $media = Media::findOrFail($idMedia);

            // Delete participations
            Participation::where('media_id', $idMedia)->delete();

            // Delete media
            $media->delete();

            DB::commit();
            Log::info("Media #$idMedia deleted successfully");

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error deleting media: " . $e->getMessage());
            return false;
        }
    }

    /**
     * @brief Supprime les fichiers locaux associés à un média.
     *
     * Supprime la vidéo transcodée et sa miniature du stockage local.
     * Le champ chemin_local reste inchangé (doit être mis à null manuellement si nécessaire).
     *
     * Chemins cibles :
     * - Vidéo : /mnt/archivage/H264/{chemin_local}
     * - Miniature : /mnt/archivage/Thumbnails/{chemin_local sans .mp4}.jpg
     *
     * @param int $idMedia Identifiant du média
     * @return bool true si la suppression a réussi ou si aucun fichier local n'existe
     */
    public function clearLocalFiles(int $idMedia): bool
    {
        try {
            $media = Media::findOrFail($idMedia);

            if ($media->chemin_local) {
                // Construction des chemins à cibler
                $fullVideoPath = "/mnt/archivage/H264/" . $media->chemin_local;
                $fullThumbnailPath = rtrim("/mnt/archivage/Thumbnails/" . $media->chemin_local, ".mp4") . ".jpg";

                // Suppression du fichier vidéo local
                Storage::delete($fullVideoPath);
                Log::info($fullVideoPath . ' deleted');

                // Suppression du fichier miniature local
                Storage::delete($fullThumbnailPath);
                Log::info($fullThumbnailPath . ' deleted');
            }
            else {
                Log::info("Media #$idMedia has no local files");
            }
            return true;
        }
        catch (\Exception $e) {
            Log::error("Error cleaning local files of media " . $idMedia . " : " . $e->getMessage());
            return false;
        }
    }

    /**
     * @brief Récupère les médias récemment modifiés.
     *
     * Retourne les médias triés par date de dernière modification décroissante.
     * Inclut les relations professeur et projets pour affichage.
     *
     * @param int $limit Nombre maximum de médias à retourner (défaut : 20)
     * @return array Tableau de médias sous forme de tableaux associatifs
     */
    public function getRecentMedia(int $limit = 20): array
    {
        return Media::with(['projets', 'professeur'])
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * @brief Recherche de médias avec filtres multiples.
     *
     * Permet une recherche multicritères sur les médias avec pagination.
     *
     * Critères de recherche supportés :
     * - keyword : mot-clé recherché dans titre, description, thème, promotion, nom du professeur, nom du projet
     * - projet : ID d'un projet spécifique
     * - promotion : nom exact de la promotion
     *
     * Le filtre keyword effectue une recherche large (OR) sur tous les champs textuels.
     * Les autres filtres sont cumulatifs (AND).
     *
     * Seuls les médias ayant un chemin_local (transcodés et disponibles) sont retournés.
     *
     * @param array $filtres Tableau associatif des filtres :
     *                       - 'keyword' : string (optionnel)
     *                       - 'projet' : int (optionnel)
     *                       - 'promotion' : string (optionnel)
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator Résultats paginés (20 par page)
     */
public function searchMedia(array $filtres)
{
    $query = Media::query();

    if (!empty($filtres['keyword'])) {
        $kw = $filtres['keyword'];

        $query->where(function($q) use ($kw) {
            // Recherche dans les colonnes directes
            $q->where('mtd_tech_titre', 'like', "%{$kw}%")
              ->orWhere('description', 'like', "%{$kw}%")
              ->orWhere('theme', 'like', "%{$kw}%")
              ->orWhere('promotion', 'like', "%{$kw}%")
              ->andWhere('chemin_local', 'exists', true);

            // Recherche dans le nom du PROFESSEUR
            $q->orWhereHas('professeur', function($sq) use ($kw) {
                $sq->where('nom', 'like', "%{$kw}%")
                   ->orWhere('prenom', 'like', "%{$kw}%");
            });

            // Recherche dans le nom du PROJET (C'est ça qui manque !)
            $q->orWhereHas('projets', function($sq) use ($kw) {
                $sq->where('libelle', 'like', "%{$kw}%");
            });
        });
    }

    // Garde quand même les filtres spécifiques "au cas où" (pour les tests)
    if (!empty($filtres['projet'])) {
        $query->whereHas('projets', function ($q) use ($filtres) {
            $q->where('projets.id', $filtres['projet'])
              ->andWhere('chemin_local', 'exists', true);
        });
    }
    
    if (!empty($filtres['promotion'])) {
        $query->where('promotion', $filtres['promotion']);
    }

    return $query->orderBy('created_at', 'desc')->paginate(20);
}

    // --- Private utility methods ---

    /**
     * @brief Recherche ou crée un professeur à partir de son nom complet.
     *
     * Parsing du nom : le dernier mot est considéré comme le prénom, le reste comme le nom.
     * Si le professeur n'existe pas, crée également un compte utilisateur associé.
     *
     * @param string $fullName Nom complet au format "Nom Prénom"
     * @return Professeur Instance du professeur (existant ou nouvellement créé)
     */
    private function findOrCreateProfessor(string $fullName): Professeur
    {
        $parts = explode(' ', trim($fullName));
        $firstName = array_pop($parts);
        $lastName = implode(' ', $parts) ?: $firstName;

        // Search for existing professor
        $professor = Professeur::where('nom', $lastName)->where('prenom', $firstName)->first();

        if ($professor) {
            return $professor;
        }

        // Create associated User
        $email = strtolower(substr($firstName, 0, 1) . '.' . $lastName) . '@mediamanager.fr';
        $user = \App\Models\User::firstOrCreate(
            ['email' => $email],
            ['name' => $firstName . ' ' . $lastName, 'password' => bcrypt('password')]
        );

        // Create Professor
        return Professeur::create([
            'user_id' => $user->id,
            'nom' => $lastName,
            'prenom' => $firstName,
        ]);
    }

    /**
     * @brief Recherche ou crée un projet.
     *
     * @param string $libelle Libellé du projet
     * @return Projet Instance du projet (existant ou nouvellement créé)
     */
    private function findOrCreateProject(string $libelle): Projet
    {
        return Projet::firstOrCreate(['libelle' => $libelle]);
    }

    /**
     * @brief Met à jour toutes les participations d'un média.
     *
     * Supprime toutes les anciennes participations et crée les nouvelles.
     * Parse les listes CSV de noms pour chaque rôle.
     *
     * @param int $idMedia Identifiant du média
     * @param array $roles Tableau associatif [nom_role => "Nom1, Nom2, Nom3"]
     * @return void
     */
    private function updateParticipations(int $idMedia, array $roles): void
    {
        // Delete old participations
        Participation::where('media_id', $idMedia)->delete();

        // Add new participations
        foreach ($roles as $roleName => $peopleListCsv) {
            if (empty(trim($peopleListCsv))) {
                continue;
            }

            $people = array_filter(array_map('trim', explode(',', $peopleListCsv)));
            $this->assignRoles($idMedia, $roleName, $people);
        }
    }

    /**
     * @brief Formate les participations pour affichage.
     *
     * Regroupe les participants par rôle et les concatène en chaînes CSV.
     *
     * @param \Illuminate\Database\Eloquent\Collection $participations Collection de participations
     * @return array Tableau associatif [nom_role => "Nom1, Nom2, Nom3"]
     */
    private function formatParticipations($participations): array
    {
        $result = [];

        foreach ($participations as $participation) {
            $role = $participation->role->libelle;
            $name = $participation->eleve->nom . ' ' . $participation->eleve->prenom;

            if (!isset($result[$role])) {
                $result[$role] = [];
            }

            $result[$role][] = $name;
        }

        // Convert to comma-separated strings
        foreach ($result as $role => &$names) {
            $names = implode(', ', $names);
        }

        return $result;
    }

    /**
     * @brief Extrait le titre d'une vidéo depuis son nom de fichier.
     *
     * Format attendu : ANNEE_PROJET_TITRE.ext
     * Si le format ne correspond pas, retourne le nom de fichier sans extension.
     *
     * @param string $filename Nom du fichier vidéo
     * @return string Titre extrait
     */
    private function extractVideoTitle(string $filename): string
    {
        if (preg_match("/^[^_]*_[^_]*_(.*)(?=\.)/", $filename, $matches)) {
            return $matches[1] ?? pathinfo($filename, PATHINFO_FILENAME);
        }

        return pathinfo($filename, PATHINFO_FILENAME);
    }

    /**
     * @brief Extrait le nom de famille d'une personne.
     *
     * Considère le dernier mot comme le prénom et tout le reste comme le nom.
     *
     * @param string $fullName Nom complet ("Nom Prénom")
     * @return string Nom de famille
     */
    private function extractLastName(string $fullName): string
    {
        $parts = explode(' ', trim($fullName));
        if (count($parts) > 1) {
            array_pop($parts);
            return implode(' ', $parts);
        }
        return $fullName;
    }

    /**
     * @brief Extrait le prénom d'une personne.
     *
     * Considère le dernier mot comme le prénom.
     *
     * @param string $fullName Nom complet ("Nom Prénom")
     * @return string Prénom
     */
    private function extractFirstName(string $fullName): string
    {
        $parts = explode(' ', trim($fullName));
        return array_pop($parts);
    }

    /**
     * @brief Extrait les métadonnées techniques d'une vidéo via FFprobe.
     *
     * Interroge FFprobe pour récupérer les informations techniques sans télécharger
     * la vidéo complète (fonctionne sur FTP).
     *
     * Métadonnées extraites :
     * - Durée (secondes et format HH:MM:SS)
     * - Résolution (largeur x hauteur)
     * - FPS (images par seconde)
     * - Codec vidéo et audio
     * - Bitrate
     * - Taille du fichier
     *
     * Ordre de priorité : chemin_local > URI_NAS_ARCH > URI_NAS_PAD
     *
     * @param Media $media Modèle du média
     * @return array|null Tableau de métadonnées ou null en cas d'échec
     */
    public function getTechnicalMetadata(Media $media): ?array
    {
        Log::info("getTechnicalMetadata called for media #{$media->id}");

        // Priorité: chemin_local > ARCH > PAD
        // Si chemin_local existe, extraire les métadonnées du fichier local
        if ($media->chemin_local) {
            $localPath = storage_path('app/' . ltrim($media->chemin_local, '/'));
            if (file_exists($localPath)) {
                Log::info("getTechnicalMetadata: Using local file = {$localPath}");
                return $this->extractMetadataFromFile($localPath);
            }
        }

        // Sinon fallback FTP
        $remoteVideoPath = $media->URI_NAS_ARCH ?? $media->URI_NAS_PAD;

        if (!$remoteVideoPath) {
            Log::warning("getTechnicalMetadata: No video path for media #{$media->id}");
            return null;
        }

        Log::info("getTechnicalMetadata: Remote path = {$remoteVideoPath}");

        // Determine FTP disk
        $ftpDisk = null;
        if ($media->URI_NAS_ARCH) {
            $ftpDisk = 'ftp_arch';
        } elseif ($media->URI_NAS_PAD) {
            $ftpDisk = 'ftp_pad';
        }

        if (!$ftpDisk) {
            Log::warning("getTechnicalMetadata: No FTP disk determined for media #{$media->id}");
            return null;
        }

        Log::info("getTechnicalMetadata: Using FTP disk = {$ftpDisk}");

        // Build FTP URL
        $config = config("filesystems.disks.{$ftpDisk}");

        $ftpUrl = sprintf(
            'ftp://%s:%s@%s/%s',
            $config['username'],
            $config['password'],
            $config['host'],
            ltrim($remoteVideoPath, '/')
        );

        Log::info("getTechnicalMetadata: FTP URL constructed (host: {$config['host']})");

        // Use ffprobe to extract metadata
        $command = sprintf(
            'ffprobe -v quiet -print_format json -show_format -show_streams %s 2>&1',
            escapeshellarg($ftpUrl)
        );

        Log::info("getTechnicalMetadata: Executing ffprobe command");

        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            Log::error("getTechnicalMetadata: ffprobe failed with return code {$returnVar}, output: " . implode("\n", $output));
            return null;
        }

        Log::info("getTechnicalMetadata: ffprobe succeeded");

        $json = implode("\n", $output);
        $data = json_decode($json, true);

        if (!$data || !isset($data['streams']) || !isset($data['format'])) {
            Log::error("getTechnicalMetadata: Invalid JSON data or missing streams/format. JSON: " . substr($json, 0, 200));
            return null;
        }

        Log::info("getTechnicalMetadata: Successfully parsed ffprobe output");

        // Extract relevant information
        $videoStream = collect($data['streams'])->firstWhere('codec_type', 'video');
        $audioStream = collect($data['streams'])->firstWhere('codec_type', 'audio');

        $metadata = [
            'duree' => isset($data['format']['duration']) ? (float)$data['format']['duration'] : null,
            'duree_format' => null,
            'taille_octets' => isset($data['format']['size']) ? (int)$data['format']['size'] : null,
            'taille_format' => null,
            'bitrate' => isset($data['format']['bit_rate']) ? (int)$data['format']['bit_rate'] : null,
        ];

        if ($videoStream) {
            $metadata['largeur'] = $videoStream['width'] ?? null;
            $metadata['hauteur'] = $videoStream['height'] ?? null;
            $metadata['resolution'] = null;
            $metadata['codec_video'] = $videoStream['codec_name'] ?? null;
            $metadata['fps'] = null;

            if (isset($videoStream['r_frame_rate'])) {
                $parts = explode('/', $videoStream['r_frame_rate']);
                if (count($parts) === 2 && $parts[1] > 0) {
                    $metadata['fps'] = round($parts[0] / $parts[1], 2);
                }
            }

            if ($metadata['largeur'] && $metadata['hauteur']) {
                $metadata['resolution'] = $metadata['largeur'] . 'x' . $metadata['hauteur'];
            }
        }

        if ($audioStream) {
            $metadata['codec_audio'] = $audioStream['codec_name'] ?? null;
            $metadata['sample_rate'] = $audioStream['sample_rate'] ?? null;
            $metadata['channels'] = $audioStream['channels'] ?? null;
        }

        // Format duration as HH:MM:SS
        if ($metadata['duree']) {
            $seconds = (int)$metadata['duree'];
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            $secs = $seconds % 60;
            $metadata['duree_format'] = sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
        }

        // Format file size
        if ($metadata['taille_octets']) {
            $metadata['taille_format'] = $this->formatFileSize($metadata['taille_octets']);
        }

        return $metadata;
    }

    /**
     * @brief Formate une taille de fichier en octets vers une forme lisible.
     *
     * Conversion automatique vers l'unité appropriée (B, KB, MB, GB, TB).
     *
     * @param int $bytes Taille en octets
     * @return string Taille formatée avec unité (ex: "1.5 GB")
     */
    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;

        return round($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
    }

    /**
     * @brief Extrait les métadonnées d'un fichier vidéo local via FFprobe.
     *
     * Variante de getTechnicalMetadata optimisée pour les fichiers locaux.
     *
     * @param string $filePath Chemin absolu vers le fichier vidéo local
     * @return array|null Tableau de métadonnées ou null en cas d'échec
     */
    private function extractMetadataFromFile(string $filePath): ?array
    {
        $command = sprintf(
            'ffprobe -v quiet -print_format json -show_format -show_streams %s 2>&1',
            escapeshellarg($filePath)
        );

        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            Log::error("extractMetadataFromFile: ffprobe failed for {$filePath}");
            return null;
        }

        $json = implode("\n", $output);
        $data = json_decode($json, true);

        if (!$data || !isset($data['streams']) || !isset($data['format'])) {
            return null;
        }

        $videoStream = collect($data['streams'])->firstWhere('codec_type', 'video');

        $metadata = [
            'duree' => isset($data['format']['duration']) ? (float)$data['format']['duration'] : null,
            'duree_format' => null,
            'taille_octets' => isset($data['format']['size']) ? (int)$data['format']['size'] : null,
            'taille_format' => null,
            'bitrate' => isset($data['format']['bit_rate']) ? (int)$data['format']['bit_rate'] : null,
        ];

        if ($videoStream) {
            $metadata['largeur'] = $videoStream['width'] ?? null;
            $metadata['hauteur'] = $videoStream['height'] ?? null;
            $metadata['resolution'] = ($metadata['largeur'] && $metadata['hauteur'])
                ? $metadata['largeur'] . 'x' . $metadata['hauteur']
                : null;
            $metadata['codec_video'] = $videoStream['codec_name'] ?? null;
            $metadata['fps'] = null;

            if (isset($videoStream['r_frame_rate'])) {
                $parts = explode('/', $videoStream['r_frame_rate']);
                if (count($parts) === 2 && $parts[1] > 0) {
                    $metadata['fps'] = round($parts[0] / $parts[1], 2);
                }
            }
        }

        if ($metadata['duree']) {
            $seconds = (int)$metadata['duree'];
            $metadata['duree_format'] = sprintf('%02d:%02d:%02d',
                floor($seconds / 3600),
                floor(($seconds % 3600) / 60),
                $seconds % 60
            );
        }

        if ($metadata['taille_octets']) {
            $metadata['taille_format'] = $this->formatFileSize($metadata['taille_octets']);
        }

        return $metadata;
    }

    /**
     * @brief Nettoie une chaîne pour affichage sécurisé.
     *
     * Supprime les retours à la ligne pour éviter les problèmes d'affichage.
     *
     * @param string|null $value Chaîne à nettoyer
     * @return string Chaîne nettoyée (vide si null)
     */
    private function sanitizeForDisplay(?string $value): string
    {
        if (!$value) {
            return '';
        }
        return preg_replace('/[\r\n]+/', ' ', trim($value));
    }

    /**
     * @brief Synchronise un chemin local avec un média existant.
     *
     * Recherche un média par titre normalisé (sans extension, insensible à la casse)
     * et met à jour son chemin_local.
     *
     * IMPORTANT : Ne crée JAMAIS de média si aucun ne correspond en base.
     * Cette méthode ne fait que mettre à jour des médias existants.
     *
     * @param string $path Chemin complet du fichier local
     * @return bool true si un média a été trouvé et mis à jour, false sinon
     */
    public function syncLocalPath(string $path): bool
    {
        $title = pathinfo($path, PATHINFO_FILENAME);
        $fullPath = $path;
        $normalizedTitle = mb_strtolower(trim($title));

        $media = Media::whereRaw(
            'LOWER(mtd_tech_titre) = ?',
            [$normalizedTitle]
        )->first();

        if (!$media) {
            Log::warning('syncLocalPath: Media non trouvé', [
                'path' => $fullPath,
                'title' => $normalizedTitle,
            ]);
            return false;
        }

        $media->chemin_local = $fullPath;
        $media->save();

        Log::info('syncLocalPath: Chemin local mis à jour', [
            'media_id' => $media->id,
            'path' => $fullPath,
        ]);

        return true;
    }

}
