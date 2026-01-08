<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class FfastransService
{
    protected string $baseUrl;
    protected ?string $username;
    protected ?string $password;

    public function __construct()
    {
        $this->baseUrl = config('services.ffastrans.url');
        $this->username = config('services.ffastrans.user');
        $this->password = config('services.ffastrans.password');
    }

    /**
     * Helper to get the HTTP client with optional authentication
     */
    protected function client()
    {
        $client = Http::timeout(10)->acceptJson();

        if ($this->username && $this->password) {
            $client->withBasicAuth($this->username, $this->password);
        }

        return $client;
    }

    /**
     * Submit a file to a specific workflow.
     *
     * @param string $sourceFile The full path to the source file (must be accessible by FFAStrans)
     * @param string $workflowId The ID or Name of the FFAStrans workflow
     * @param array $variables Optional user variables to pass to the workflow
     * @return array The response containing the Job ID
     * @throws Exception
     */
    public function submitJob(string $sourceFile, string $workflowId, array $variables = [])
    {
        // GET CONFIG
        $localRoot  = config('services.ffastrans.path_local'); 
        $remoteRoot = config('services.ffastrans.path_remote');

        // 2. TRANSLATE PATH
        $finalPath = $sourceFile;

        if ($localRoot && $remoteRoot) {
            // Check if the source file starts with the local root
            if (str_starts_with($sourceFile, $localRoot)) {
                // Remove the local root from the start
                $relativePath = substr($sourceFile, strlen($localRoot));
                
                // Ensure remote root doesn't end with slash and relative doesn't start with slash
                $remoteRoot = rtrim($remoteRoot, '\\/');
                $relativePath = ltrim($relativePath, '/\\');
                
                // Combine them
                $finalPath = $remoteRoot . DIRECTORY_SEPARATOR . $relativePath;
            }
        }

        // FORCE WINDOWS BACKSLASHES (Critical for FFAStrans)
        // This turns "/path/to/file" into "\path\to\file"
        $finalPath = str_replace('/', '\\', $finalPath);

        // SUBMIT
        $endpoint = "{$this->baseUrl}/api/json/v2/jobs";
        
        $payload = [
            'workflow' => $workflowId,
            'input' => $finalPath,
            'variables' => $variables
        ];

        try {
            $response = $this->client()->post($endpoint, $payload);
            
            if ($response->failed()) {
                 Log::error('FFAStrans Submit Error', ['body' => $response->body()]);
                 throw new Exception("Failed to submit job: " . $response->status());
            }
            return $response->json();
            
        } catch (Exception $e) {
            Log::error('FFAStrans Exception: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getFullStatusList()
    {
        // Get Active Jobs
        try {
            $activeResponse = $this->client()->get("{$this->baseUrl}/api/json/v2/jobs");
            $activeJobs = $activeResponse->successful() ? ($activeResponse->json()['jobs'] ?? []) : [];
        } catch (Exception $e) {
            $activeJobs = [];
        }

        // Get History (Last 50)
        try {
            $historyResponse = $this->client()->get("{$this->baseUrl}/api/json/v2/history?start=0&count=50");
            $historyJobs = $historyResponse->successful() ? ($historyResponse->json()['history'] ?? []) : [];
        } catch (Exception $e) {
            $historyJobs = [];
        }

        $allJobs = [];

        // Map Active
        foreach ($activeJobs as $job) {
            $allJobs[] = [
                'id' => $job['job_id'] ?? $job['guid'],
                'filename' => basename($job['input'] ?? 'Fichier Inconnu'),
                'status' => $job['state'] ?? 'En cours',
                'progress' => $job['progress'] ?? 0,
                'date' => $job['submit_time'] ?? date('Y-m-d H:i:s'),
                'is_finished' => false
            ];
        }

        // Map History
        foreach ($historyJobs as $job) {
            $allJobs[] = [
                'id' => $job['job_id'] ?? $job['guid'],
                'filename' => basename($job['source'] ?? 'Fichier Inconnu'),
                'status' => $job['result'] ?? 'Terminé',
                'progress' => 100,
                'date' => $job['end_time'] ?? date('Y-m-d H:i:s'),
                'is_finished' => true
            ];
        }

        // Sort by Date DESC
        usort($allJobs, function ($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return $allJobs;
    }

    /**
     * Get the status of a specific job.
     *
     * @param string $jobId
     * @return array
     */
    public function getJobStatus(string $jobId)
    {
        $endpoint = "{$this->baseUrl}/api/json/v2/jobs/{$jobId}";

        $response = $this->client()->get($endpoint);

        if ($response->failed()) {
            return ['state' => 'Error', 'msg' => 'Could not retrieve status'];
        }

        return $response->json();
    }

    /**
     * Retrieve list of all available workflows.
     * Useful for populating a dropdown in admin panel.
     *
     * @return array
     */
    public function getWorkflows()
    {
        $endpoint = "{$this->baseUrl}/api/json/v2/workflows";

        $response = $this->client()->get($endpoint);

        if ($response->successful()) {
            return $response->json()['workflows'] ?? [];
        }

        return [];
    }
    
    /**
     * Retrieve the history/logs of a job (Ticket).
     * * @param string $jobId
     * @return array
     */
    public function getJobHistory(string $jobId) 
    {
        $endpoint = "{$this->baseUrl}/api/json/v2/history/{$jobId}";
        $response = $this->client()->get($endpoint);
        
        return $response->json();
    }

    /**
     * Request FFAStrans to cancel/abort a specific job.
     *
     * @param string $jobId
     * @return bool True if successful
     */
    public function cancelJob(string $jobId)
    {
        $endpoint = "{$this->baseUrl}/api/json/v2/jobs/{$jobId}";

        try {
            $response = $this->client()->delete($endpoint);
            
            // 200 OK or 204 No Content means success
            return $response->successful();

        } catch (Exception $e) {
            Log::error("Failed to cancel FFAStrans job {$jobId}: " . $e->getMessage());
            return false;
        }
    }
}