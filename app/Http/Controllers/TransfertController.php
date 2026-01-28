<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FfastransService;
use App\Models\Media;
use Illuminate\Support\Facades\Log;

class TransfertController extends Controller
{
    protected $ffastrans;

    public function __construct(FfastransService $ffastrans)
    {
        $this->ffastrans = $ffastrans;
    }

    public function index()
    {
        $maxConcurrent = config('btsplay.process.max_concurrent_transferts');
        return view('admin.transferts', compact('maxConcurrent'));
    }
    
    public function list(FfastransService $ffastrans)
    {
        $activeMap = [];
        try {
            $allJobs = $ffastrans->getFullStatusList();
            
            foreach ($allJobs as $job) {
                if (isset($job['filename'])) {
                    $key = pathinfo($job['filename'], PATHINFO_FILENAME);
                   
                    if ($job['is_finished'] === false) {
                        $activeMap[$key] = $job;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("FFAStrans Status Check Failed: " . $e->getMessage());
            $activeMap = [];
        }

        $padRoot = rtrim(config('btsplay.uris.nas_pad'), '/');
        $archRoot = rtrim(config('btsplay.uris.nas_arch'), '/');

        $query = Media::whereNull('chemin_local')
            ->where(function ($q) use ($padRoot, $archRoot) {
                if ($padRoot) $q->where('URI_NAS_PAD', 'LIKE', "{$padRoot}%");
                if ($archRoot) $q->orWhere('URI_NAS_ARCH', 'LIKE', "{$archRoot}%");
            });

        $results = [];

        foreach ($query->cursor() as $media) {
            
            if (!empty($media->URI_NAS_ARCH)) {
                $finalPath = $media->URI_NAS_ARCH;
                $finalDisk = 'nas_arch';
                $displayExt = '.mp4';
                $sourceLabel = 'NAS_ARCH';
            } else {
                $finalPath = $media->URI_NAS_PAD ?? ''; 
                $finalDisk = 'ftp_pad';
                $displayExt = '.mxf';
                $sourceLabel = 'NAS_PAD';
            }

            if (empty($finalPath)) continue; 

            $nameWithoutExt = pathinfo($finalPath, PATHINFO_FILENAME);
            if (empty($nameWithoutExt) || $nameWithoutExt === '.') {
                $nameWithoutExt = $media->mtd_tech_titre ?? 'Video_' . $media->id;
            }
            $fullFilename = $nameWithoutExt . $displayExt;

            $item = [
                'id'       => $media->id,
                'filename' => $fullFilename,
                'path'     => $finalPath,
                'disk'     => $finalDisk,
                'source'   => $sourceLabel,
                'job_id'   => null,
                'status'   => 'En attente',
                'progress' => 0,
                'finished' => false
            ];

            if (isset($activeMap[$nameWithoutExt])) {
                $job = $activeMap[$nameWithoutExt];
                $item['job_id']   = $job['id'];
                $item['status']   = $job['status']; // Already French from Service
                $item['progress'] = $job['progress'];
                $item['finished'] = $job['is_finished'];
            }

            $results[] = $item;
        }

        return response()->json([
            'status' => 'done',
            'count' => count($results),
            'results' => $results
        ]);
    }

    public function startJob(Request $request)
    {
        $filePath = $request->input('path');
        $disk = $request->input('disk');
        $workflowId = config('btsplay.process.workflow_id'); 

        $windowsRoot = '';
        if ($disk === 'nas_arch') {
            $windowsRoot = env('URI_NAS_ARCH_WIN'); 
        } elseif ($disk === 'ftp_pad') {
            $windowsRoot = env('URI_NAS_PAD_WIN'); 
        }

        $windowsRoot = str_replace('/', '\\', $windowsRoot);
        $directoryPart = dirname($filePath);
        $winRelativeDir  = str_replace('/', '\\', $directoryPart);
        $winRelativeFile = str_replace('/', '\\', $filePath);
        $uncInputFile = rtrim($windowsRoot, '\\') . '\\' . ltrim($winRelativeFile, '\\');
        $variableData = ltrim($winRelativeDir, '\\') . '\\';

        $variables = [
            ['name' => 's_project_path', 'data' => $variableData]
        ];

        try {
            $response = $this->ffastrans->submitJob($uncInputFile, $workflowId, $variables);
            return response()->json(['success' => true, 'job_id' => $response['job_id'] ?? 'unknown']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function checkStatus($jobId)
    {
        try {
            $apiData = $this->ffastrans->getJobStatus($jobId);
            $rawState = $apiData['state'] ?? ''; 
            $progress = $apiData['progress'] ?? 0;

            if ($progress == 0 && isset($apiData['variables']) && is_array($apiData['variables'])) {
                foreach ($apiData['variables'] as $var) {
                    if (in_array($var['name'], ['progress', 'i_progress', 's_progress'])) {
                        $progress = (int) $var['data'];
                    }
                    if (in_array($var['name'], ['status', 's_status'])) {
                        $rawState = $var['data'];
                    }
                }
            }
            
            $stateLower = strtolower($rawState);

            // FORCE FRENCH LABELS
            if (in_array($stateLower, ['success', 'finished', 'done', 'terminé'])) {
                $label = 'Terminé';
                $finished = true;
                $progress = 100; 
            } 
            elseif (in_array($stateLower, ['error', 'failed', 'aborted', 'echoué'])) {
                $label = 'Echoué';
                $finished = true;
            } 
            elseif (in_array($stateLower, ['cancelled', 'canceled', 'annulé'])) {
                $label = 'Annulé';
                $finished = true;
            }
            else {
                // If unknown or empty, assume running
                $label = 'En cours'; 
                $finished = false;
            }

            return response()->json([
                'progress' => $progress,
                'label' => $label,
                'finished' => $finished,
                'debug_state' => $rawState
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur de connexion'], 500);
        }
    }

    public function cancel($jobId)
    {
        $success = $this->ffastrans->cancelJob($jobId);
        if ($success) {
            return back()->with('success', 'La demande d\'annulation a été envoyée.');
        } else {
            return back()->with('error', 'Impossible d\'annuler le job.');
        }
    }
}