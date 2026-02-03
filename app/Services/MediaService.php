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

/**
 * Media management service
 * Contains all business logic for media operations
 */
class MediaService
{
    /**
     * Get complete information about a media
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

            // Roles
            'mtdRoles' => $this->formatParticipations($media->participations),
        ];
    }

    /**
     * Update media metadata
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
     * Assign participants with their roles to a media
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
     * Delete a media and its participations
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
     * Get recently modified media
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
     * Search media with filters (Title, Description, Project Name)
     */
  public function searchMedia(array $filtres)
{
    $query = Media::query();

    if (!empty($filtres['keyword'])) {
        $kw = $filtres['keyword'];

        $query->where(function($q) use ($kw) {
            $q->where('mtd_tech_titre', 'like', "%{$kw}%")//filtre titre
              ->orWhere('description', 'like', "%{$kw}%")//filtre description
              ->orWhere('theme', 'like', "%{$kw}%")  // filtre thheme
              ->orWhere('promotion', 'like', "%{$kw}%")//filtre promotion
              ->orWhere('type', 'like', "%{$kw}%");    // filtre type
            
            // Si on veut aussi chercher par nom + prenom de prof
            $q->orWhereHas('professeur', function($sq) use ($kw) {
                $sq->where('nom', 'like', "%{$kw}%")
                ->orWhere('prenom', 'like', "%{$kw}%")
                ->orWhere(DB::raw("CONCAT(prenom, ' ', nom)"), 'like', "%{$kw}%")
                ->orWhere(DB::raw("CONCAT(nom, ' ', prenom)"), 'like', "%{$kw}%");
            });
        });
    }

    return $query->paginate(12);


        // --- 2. SPECIFIC DROPDOWN FILTERS (Strict AND conditions) ---

        // Specific Title Filter (if used separately)
        if (!empty($filtres['titre'])) {
            $query->where('mtd_tech_titre', 'like', '%' . $filtres['titre'] . '%');
        }

        // Project Dropdown Filter (Exact Match)
        if (!empty($filtres['projet_id'])) {
            // Using whereHas ensures it works for Many-to-Many relationships
            $query->whereHas('projets', function ($q) use ($filtres) {
                $q->where('projets.id', $filtres['projet_id']);
            });
        }

        // Professor Dropdown Filter
        if (!empty($filtres['professeur_id'])) {
            $query->where('professeur_id', $filtres['professeur_id']);
        }

        // Promotion Filter
        if (!empty($filtres['promotion'])) {
            $query->where('promotion', 'like', '%' . $filtres['promotion'] . '%');
        }

        // Type Filter
        if (!empty($filtres['type'])) {
            $query->where('type', 'like', '%' . $filtres['type'] . '%');
        }

        // Return results paginated
        return $query->orderBy('created_at', 'desc')->paginate(20);
    }

    // --- Private utility methods ---

    /**
     * Find or create a professor from their full name
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
     * Find or create a project
     */
    private function findOrCreateProject(string $libelle): Projet
    {
        return Projet::firstOrCreate(['libelle' => $libelle]);
    }

    /**
     * Update all participations for a media
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
     * Format participations for display
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
     * Extract video title from filename
     * Format: YEAR_PROJECT_TITLE.ext
     */
    private function extractVideoTitle(string $filename): string
    {
        if (preg_match("/^[^_]*_[^_]*_(.*)(?=\.)/", $filename, $matches)) {
            return $matches[1] ?? pathinfo($filename, PATHINFO_FILENAME);
        }

        return pathinfo($filename, PATHINFO_FILENAME);
    }

    /**
     * Extract person's last name (before last space)
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
     * Extract person's first name (after last space)
     */
    private function extractFirstName(string $fullName): string
    {
        $parts = explode(' ', trim($fullName));
        return array_pop($parts);
    }

    /**
     * Extract technical metadata from video via FTP without downloading
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
     * Format file size in bytes to readable format
     */
    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;

        return round($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
    }

    /**
     * Extract metadata from local file using ffprobe
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
     * Sanitize string for display: remove newlines
     */
    private function sanitizeForDisplay(?string $value): string
    {
        if (!$value) {
            return '';
        }
        return preg_replace('/[\r\n]+/', ' ', trim($value));
    }

    /**
     * Synchronise un chemin local avec un media existant
     * IMPORTANT : Ne crée jamais de media dans le cas ou aucune correspondance n'est faite en BDD
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
