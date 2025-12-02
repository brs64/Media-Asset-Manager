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
        $endpoint = "{$this->baseUrl}/api/json/v2/jobs";

        // FFAStrans API payload structure
        $payload = [
            'workflow' => $workflowId,
            'input' => $sourceFile,
            'variables' => $variables
        ];

        try {
            $response = $this->client()->post($endpoint, $payload);

            if ($response->failed()) {
                Log::error('FFAStrans Submit Error', ['body' => $response->body()]);
                throw new Exception("Failed to submit job to FFAStrans: " . $response->status());
            }

            return $response->json();

        } catch (Exception $e) {
            Log::error('FFAStrans Connection Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get the status of a specific job.
     *
     * @param string $jobId
     * @return array
     */
    public function getJobStatus(string $jobId)
    {
        // Note: Endpoint format may vary slightly depending on FFAStrans API version
        $endpoint = "{$this->baseUrl}/api/json/v2/jobs/{$jobId}";

        $response = $this->client()->get($endpoint);

        if ($response->failed()) {
            return ['state' => 'Error', 'msg' => 'Could not retrieve status'];
        }

        return $response->json();
    }

    /**
     * Retrieve list of all available workflows.
     * Useful for populating a dropdown in your admin panel.
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
}