<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class FileExplorerService
{
    /**
     * Scan un dossier (1 seul niveau)
     * Retourne uniquement :
     *  - les dossiers
     *  - les fichiers vidéos
     */
    public static function scanDisk(string $diskName, string $directory = '/'): array
    {
        $disk = Storage::disk($diskName);
        $results = [];

        echo "<script>console.log($diskName " / " $directory);</script>";

        // 📁 Dossiers
        $directories = $disk->directories($directory);
        sort($directories);

        foreach ($directories as $dirPath) {
            $results[] = [
                'type' => 'folder',
                'name' => basename($dirPath),
                'path' => $dirPath,
                'disk' => $diskName,
            ];
        }

        // 🎬 Fichiers vidéos uniquement
        $files = $disk->files($directory);
        sort($files);

        foreach ($files as $filePath) {
            $fileName = basename($filePath);

            // fichiers cachés ou non vidéos
            if ($fileName === '.gitkeep' || str_starts_with($fileName, '.')) {
                continue;
            }

            if (self::isVideo($fileName)) {
                $results[] = [
                    'type' => 'video',
                    'name' => $fileName,
                    'path' => $filePath,
                    'disk' => $diskName,
                    'id'   => null, // prêt pour ton helper legacy
                ];
            }
        }

        return $results;
    }

    /**
     * Détection vidéo
     */
    public static function isVideo(string $filename): bool
    {
        return preg_match('/\.(mp4|mov|avi|mkv|webm|m4v|mxf)$/i', $filename);
    }
}
