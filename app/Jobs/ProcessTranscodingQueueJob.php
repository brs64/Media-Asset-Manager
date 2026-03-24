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

    public $tries = 1;
    public $timeout = 120;

    public function handle(FfastransService $ffastrans)
    {
        // 1. Singleton Lock (Prevents multiple managers from running)
        if (Cache::has('transcode_engine_lock')) return;
        Cache::put('transcode_engine_lock', true, 50);

        try {
            // A. UPDATE STATUS OF RUNNING VIDEOS
            // This is what the browser used to do. Now the job does it!
            $runningMedias = Media::where('transcode_status', 'en_cours')
                ->whereNotNull('transcode_job_id')
                ->get();

            foreach ($runningMedias as $m) {
                $status = $ffastrans->getJobStatus($m->transcode_job_id);
                $state = strtolower($status['state'] ?? '');
                
                if (in_array($state, ['success', 'finished', 'done', 'terminé'])) {
                    // 1. Mark as finished
                    $m->transcode_status = 'termine';
                    
                    // 2. GENERATE AND SAVE CHEMIN LOCAL (Moved from JS to PHP)
                    // We assume the local path matches the NAS relative path
                    $pathOnNas = $m->URI_NAS_ARCH ?? $m->URI_NAS_PAD;
                    if ($pathOnNas) {
                        $m->chemin_local = ltrim($pathOnNas, '/\\'); // Ensure it's a relative path
                    }

                    $m->save();
                    Log::info("Background Sync: Media {$m->id} marked as Terminé with path: {$m->chemin_local}");
                    
                } elseif (in_array($state, ['error', 'failed', 'echoué'])) {
                    $m->update(['transcode_status' => 'echoue']);
                }
            }

            // B. START NEW VIDEOS
            $limit = (int) env('NB_VIDEOS_FFASTRANS');
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

        // C. RECURSION (The Perpetual Motion)
        // If there is ANY video not finished, check again in 60 seconds
        $hasWork = Media::whereIn('transcode_status', ['en_attente', 'en_cours'])->exists();
        if ($hasWork) {
            self::dispatch()->onQueue('transcoding')->delay(now()->addSeconds(60));
            
            // Kick the worker to ensure it stays alive
            $cmd = 'php ' . base_path('artisan') . ' queue:work --queue=transcoding --stop-when-empty > /dev/null 2>&1 &';
            exec($cmd);
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
            Log::info("Background Start: Media {$media->id} sent to FFAStrans.");
        } catch (\Exception $e) {
            $media->update(['transcode_status' => 'echoue']);
        }
    }
}