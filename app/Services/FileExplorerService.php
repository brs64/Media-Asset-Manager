<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FileExplorerService
{
    /**
     * Adapted from your legacy 'controleurArborescence' (Local part)
     */
    /* public static function scanLocal($directory)
    {
        $results = [];
        
        // Check if directory exists
        if (!is_dir($directory)) return [];

        $items = scandir($directory);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..' || $item === '.gitkeep') continue;

            $path = $directory . '/' . $item;
            $relativePath = str_replace(public_path() . '/', '', $path); // Clean path for frontend

            if (is_dir($path)) {
                $results[] = [
                    'type' => 'folder',
                    'name' => $item,
                    'path' => $relativePath,
                    'children' => self::scanLocal($path) // Recursive call
                ];
            } elseif (self::isVideo($item)) {
                // Call your helper function here if needed
                // $id = getIdVideoURIetTitre(...); 
                $results[] = [
                    'type' => 'video',
                    'name' => $item,
                    'path' => $relativePath,
                    'id' => null // Add your ID logic here
                ];
            } else {
                $results[] = [
                    'type' => 'file',
                    'name' => $item,
                    'path' => $relativePath
                ];
            }
        }
        return $results;
    } */

    /**
     * Adapted from your legacy 'controleurArborescence' (FTP part)
     */
    /* public static function scanFtp($serverName)
    {
        // NOTE: In Laravel, it is HIGHLY recommended to use Storage::disk('ftp') 
        // configured in config/filesystems.php instead of raw ftp_connect.
        // However, here is your legacy logic adapted:
        
        if (!defined($serverName)) return []; // Safety check

        // Retrieve credentials (Moved from constants to .env/config in a real app)
        // For now, assuming you still have access to your constants or Helper
        // $conn_id = connexionFTP_NAS(...); 
        
        // Mocking empty return for safety until you migrate connection logic
        return []; 
    } */ 

    /**
     * Scans any configured disk (Local or FTP) recursively.
     * * @param string $diskName The key from config/filesystems.php (e.g., 'external_local', 'ftp_pad')
     * @param string $directory The sub-folder to scan (default is root)
     */
    public static function scanDisk($diskName, $directory = '/')
    {
        $results = [];
        
        // 1. Get the disk instance
        $disk = Storage::disk($diskName);

        // 2. Get all directories in the current path
        // (Laravel handles the FTP vs Local difference automatically)
        $directories = $disk->directories($directory);
        
        foreach ($directories as $dirPath) {
            // Get just the folder name (e.g. 'Vacation' instead of 'videos/Vacation')
            $folderName = basename($dirPath);
            
            $results[] = [
                'type' => 'folder',
                'name' => $folderName,
                'path' => $dirPath,
                'children' => self::scanDisk($diskName, $dirPath) // RECURSION
            ];
        }

        // 3. Get all files in the current path
        $files = $disk->files($directory);
        
        foreach ($files as $filePath) {
            $fileName = basename($filePath);

            // Skip hidden files or specific system files
            if ($fileName === '.gitkeep' || str_starts_with($fileName, '.')) continue;

            if (self::isVideo($fileName)) {
                // TODO: Here is where you would call your old helper:
                // $id = getIdVideoURIetTitre(...)
                // For now, we leave ID null.
                
                $results[] = [
                    'type' => 'video',
                    'name' => $fileName,
                    'path' => $filePath,
                    'disk' => $diskName, // Useful to know which disk this video belongs to later
                    'id'   => null 
                ];
            } else {
                $results[] = [
                    'type' => 'file',
                    'name' => $fileName,
                    'path' => $filePath
                ];
            }
        }

        return $results;
    }

    public static function isVideo($filename)
    {
        return preg_match('/\.(mp4|mov|avi|mkv)$/i', $filename);
    }
}