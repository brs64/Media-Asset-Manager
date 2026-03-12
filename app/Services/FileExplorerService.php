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
    public function scanDisk(string $diskName, string $directory): array
    {
        $directory = rtrim($directory, '/\\');

        // Cas particulier : disque local absolu (Windows / Linux)
        if ($diskName === 'external_local') {
            if (!is_dir($directory)) return [];

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
                } elseif ($this->isVideo($file)) {
                    $results[] = [
                        'type' => 'video',
                        'name' => $file,
                        'path' => $fullPath,
                        'disk' => $diskName,
                        'id'   => null,
                    ];
                }
            }

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
                if ($fileName === '.gitkeep' || str_starts_with($fileName, '.')) continue;
                if ($this->isVideo($fileName)) {
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
            return [];
        }

        return $results;
    }

    public function scanDiskRecursive(string $diskName, string $path = '/', callable $onItemFound = null)
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 600);

        $allFiles = [];
        $items = $this->scanDisk($diskName, $path);

        foreach ($items as $item) {
            if ($onItemFound) $onItemFound($item);

            if ($item['type'] === 'folder') {
                $this->scanDiskRecursive($diskName, $item['path'], $onItemFound);
            }
        }

        return $allFiles;
    }

    public function isVideo(string $filename): bool
    {
        return preg_match('/\.(mp4|mov|avi|mkv|webm|m4v|mxf)$/i', $filename);
    }
}