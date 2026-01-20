<?php

namespace App\Jobs;

use App\Services\FileExplorerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ScanDiskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $disk;
    public string $path;
    public string $scanId;

    // Timeout du job (secondes)
    public $timeout = 0;

    // Nombre de tentatives
    public $tries = 1;

    public function __construct(string $disk, string $path, string $scanId)
    {
        $this->disk = $disk;
        $this->path = $path;
        $this->scanId = $scanId;
    }

    /* public function handle()
    {
        try {
            Log::info("Scan FTP démarré", [
                'disk' => $this->disk,
                'path' => $this->path,
                'scan_id' => $this->scanId,
            ]);

            // Lancement du scan récursif
            $results = FileExplorerService::scanDiskRecursive($this->disk, $this->path);

            // Compter les fichiers de type 'video' par exemple
            $fileCount = collect($results)
                ->where('type', 'video')
                ->count();

            // Stocker les résultats complets
            Cache::put(
                "scan:{$this->scanId}:results",
                $results,
                now()->addHours(2)
            );

            // Stocker le nombre de fichiers OK
            Cache::put(
                "scan:{$this->scanId}:count",
                $fileCount,
                now()->addHours(2)
            );

            // Statut terminé
            Cache::put(
                "scan:{$this->scanId}:status",
                'done',
                now()->addHours(2)
            );

            Log::info("Scan FTP terminé", [
                'scan_id' => $this->scanId,
                'count' => $fileCount,
            ]);

        } catch (\Throwable $e) {
            Log::error("Erreur lors du scan FTP", [
                'scan_id' => $this->scanId,
                'disk' => $this->disk,
                'path' => $this->path,
                'message' => $e->getMessage(),
            ]);

            Cache::put(
                "scan:{$this->scanId}:status",
                'failed',
                now()->addHours(2)
            );

            // Stocker 0 fichiers si échec
            Cache::put(
                "scan:{$this->scanId}:count",
                0,
                now()->addHours(2)
            );
        }
    } */

    public function handle()
    {
        Log::info("Scan FTP démarré avec Live Feedback", ['scan_id' => $this->scanId]);

        // We will hold files here temporarily until we have 10 of them
        $buffer = []; 

        try {
            FileExplorerService::scanDiskRecursive(
                $this->disk, 
                $this->path, 
                function ($item) use (&$buffer) {
                    
                    // We only care about VIDEO files for the live counter
                    if (($item['type'] ?? '') === 'video') {
                        $buffer[] = $item;

                        // EVERY 10 VIDEOS -> Update the Cache
                        if (count($buffer) >= 10) {
                            $this->flushBufferToCache($buffer);
                            $buffer = []; // Empty the buffer
                        }
                    }
                }
            );

            // Save any remaining files in the buffer (e.g. the last 4 files)
            if (count($buffer) > 0) {
                $this->flushBufferToCache($buffer);
            }

            // Mark as DONE
            Cache::put("scan:{$this->scanId}:status", 'done', now()->addHours(2));
            Log::info("Scan FTP terminé avec succès");

        } catch (\Throwable $e) {
            Log::error("Erreur Scan: " . $e->getMessage());
            Cache::put("scan:{$this->scanId}:status", 'failed', now()->addHours(2));
        }
    }

    // Helper function to keep code clean
    protected function flushBufferToCache(array $newFiles)
    {
        $cacheKey = "scan:{$this->scanId}:results";
        
        // 1. Get current list
        $currentList = Cache::get($cacheKey, []);
        
        // 2. Merge new files
        $updatedList = array_merge($currentList, $newFiles);
        
        // 3. Save back to cache
        Cache::put($cacheKey, $updatedList, now()->addHours(2));

        // 4. Update the visible counter
        Cache::put("scan:{$this->scanId}:count", count($updatedList), now()->addHours(2));
    }
}
