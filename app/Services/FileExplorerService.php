<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class FileExplorerService
{
    /**
     * Scan un dossier (1 seul niveau)
     * Retourne uniquement :
     *  - les dossiers
     *  - les fichiers vidéos
     */
    public static function scanDisk(string $diskName, string $directory): array
    {
        // On nettoie le chemin
        $directory = rtrim($directory, '/\\');

        // Cas particulier : disque local absolu (Windows / Linux)
        if ($diskName === 'external_local') {
            if (!is_dir($directory)) {
                return []; // chemin inexistant
            }

            $results = [];
            foreach (scandir($directory) as $file) {
                if ($file === '.' || $file === '..') continue;
                $fullPath = $directory . DIRECTORY_SEPARATOR . $file;

                if (is_dir($fullPath)) {
                    $results[] = [
                        'type' => 'folder',
                        'name' => $file,
                        'path' => $fullPath,
                        'disk' => $diskName,
                    ];
                } elseif (self::isVideo($file)) {
                    $results[] = [
                        'type' => 'video',
                        'name' => $file,
                        'path' => $fullPath,
                        'disk' => $diskName,
                        'id' => null,
                    ];
                }
            }

            // Tri alphabétique
            usort($results, fn($a, $b) => strcasecmp($a['name'], $b['name']));

            return $results;
        }

        // Cas normal : NAS / FTP / disque Laravel
        $disk = Storage::disk($diskName);
        $results = [];

        try {
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

            $files = $disk->files($directory);
            sort($files);

            foreach ($files as $filePath) {
                $fileName = basename($filePath);

                if ($fileName === '.gitkeep' || str_starts_with($fileName, '.')) {
                    continue;
                }

                if (self::isVideo($fileName)) {
                    $results[] = [
                        'type' => 'video',
                        'name' => $fileName,
                        'path' => $filePath,
                        'disk' => $diskName,
                        'id'   => null,
                    ];
                }
            }
        } catch (\Throwable $e) {
            // Si erreur FTP ou disque inaccessible, on renvoie vide
            return [];
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
