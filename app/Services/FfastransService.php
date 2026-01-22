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
     * Handles both Absolute paths (starting with /) and Relative paths.
     */
    public function submitJob(string $sourceFile, string $workflowId, array $variables = [])
    {
        if (str_starts_with($sourceFile, '\\\\')) {
            $finalPath = $sourceFile;
        } 
        else {
            $finalPath = $this->translatePath($sourceFile);
        };

        $endpoint = "{$this->baseUrl}/api/json/v2/jobs";
        
        $payload = [
            'wf_id' => $workflowId,
            'inputfile' => $finalPath,
            'priority' => 5,
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

    public function translatePath(string $linuxPath): string
    {
        $localRoot  = config('services.ffastrans.path_local');
        $remoteRoot = config('services.ffastrans.path_remote');
        
        $finalPath = $linuxPath;

        if ($remoteRoot) {
            $remoteRoot = rtrim($remoteRoot, '\\/');

            // CASE A: Input is an Absolute Path matching Local Root
            if ($localRoot && str_starts_with($linuxPath, $localRoot)) {
                $relativePath = substr($linuxPath, strlen($localRoot));
                $relativePath = ltrim($relativePath, '/\\');
                $finalPath = $remoteRoot . DIRECTORY_SEPARATOR . $relativePath;
            }
            // CASE B: Input is a Relative Path (No starting slash, no Drive letter)
            elseif (!str_starts_with($linuxPath, '/') && !preg_match('/^[a-zA-Z]:/', $linuxPath)) {
                $finalPath = $remoteRoot . DIRECTORY_SEPARATOR . $linuxPath;
            }
        }

        return str_replace('/', '\\', $finalPath);
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
     * Checks Active jobs first, then falls back to History if not found.
     */
    public function getJobStatus(string $jobId)
    {
        $endpoint = "{$this->baseUrl}/api/json/v2/jobs/{$jobId}";
        $response = $this->client()->get($endpoint);

        if ($response->successful()) {
            $data = $response->json();
            return $data;
        }

        if ($response->status() === 404) {
            $historyEndpoint = "{$this->baseUrl}/api/json/v2/history/{$jobId}";
            $historyResponse = $this->client()->get($historyEndpoint);

            if ($historyResponse->successful()) {
                $historyData = $historyResponse->json();
                
                return [
                    'state'    => $historyData['result'] ?? 'Success', 
                    'progress' => 100, 
                    'message'  => $historyData['msg'] ?? ''
                ];
            }
        }

        return ['state' => 'Error', 'msg' => 'Job not found in Active or History'];
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