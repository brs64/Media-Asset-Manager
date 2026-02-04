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
            $diskRoot = rtrim(config('filesystems.disks.external_local.root'), '/\\');

            // Build full path for scanning
            $scanPath = $diskRoot . '/' . ltrim($directory, '/\\');
            $scanPath = rtrim($scanPath, '/\\');

            if (!is_dir($scanPath)) {
                return []; // chemin inexistant
            }

            $results = [];
            foreach (scandir($scanPath) as $file) {
                if ($file === '.' || $file === '..') continue;
                $fullPath = $scanPath . DIRECTORY_SEPARATOR . $file;

                // Store path relative to disk root (not absolute)
                $relativePath = ltrim($directory . '/' . $file, '/\\');

                if (is_dir($fullPath)) {
                    $results[] = [
                        'type' => 'folder',
                        'name' => $file,
                        'path' => $relativePath,
                        'disk' => $diskName,
                    ];
                } elseif (self::isVideo($file)) {
                    $results[] = [
                        'type' => 'video',
                        'name' => $file,
                        'path' => $relativePath,
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

    public static function scanDiskRecursive(string $diskName, string $path = '/', callable $onItemFound = null)
    {
        ini_set('memory_limit', '512M'); 
        ini_set('max_execution_time', 600); // 10 minutes

        $allFiles = [];
        
        $items = self::scanDisk($diskName, $path);

        foreach ($items as $item) {

            if ($onItemFound) {
                $onItemFound($item);
            }
    
            if ($item['type'] === 'folder') {

                self::scanDiskRecursive($diskName, $item['path'], $onItemFound);
            }
        }

        return $allFiles;
    }

    /**
     * Détection vidéo
     */
    public static function isVideo(string $filename): bool
    {
        return preg_match('/\.(mp4|mov|avi|mkv|webm|m4v|mxf)$/i', $filename);
    }
}
