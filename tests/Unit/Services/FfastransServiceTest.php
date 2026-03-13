<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\FfastransService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class FfastransServiceTest extends TestCase
{
    protected FfastransService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.ffastrans.url', 'http://ffastrans.local');
        Config::set('services.ffastrans.user', 'testuser');
        Config::set('services.ffastrans.password', 'testpass');
        Config::set('services.ffastrans.path_local', '/var/www/storage/app/videos');
        Config::set('services.ffastrans.path_remote', '\\\\server\\share\\videos');

        $this->service = new FfastransService();
    }

    /** @test */
    public function translatePath_converts_linux_to_windows_path()
    {
        $linuxPath = '/var/www/storage/app/videos/2024/MonVideo.mp4';

        $result = $this->service->translatePath($linuxPath);

        $this->assertEquals('\\\\server\\share\\videos\\2024\\MonVideo.mp4', $result);
    }

    /** @test */
    public function translatePath_handles_relative_paths()
    {
        Config::set('services.ffastrans.path_local', null);
        Config::set('services.ffastrans.path_remote', '\\\\server\\share');
        $this->service = new FfastransService();

        $result = $this->service->translatePath('videos/test.mp4');

        $this->assertEquals('\\\\server\\share\\videos\\test.mp4', $result);
    }

    /** @test */
    public function translatePath_returns_original_if_no_remote_root()
    {
        Config::set('services.ffastrans.path_remote', null);
        $this->service = new FfastransService();

        $result = $this->service->translatePath('/some/path/video.mp4');

        $this->assertEquals('\\some\\path\\video.mp4', $result);
    }

    /** @test */
    public function translatePath_preserves_windows_paths()
    {
        $windowsPath = 'C:\\Videos\\test.mp4';

        $result = $this->service->translatePath($windowsPath);

        // Should convert forward slashes if any but preserve structure
        $this->assertStringContainsString('C:\\Videos\\test.mp4', $result);
    }

    /** @test */
    public function translatePath_replaces_forward_slashes_with_backslashes()
    {
        $result = $this->service->translatePath('/var/www/storage/app/videos/test.mp4');

        $this->assertStringNotContainsString('/', $result);
        $this->assertStringContainsString('\\', $result);
    }

    /** @test */
    public function submitJob_sends_correct_payload_to_api()
    {
        Http::fake([
            'ffastrans.local/api/json/v2/jobs' => Http::response([
                'job_id' => 'job-123',
                'status' => 'submitted',
            ], 200),
        ]);

        $result = $this->service->submitJob(
            '/var/www/storage/app/videos/test.mp4',
            'workflow-456',
            ['custom_var' => 'value']
        );

        $this->assertArrayHasKey('job_id', $result);
        $this->assertEquals('job-123', $result['job_id']);

        Http::assertSent(function ($request) {
            return $request->url() === 'http://ffastrans.local/api/json/v2/jobs' &&
                   $request['wf_id'] === 'workflow-456' &&
                   $request['priority'] === 5 &&
                   $request['variables']['custom_var'] === 'value';
        });
    }

    /** @test */
    public function submitJob_preserves_unc_paths()
    {
        Http::fake([
            'ffastrans.local/api/json/v2/jobs' => Http::response(['job_id' => 'job-123'], 200),
        ]);

        $uncPath = '\\\\server\\share\\video.mp4';
        $this->service->submitJob($uncPath, 'workflow-456');

        Http::assertSent(function ($request) use ($uncPath) {
            return $request['inputfile'] === $uncPath;
        });
    }

    /** @test */
    public function submitJob_throws_exception_on_failed_response()
    {
        Http::fake([
            'ffastrans.local/api/json/v2/jobs' => Http::response(['error' => 'Bad request'], 400),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to submit job: 400');

        $this->service->submitJob('/path/to/video.mp4', 'workflow-456');
    }

    /** @test */
    public function getFullStatusList_combines_active_and_history_jobs()
    {
        Http::fake([
            'ffastrans.local/api/json/v2/jobs' => Http::response([
                'jobs' => [
                    [
                        'job_id' => 'job-1',
                        'input' => '/videos/active.mp4',
                        'progress' => 50,
                        'submit_time' => '2024-01-15 10:00:00',
                    ],
                ],
            ], 200),
            'ffastrans.local/api/json/v2/history*' => Http::response([
                'history' => [
                    [
                        'job_id' => 'job-2',
                        'source' => '/videos/finished.mp4',
                        'result' => 'Success',
                        'end_time' => '2024-01-15 09:00:00',
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getFullStatusList();

        $this->assertCount(2, $result);

        // Check active job
        $activeJob = collect($result)->firstWhere('id', 'job-1');
        $this->assertEquals('En cours', $activeJob['status']);
        $this->assertEquals(50, $activeJob['progress']);
        $this->assertEquals('active.mp4', $activeJob['filename']);

        // Check history job
        $historyJob = collect($result)->firstWhere('id', 'job-2');
        $this->assertEquals('Terminé', $historyJob['status']);
        $this->assertEquals(100, $historyJob['progress']);
        $this->assertEquals('finished.mp4', $historyJob['filename']);
    }

    /** @test */
    public function getFullStatusList_sorts_by_date_descending()
    {
        Http::fake([
            'ffastrans.local/api/json/v2/jobs' => Http::response(['jobs' => []], 200),
            'ffastrans.local/api/json/v2/history*' => Http::response([
                'history' => [
                    [
                        'job_id' => 'old-job',
                        'source' => '/videos/old.mp4',
                        'result' => 'Success',
                        'end_time' => '2024-01-10 10:00:00',
                    ],
                    [
                        'job_id' => 'new-job',
                        'source' => '/videos/new.mp4',
                        'result' => 'Success',
                        'end_time' => '2024-01-15 10:00:00',
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getFullStatusList();

        $this->assertEquals('new-job', $result[0]['id']);
        $this->assertEquals('old-job', $result[1]['id']);
    }

    /** @test */
    public function getFullStatusList_translates_statuses_to_french()
    {
        Http::fake([
            'ffastrans.local/api/json/v2/jobs' => Http::response(['jobs' => []], 200),
            'ffastrans.local/api/json/v2/history*' => Http::response([
                'history' => [
                    ['job_id' => 'job-1', 'source' => 'a.mp4', 'result' => 'Success', 'end_time' => '2024-01-15'],
                    ['job_id' => 'job-2', 'source' => 'b.mp4', 'result' => 'Error', 'end_time' => '2024-01-15'],
                    ['job_id' => 'job-3', 'source' => 'c.mp4', 'result' => 'Cancelled', 'end_time' => '2024-01-15'],
                    ['job_id' => 'job-4', 'source' => 'd.mp4', 'result' => 'Failed', 'end_time' => '2024-01-15'],
                    ['job_id' => 'job-5', 'source' => 'e.mp4', 'result' => '', 'end_time' => '2024-01-15'],
                ],
            ], 200),
        ]);

        $result = $this->service->getFullStatusList();

        $this->assertEquals('Terminé', collect($result)->firstWhere('id', 'job-1')['status']);
        $this->assertEquals('Echoué', collect($result)->firstWhere('id', 'job-2')['status']);
        $this->assertEquals('Annulé', collect($result)->firstWhere('id', 'job-3')['status']);
        $this->assertEquals('Echoué', collect($result)->firstWhere('id', 'job-4')['status']);
        $this->assertEquals('En cours', collect($result)->firstWhere('id', 'job-5')['status']);
    }

    /** @test */
    public function getFullStatusList_handles_api_errors_gracefully()
    {
        Http::fake([
            'ffastrans.local/api/json/v2/jobs' => Http::response([], 500),
            'ffastrans.local/api/json/v2/history*' => Http::response([], 500),
        ]);

        $result = $this->service->getFullStatusList();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /** @test */
    public function getJobStatus_returns_active_job_status()
    {
        Http::fake([
            'ffastrans.local/api/json/v2/jobs/job-123' => Http::response([
                'job_id' => 'job-123',
                'state' => 'Processing',
                'progress' => 75,
            ], 200),
        ]);

        $result = $this->service->getJobStatus('job-123');

        $this->assertArrayHasKey('state', $result);
        $this->assertEquals('Processing', $result['state']);
        $this->assertEquals(75, $result['progress']);
    }

    /** @test */
    public function getJobStatus_falls_back_to_history_if_not_active()
    {
        Http::fake([
            'ffastrans.local/api/json/v2/jobs/job-123' => Http::response([], 404),
            'ffastrans.local/api/json/v2/history/job-123' => Http::response([
                'job_id' => 'job-123',
                'result' => 'Success',
                'msg' => 'Job completed successfully',
            ], 200),
        ]);

        $result = $this->service->getJobStatus('job-123');

        $this->assertEquals('Success', $result['state']);
        $this->assertEquals(100, $result['progress']);
        $this->assertEquals('Job completed successfully', $result['message']);
    }

    /** @test */
    public function getJobStatus_returns_error_when_job_not_found()
    {
        Http::fake([
            'ffastrans.local/api/json/v2/jobs/job-999' => Http::response([], 404),
            'ffastrans.local/api/json/v2/history/job-999' => Http::response([], 404),
        ]);

        $result = $this->service->getJobStatus('job-999');

        $this->assertEquals('Error', $result['state']);
        $this->assertEquals('Job not found', $result['msg']);
    }

    /** @test */
    public function cancelJob_deletes_job_successfully()
    {
        Http::fake([
            'ffastrans.local/api/json/v2/jobs/job-123' => Http::response([], 200),
        ]);

        $result = $this->service->cancelJob('job-123');

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->method() === 'DELETE' &&
                   $request->url() === 'http://ffastrans.local/api/json/v2/jobs/job-123';
        });
    }

    /** @test */
    public function cancelJob_returns_false_on_failure()
    {
        Http::fake([
            'ffastrans.local/api/json/v2/jobs/job-123' => Http::response([], 500),
        ]);

        $result = $this->service->cancelJob('job-123');

        $this->assertFalse($result);
    }

    /** @test */
    public function cancelJob_handles_exceptions()
    {
        Http::fake(function () {
            throw new \Exception('Network error');
        });

        $result = $this->service->cancelJob('job-123');

        $this->assertFalse($result);
    }

    /** @test */
    public function client_uses_basic_auth_when_configured()
    {
        Http::fake([
            'ffastrans.local/api/json/v2/jobs' => Http::response(['jobs' => []], 200),
        ]);

        $this->service->getFullStatusList();

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization');
        });
    }

    /** @test */
    public function client_works_without_auth_when_not_configured()
    {
        Config::set('services.ffastrans.user', null);
        Config::set('services.ffastrans.password', null);
        $this->service = new FfastransService();

        Http::fake([
            'ffastrans.local/api/json/v2/jobs' => Http::response(['jobs' => []], 200),
        ]);

        $this->service->getFullStatusList();

        // Should still work
        Http::assertSent(function ($request) {
            return $request->url() === 'http://ffastrans.local/api/json/v2/jobs';
        });
    }
}
