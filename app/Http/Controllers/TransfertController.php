<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FfastransService;
use App\Models\Media;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Jobs\ProcessTranscodingQueueJob;

class TransfertController extends Controller
{
    protected $ffastrans;

    public function __construct(FfastransService $ffastrans)
    {
        $this->ffastrans = $ffastrans;
    }

    public function index()
    {
        $maxConcurrent = config('btsplay.process.max_videos');
        return view('admin.transferts', compact('maxConcurrent'));
    }
    
    public function list(FfastransService $ffastrans)
    {
        $query = Media::whereNull('chemin_local')
                ->where('transcode_status', '!=', 'termine')
                ->select('id', 'mtd_tech_titre', 'URI_NAS_PAD', 'URI_NAS_ARCH', 'transcode_status', 'transcode_job_id')
                ->get();

        $results = $query->map(function ($media) {
            $availablePaths = [];
            if (!empty($media->URI_NAS_PAD)) $availablePaths[] = ['label' => 'NAS_PAD', 'path' => $media->URI_NAS_PAD];
            if (!empty($media->URI_NAS_ARCH)) $availablePaths[] = ['label' => 'NAS_ARCH', 'path' => $media->URI_NAS_ARCH];

            $primaryPath = $media->URI_NAS_ARCH ?? $media->URI_NAS_PAD;
            $primaryDisk = (!empty($media->URI_NAS_ARCH)) ? 'nas_arch' : 'ftp_pad';
            $dbStatus = $media->transcode_status ?? 'disponible';
            
            $statusLabelMap = [
                'en_attente' => "En file d'attente",
                'en_cours'   => 'Démarrage...',
                'termine'    => 'Terminé',
                'echoue'     => 'Echoué',
                'annule'     => 'Annulé',
                'disponible' => 'Disponible'
            ];

            return [
                'id'              => $media->id,
                'filename'        => basename($primaryPath),
                'path'            => $primaryPath, 
                'disk'            => $primaryDisk, 
                'available_paths' => $availablePaths,
                'job_id'          => $media->transcode_job_id, 
                'status'          => $statusLabelMap[$dbStatus] ?? 'Disponible',
                'progress'        => ($dbStatus === 'termine') ? 100 : 0,
                'finished'        => in_array($dbStatus, ['termine', 'echoue', 'annule']),
                'is_queued'       => ($dbStatus === 'en_attente')
            ];
        });

        return response()->json(['results' => $results]);
    }

    public function startJob(Request $request)
    {
        $media = Media::find($request->input('id'));
        if (!$media) return response()->json(['success' => false, 'message' => 'Media introuvable'], 404);

        if ($request->input('action') === 'queue') {
            $media->update(['transcode_status' => 'en_attente']);

            \App\Jobs\ProcessTranscodingQueueJob::dispatch()
                    ->onQueue('transcoding')
                    ->delay(now()->subSeconds(3605)); // Dispatch with a past delay to trigger immediately

            /*$command = 'php ' . base_path('artisan') . ' queue:work --stop-when-empty > /dev/null 2>&1 &';
            pclose(popen($command, "r"));*/
            $command = 'php ' . base_path('artisan') . ' queue:work --queue=transcoding --stop-when-empty > /dev/null 2>&1 &';
            exec($command);

            return response()->json(['success' => true]);
        }

        $success = $this->actuallyStartJob($media);
        
        return $success 
            ? response()->json(['success' => true, 'job_id' => $media->transcode_job_id])
            : response()->json(['success' => false, 'message' => 'Erreur lors du lancement FFAStrans'], 500);
    }

    public function checkStatus($jobId)
    {
        try {
            $apiData = $this->ffastrans->getJobStatus($jobId);
            $source = $apiData['source'] ?? 'not_found';
            $rawState = strtolower($apiData['state'] ?? '');
            
            $progress = $apiData['progress'] ?? 0;
            $finished = false;
            $label = 'En cours';

            // Find the media record to update its status in the DB during polling
            $media = Media::where('transcode_job_id', $jobId)->first();

            if (in_array($rawState, ['success', 'finished', 'done', 'terminé']) || ($source === 'history' && !in_array($rawState, ['error', 'failed']))) {
                $label = 'Terminé';
                $progress = 100;
                $finished = true;
                if ($media) $media->update(['transcode_status' => 'termine']);
            } 
            elseif (in_array($rawState, ['error', 'failed', 'aborted', 'echoué'])) {
                $label = 'Echoué';
                $finished = true;
                if ($media) $media->update(['transcode_status' => 'echoue']);
            } 
            elseif ($source === 'active') {
                $label = "Node " . ($apiData['steps'] ?? '?') . ": " . ($apiData['proc'] ?? 'Traitement');
                $finished = false;
                if ($media && $media->transcode_status !== 'en_cours') {
                    $media->update(['transcode_status' => 'en_cours']);
                }
            }

            return response()->json([
                'progress' => $progress,
                'label' => $label,
                'finished' => $finished
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function cancel($id)
    {
        if (str_starts_with($id, 'queue-')) {
            $dbId = str_replace('queue-', '', $id);
            Media::where('id', $dbId)->update([
                'transcode_status' => 'annule',
                'transcode_job_id' => null
            ]);
            return response()->json(['success' => true]);
        }

        $success = $this->ffastrans->cancelJob($id);
        if ($success) {
            Media::where('transcode_job_id', $id)->update(['transcode_status' => 'annule']);
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false], 500);
    }


    public function actuallyStartJob(Media $media)
    {
        $disk = (!empty($media->URI_NAS_ARCH)) ? 'nas_arch' : 'ftp_pad';
        $filePath = $media->URI_NAS_ARCH ?? $media->URI_NAS_PAD;
        $workflowId = config('btsplay.process.workflow_id');

        $windowsRoot = ($disk === 'nas_arch') ? env('URI_NAS_ARCH_WIN') : env('URI_NAS_PAD_WIN');
        $uncInputFile = rtrim(str_replace('/', '\\', $windowsRoot), '\\') . '\\' . ltrim(str_replace('/', '\\', $filePath), '\\');
        $variableData = ltrim(str_replace('/', '\\', dirname($filePath)), '\\') . '\\';

        try {
            $response = $this->ffastrans->submitJob($uncInputFile, $workflowId, [['name' => 's_project_path', 'data' => $variableData]]);
            $jobId = $response['job_id'] ?? 'unknown';

            $media->update([
                'transcode_status' => 'en_cours',
                'transcode_job_id' => $jobId
            ]);
            return true;
        } catch (\Exception $e) {
            $media->update(['transcode_status' => 'echoue']);
            return false;
        }
    }

    public function getDbStatus($id)
    {
        try {
            $media = Media::select('id', 'transcode_status', 'transcode_job_id')->find($id);
            
            if (!$media) return response()->json(['job_id' => null, 'status' => 'Disponible']);

            // THE HEARTBEAT
            if ($media->transcode_status === 'en_attente' && !Cache::has('worker_kick_throttle')) {
                Cache::put('worker_kick_throttle', true, 5); 

                // Backdated dispatch
                ProcessTranscodingQueueJob::dispatch()
                    ->onQueue('transcoding')
                    ->delay(now()->subSeconds(3605));
                    
                $command = 'php ' . base_path('artisan') . ' queue:work --queue=transcoding --stop-when-empty > /dev/null 2>&1 &';
                exec($command);
            }

            $statusLabelMap = [
                'en_attente' => "En file d'attente",
                'en_cours'   => 'Démarrage...',
                'termine'    => 'Terminé',
                'echoue'     => 'Echoué',
                'annule'     => 'Annulé',
                'disponible' => 'Disponible'
            ];

            return response()->json([
                'job_id' => $media->transcode_job_id,
                'status' => $statusLabelMap[$media->transcode_status] ?? 'Disponible'
            ]);
        } catch (\Exception $e) {
            // Log the error but return a valid JSON so the JS doesn't crash
            Log::error("getDbStatus Error: " . $e->getMessage());
            return response()->json(['job_id' => null, 'status' => 'Sync en cours...'], 200);
        }
    }
}