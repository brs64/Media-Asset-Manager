<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FileExplorerService;
use App\Services\FfastransService;
use App\Models\Media;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WatchNewFiles extends Command
{
    protected $signature = 'files:watch';
    protected $description = 'Scan current academic year folder for new videos';

    protected $ffastrans;

    public function __construct(FfastransService $ffastrans)
    {
        parent::__construct();
        $this->ffastrans = $ffastrans;
    }

    public function handle()
    {
        // 1. Calculate Current Academic Year Folder
        $now = Carbon::now();
        $startYear = ($now->month >= 9) ? $now->year : $now->year - 1;
        $endYear = $startYear + 1;
        $targetFolder = "{$startYear}-{$endYear}"; // e.g., "2024-2025"

        $this->info("Année académique ciblée : $targetFolder");

        // 2. Config: Disk Name -> Database Column
        $disksConfig = [
            'ftp_pad'  => 'URI_NAS_PAD', // Must match model column names
            // 'nas_arch' => 'URI_NAS_ARCH',
        ];

        foreach ($disksConfig as $diskName => $dbColumn) {
            
            // 3. Scan ONLY the target folder
            try {
                $this->info("Analyse du disque $diskName dans le dossier $targetFolder...");
                
                // This works perfectly with your service signature
                $tree = FileExplorerService::scanDisk($diskName, $targetFolder); 
                $flatList = $this->flattenTree($tree);

            } catch (\Exception $e) {
                // Warning in French
                Log::warning("Folder $targetFolder not found on $diskName. Skipping.");
                $this->warn("Dossier $targetFolder introuvable sur $diskName (ou inaccessible). Ignoré.");
                continue;
            }

            // 4. Processing Loop (Max 2000 items)
            $count = 0;
            $newFilesCount = 0;

            foreach ($flatList as $file) {
                if ($file['type'] !== 'video') continue;
                
                // Safety break
                if ($count++ > 2000) {
                    $this->warn("Trop de fichiers dans le scan (>2000). Arrêt de sécurité.");
                    Log::warning("Too many files in scan. Stopping for safety.");
                    break;
                }

                $filePath = $file['path'];

                // A. Check Database (Permanent Memory)
                $inDatabase = Media::where($dbColumn, $filePath)->exists();
                if ($inDatabase) continue;

                // B. Check Cache (Temporary Memory - 24h)
                $cacheKey = 'processing_' . md5($filePath);
                if (Cache::has($cacheKey)) continue;

                // C. It's New!
                $this->info("✨ Nouveau fichier détecté : $filePath");
                $this->triggerAutomation($filePath, $diskName, $cacheKey);
                $newFilesCount++;
            }

            if ($newFilesCount === 0) {
                $this->line("Aucun nouveau fichier sur $diskName.");
            }
        }
        
        $this->info("Scan terminé.");
    }

    private function triggerAutomation($filePath, $diskName, $cacheKey)
    {
        // 1. Determine Workflow ID
        // Still need to add logic to determine the right workflow per disk if needed
        $workflowId = '20260107-1346-5181-4d80-590c5ffd0313'; 

        try {
            // 2. Submit Job via Service
            $response = $this->ffastrans->submitJob($filePath, $workflowId);
            $jobId = $response['job_id'] ?? 'inconnu';

            $this->comment("   -> Job lancé avec succès (ID: $jobId)");

            // 3. Save to Cache for 24 hours
            // Prevents re-triggering the same file while it's processing
            Cache::put($cacheKey, $jobId, now()->addHours(24));

        } catch (\Exception $e) {
            $this->error("   -> Erreur lors du lancement : " . $e->getMessage());
            Log::error("Failed to auto-process $filePath: " . $e->getMessage());
        }
    }

    /**
     * Helper to flatten the recursive tree from FileExplorerService
     */
    private function flattenTree($nodes) {
        $result = [];
        foreach ($nodes as $node) {
            if ($node['type'] === 'folder') {
                $result = array_merge($result, $this->flattenTree($node['children']));
            } else {
                $result[] = $node;
            }
        }
        return $result;
    }
}