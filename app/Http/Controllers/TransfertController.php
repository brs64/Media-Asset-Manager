<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FfastransService;

class TransfertController extends Controller
{
    protected $ffastrans;

    public function __construct(FfastransService $ffastrans)
    {
        $this->ffastrans = $ffastrans;
    }

    public function index()
    {
        return view('admin.transferts');
    }

    /**
     * Display the list of videos in transfer
     */
    public function list()
{
        try {
            $transfers = $this->ffastrans->getFullStatusList();
            return response()->json($transfers);
        } catch (\Exception $e) {
            return response()->json([], 500);
        }
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