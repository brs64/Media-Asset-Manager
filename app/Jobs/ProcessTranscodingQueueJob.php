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
use Illuminate\Support\Facades\Storage;

class ProcessTranscodingQueueJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    public $timeout = 300; // 5 minutes to handle slow folder submissions

    public function handle(FfastransService $ffastrans)
    {
        // Use an atomic lock with an owner ID. 
        // If the lock is already taken, this job exits immediately.
        $lock = Cache::lock('transcode_engine_lock', 90); // Lock for 2 minutes

        if (!$lock->get()) {
            return; // Another worker is already doing the work. Safe exit.
        }

        try {
            // A. UPDATE STATUSES
            $runningMedias = Media::where('transcode_status', 'en_cours')
                ->whereNotNull('transcode_job_id')
                ->where('transcode_job_id', '!=', 'unknown')
                ->get();

            foreach ($runningMedias as $m) {
                try {
                    $status = $ffastrans->getJobStatus($m->transcode_job_id);

                    if (in_array($status['source'], ['timeout', 'pending'])) {
                        continue; 
                    }

                    $state = strtolower($status['state'] ?? '');
                    
                    /*$isApiFinished = ($status['source'] === 'history' && $state === 'success') || 
                         ($status['source'] === 'active' && in_array($state, ['success', 'finished', 'done', 'terminé']));*/

                    $isApiFinished = ($status['source'] === 'history' && $state === 'success');

                    if ($isApiFinished) {
                        // TODO: Récupérer le chemin en sortie depuis FFAStrans et ne pas le deviner serait plus propre..
                        $pathOnNas = $m->URI_NAS_ARCH ?: $m->URI_NAS_PAD;
                        $localFileName = preg_replace('/\.(mxf|mov|avi|mkv)$/i', '.mp4', ltrim($pathOnNas, '/\\'));

                        $diskRoot = rtrim(config('filesystems.disks.external_local.root'), '/\\');
                        $fullLocalPath = $diskRoot . '/' . ltrim($localFileName, '/');

                        $m->update([
                            'transcode_status' => 'termine',
                            'chemin_local' => $localFileName 
                        ]);

                        Log::info("Engine Success: Media {$m->id} finalized. API trusted.");
                    }    
                    elseif (str_contains($state, 'abort') || str_contains($state, 'cancel')) {
                        // Handle Aborted jobs in the background engine
                        $m->update(['transcode_status' => 'annule']);
                    }
                    elseif (str_contains($state, 'fail') || str_contains($state, 'err') || str_contains($state, 'echou')) {
                        $m->update(['transcode_status' => 'echoue']);
                    }
                } catch (\Exception $e) {
                    Log::error("Transcode Engine Error: " . $e->getMessage());
                }
            }

            // B. START NEW VIDEOS (Respect the NB_VIDEOS_FFASTRANS limit)
            $limit = (int) config('btsplay.process.max_videos');
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
            sleep(10);
            $lock->release(); // Release the lock before re-dispatching
            // Schedule the next check in 30 seconds
            self::dispatch()->onQueue('transcoding');
            
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
        $filePath = $media->URI_NAS_ARCH ?: $media->URI_NAS_PAD;
        $disk = (!empty($media->URI_NAS_ARCH)) ? 'nas_arch' : 'ftp_pad';
        $workflowId = config('btsplay.process.workflow_id');
        $windowsRoot = ($disk === 'nas_arch') ? config('btsplay.uris.nas_arch_win') : config('btsplay.uris.nas_pad_win');
        
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