<?php

namespace App\Jobs;

use App\Models\Media;
use App\Services\FfastransService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessTranscodingQueueJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    public $timeout = 300; // 5 minutes to handle slow folder submissions

    public function handle(FfastransService $ffastrans)
    {
        // 1. SINGLETON LOCK: Prevents multiple "Engines" from fighting (Fixes Deadlocks)
        if (Cache::has('transcode_engine_lock')) return;
        Cache::put('transcode_engine_lock', true, 600); // 10 min safety window

        try {
            // A. UPDATE STATUSES
            $runningMedias = Media::where('transcode_status', 'en_cours')
                ->whereNotNull('transcode_job_id')
                ->where('transcode_job_id', '!=', 'unknown')
                ->get();

            foreach ($runningMedias as $m) {
                $status = $ffastrans->getJobStatus($m->transcode_job_id);
                $state = strtolower($status['state'] ?? '');
                
                // SAFETY: Require 100% progress in history to prevent "Heavy Video" premature finishing
                if (($status['source'] === 'history' && $status['progress'] == 100) || 
                    in_array($state, ['success', 'finished', 'done', 'terminé'])) {
                    
                    $pathOnNas = $m->URI_NAS_ARCH ?? $m->URI_NAS_PAD;
                    $localFileName = ltrim($pathOnNas, '/\\');

                    $fullLocalPath = config('filesystems.disks.external_local.root') . DIRECTORY_SEPARATOR . $localFileName;

                    if (file_exists($fullLocalPath)) {
                        $m->update([
                            'transcode_status' => 'termine',
                            'chemin_local' => $localFileName
                        ]);
                        Log::info("Engine: Media {$m->id} finalized successfully.");
                    } else {
                        Log::warning("Engine: FFAStrans says finished, but file not yet visible at: {$fullLocalPath}");
                        // The job stays 'en_cours' and will check again in 30 seconds.
                    }
                    
                } elseif (in_array($state, ['error', 'failed', 'echoué'])) {
                    $m->update(['transcode_status' => 'echoue']);
                }
            }

            // B. START NEW VIDEOS (Respect the NB_VIDEOS_FFASTRANS limit)
            $limit = (int) env('NB_VIDEOS_FFASTRANS', 2);
            $activeJobs = $ffastrans->getFullStatusList();
            $activeCount = collect($activeJobs)->where('is_finished', false)->count();

            if ($activeCount < $limit) {
                $needed = $limit - $activeCount;
                $nextOnes = Media::where('transcode_status', 'en_attente')
                    ->orderBy('updated_at', 'asc')
                    ->take($needed)
                    ->get();

                foreach ($nextOnes as $media) {
                    $this->submitMedia($media, $ffastrans);
                }
            }
        } catch (\Exception $e) {
            Log::error("Transcode Engine Error: " . $e->getMessage());
        } finally {
            Cache::forget('transcode_engine_lock');
        }

        // C. RECURSION & AUTO-STOP (The Core Logic)
        // Check if there is still work to do
        $hasWork = Media::whereIn('transcode_status', ['en_attente', 'en_cours'])->exists();
        
        if ($hasWork) {
            // Schedule the next check in 30 seconds
            self::dispatch()->onQueue('transcoding')->delay(now()->addSeconds(30));
            
            // Helper logic to start the worker ONLY if it isn't already running
            $isWorkerRunning = shell_exec('ps aux | grep "queue:work --queue=transcoding" | grep -v grep');

            if (!$isWorkerRunning) {
                $command = 'php ' . base_path('artisan') . ' queue:work --queue=transcoding --stop-when-empty > /dev/null 2>&1 &';
                exec($command);
            }
        } else {
            Log::info("Engine: No more work found. Stopping process to save server resources.");
        }
    }

    protected function submitMedia(Media $media, FfastransService $ffastrans)
    {
        $filePath = $media->URI_NAS_ARCH ?? $media->URI_NAS_PAD;
        $disk = (!empty($media->URI_NAS_ARCH)) ? 'nas_arch' : 'ftp_pad';
        $workflowId = config('btsplay.process.workflow_id');
        $windowsRoot = ($disk === 'nas_arch') ? env('URI_NAS_ARCH_WIN') : env('URI_NAS_PAD_WIN');
        
        $uncInputFile = rtrim(str_replace('/', '\\', $windowsRoot), '\\') . '\\' . ltrim(str_replace('/', '\\', $filePath), '\\');
        $variableData = ltrim(str_replace('/', '\\', dirname($filePath)), '\\') . '\\';

        try {
            $response = $ffastrans->submitJob($uncInputFile, $workflowId, [['name' => 's_project_path', 'data' => $variableData]]);
            $media->update([
                'transcode_status' => 'en_cours',
                'transcode_job_id' => $response['job_id'] ?? 'unknown'
            ]);
        } catch (\Exception $e) {
            $media->update(['transcode_status' => 'echoue']);
        }
    }
}