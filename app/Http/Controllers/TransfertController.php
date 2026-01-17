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

    /**
     * Display the list of videos in transfer
     */
    public function list()
    {
        try {
            // 1. Get Active Jobs from FFAStrans
            $activeJobs = $this->ffastrans->getFullStatusList();
            
            $activeMap = [];
            foreach ($activeJobs as $job) {
                $activeMap[$job['filename']] = $job;
            }

            // 2. Get Target Folder from .env
            $targetFolder = trim(env('URI_RACINE_NAS_PAD'), '/'); 
            
            // 3. Scan the Disk (Reusing your Service)
            $rawTree = FileExplorerService::scanDisk('ftp_pad', $targetFolder);
            $flatFiles = $this->flattenTree($rawTree);

            // 4. Build the Unified List
            $unifiedList = [];
            $dbColumn = 'URI_NAS_MPEG'; // Need to change it to URI_LOCAL possibly

            foreach ($flatFiles as $file) {
                if ($file['type'] !== 'video') continue;

                $filename = $file['name'];
                $path = $file['path'];

                // A. Check if this file is already in the Database (Archived/Done previously)
                if (Media::where($dbColumn, $path)->exists()) {
                    continue; 
                }

                // B. Check if it is currently processing (Active Job)
                if (isset($activeMap[$filename])) {
                    // IT IS RUNNING: Use the data from FFAStrans
                    $job = $activeMap[$filename];
                    $unifiedList[] = [
                        'filename' => $filename,
                        'path'     => $path,
                        'disk'     => 'ftp_pad',
                        'job_id'   => $job['id'],        // Exists -> Shows Progress Bar
                        'status'   => $job['status'],
                        'progress' => $job['progress'],
                        'finished' => $job['is_finished']
                    ];
                } else {
                    // IT IS PENDING: Use default data
                    $unifiedList[] = [
                        'filename' => $filename,
                        'path'     => $path,
                        'disk'     => 'ftp_pad',
                        'job_id'   => null,              // Null -> Shows Start Button
                        'status'   => 'En attente',
                        'progress' => 0,
                        'finished' => false
                    ];
                }
            }

            return response()->json($unifiedList);

        } catch (\Exception $e) {
            Log::error("List Error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
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