<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FfastransService;
use App\Services\FileExplorerService;
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
        $maxConcurrent = env('NB_MAX_PROCESSUS_TRANSFERT');

        return view('admin.transferts', compact('maxConcurrent'));
    }
    
    public function list(FfastransService $ffastrans){
        $activeMap = [];
        try {
            $activeJobs = $ffastrans->getFullStatusList();
            foreach ($activeJobs as $job) {
                $key = pathinfo($job['filename'], PATHINFO_FILENAME);
                $activeMap[$key] = $job;
            }
        } catch (\Exception $e) {
            $activeMap = [];
        }

        $padRoot = rtrim(env('URI_RACINE_NAS_PAD'), '/');
        $archRoot = rtrim(env('URI_RACINE_NAS_ARCH'), '/');

        $candidates = Media::whereNull('URI_NAS_MPEG')
            ->where(function ($query) use ($padRoot, $archRoot) {
                $query->where('URI_NAS_PAD', 'LIKE', "{$padRoot}%")
                      ->orWhere('URI_NAS_ARCH', 'LIKE', "{$archRoot}%");
            })
            ->get();

        $results = [];

        foreach ($candidates as $media) {
            $assetName = $media->asset_name;

            if (!empty($media->URI_NAS_ARCH)) {
                $finalPath = $media->URI_NAS_ARCH;
                $finalDisk = 'nas_arch';
                $displayExt = '.mp4';
                $sourceLabel = 'NAS_ARCH';
            } else {
                $finalPath = $media->URI_NAS_PAD;
                $finalDisk = 'ftp_pad';
                $displayExt = '.mxf';
                $sourceLabel = 'NAS_PAD';
            }

            if (isset($activeMap[$assetName])) {
                $job = $activeMap[$assetName];
                $results[] = [
                    'filename' => $assetName . $displayExt,
                    'path'     => $finalPath,
                    'disk'     => $finalDisk,
                    'source'   => $sourceLabel,
                    'job_id'   => $job['id'],
                    'status'   => $job['status'],
                    'progress' => $job['progress'],
                    'finished' => $job['is_finished']
                ];
            } else {
                $results[] = [
                    'filename' => $assetName . $displayExt,
                    'path'     => $finalPath,
                    'disk'     => $finalDisk,
                    'source'   => $sourceLabel,
                    'job_id'   => null,
                    'status'   => 'En attente',
                    'progress' => 0,
                    'finished' => false
                ];
            }
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
        $workflowId = env('WORKFLOW_ID'); 

        $directoryPath = dirname($filePath);
        $windowsDirectory = str_replace('/', '\\', $directoryPath);

        $variables = [
            [
                'name' => 'project_path', 
                'data' => $windowsDirectory
            ]
        ];

        try {
            $response = $this->ffastrans->submitJob($filePath, $workflowId, $variables);
            
            return response()->json([
                'success' => true,
                'job_id' => $response['job_id'] ?? 'unknown',
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function flattenTree(array $tree): array
    {
        $flat = [];

        foreach ($tree as $node) {
            $flat[] = $node;

            if (
                isset($node['children']) &&
                is_array($node['children']) &&
                count($node['children']) > 0
            ) {
                $flat = array_merge($flat, $this->flattenTree($node['children']));
            }
        }

        return $flat;
    }

    /**
     * AJAX Endpoint: Check status of a single job
     */
    public function checkStatus($jobId)
    {
        try {
            $apiData = $this->ffastrans->getJobStatus($jobId);
            
            // Map API state to UI labels
            $rawState = $apiData['state'] ?? 'Unknown'; 
            $progress = $apiData['progress'] ?? 0;

            if (in_array($rawState, ['Success', 'Finished', 'Done'])) {
                $label = 'Terminé';
                $finished = true;
                $progress = 100;
            } elseif (in_array($rawState, ['Error', 'Failed', 'Cancelled'])) {
                $label = 'Echoué';
                $finished = true;
            } else {
                $label = 'En cours';
                $finished = false;
            }

            return response()->json([
                'progress' => $progress,
                'label' => $label,
                'finished' => $finished
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur de connexion'], 500);
        }
    }

    /**
     * Action: Cancel a Job
     */
    public function cancel($jobId)
    {
        $success = $this->ffastrans->cancelJob($jobId);

        if ($success) {
            return back()->with('success', 'La demande d\'annulation a été envoyée.');
        } else {
            return back()->with('error', 'Impossible d\'annuler le job (il est peut-être déjà fini).');
        }
    }
}