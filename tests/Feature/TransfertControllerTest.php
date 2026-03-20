<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\FfastransService;
use App\Models\Media;
use App\Models\User;

class TransfertControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        config()->set('btsplay.process.workflow_id', 'test-workflow');
    }

    /** @test */
    public function index_displays_transfert_page()
    {
        $response = $this->actingAs($this->user)->get(route('admin.transferts'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.transferts');
        $response->assertViewHas('maxConcurrent');
    }

    /** @test */
    public function list_returns_media_without_local_paths()
    {
        // Create media with NAS paths but no local path
        $mediaWithArch = Media::factory()->create([
            'URI_NAS_ARCH' => '/nas/arch/video1.mp4',
            'URI_NAS_PAD' => null,
            'chemin_local' => null,
        ]);

        $mediaWithPad = Media::factory()->create([
            'URI_NAS_ARCH' => null,
            'URI_NAS_PAD' => '/nas/pad/video2.mxf',
            'chemin_local' => null,
        ]);

        // Create media with local path (should be excluded)
        Media::factory()->create([
            'URI_NAS_ARCH' => '/nas/arch/video3.mp4',
            'chemin_local' => '/local/video3.mp4',
        ]);

        $this->mock(FfastransService::class)
            ->shouldReceive('getFullStatusList')
            ->once()
            ->andReturn([]);

        $response = $this->actingAs($this->user)->get(route('admin.transferts.list'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'count',
            'results' => [
                '*' => ['id', 'filename', 'path', 'disk', 'source', 'job_id', 'status', 'progress', 'finished']
            ]
        ]);

        $json = $response->json();
        $this->assertEquals(2, $json['count']);
    }

    /** @test */
    public function list_matches_active_jobs_with_media()
    {
        $media = Media::factory()->create([
            'mtd_tech_titre' => 'TestVideo.mp4',
            'URI_NAS_ARCH' => '/nas/arch/TestVideo.mp4',
            'chemin_local' => null,
        ]);

        $this->mock(FfastransService::class)
            ->shouldReceive('getFullStatusList')
            ->once()
            ->andReturn([
                [
                    'id' => 'job-123',
                    'filename' => 'TestVideo.mp4',
                    'status' => 'En cours',
                    'progress' => 50,
                    'is_finished' => false,
                    'date' => '2024-01-15 10:00:00',
                ]
            ]);

        $response = $this->actingAs($this->user)->get(route('admin.transferts.list'));

        $json = $response->json();
        $result = collect($json['results'])->firstWhere('id', $media->id);

        $this->assertEquals('job-123', $result['job_id']);
        $this->assertEquals('En cours', $result['status']);
        $this->assertEquals(50, $result['progress']);
        $this->assertFalse($result['finished']);
    }

    /** @test */
    public function list_handles_ffastrans_error_gracefully()
    {
        Media::factory()->create([
            'URI_NAS_ARCH' => '/nas/arch/video.mp4',
            'chemin_local' => null,
        ]);

        $this->mock(FfastransService::class)
            ->shouldReceive('getFullStatusList')
            ->once()
            ->andThrow(new \Exception('FFAStrans connection error'));

        $response = $this->actingAs($this->user)->get(route('admin.transferts.list'));

        $response->assertStatus(200);
        $json = $response->json();

        // Should still return results but without active job info
        $this->assertArrayHasKey('results', $json);
        $this->assertEquals('En attente', $json['results'][0]['status']);
    }

    /** @test */
    public function list_prioritizes_arch_over_pad()
    {
        $media = Media::factory()->create([
            'URI_NAS_ARCH' => '/nas/arch/video.mp4',
            'URI_NAS_PAD' => '/nas/pad/video.mxf',
            'chemin_local' => null,
        ]);

        $this->mock(FfastransService::class)
            ->shouldReceive('getFullStatusList')
            ->andReturn([]);

        $response = $this->actingAs($this->user)->get(route('admin.transferts.list'));

        $json = $response->json();
        $result = $json['results'][0];

        $this->assertEquals('nas_arch', $result['disk']);
        $this->assertEquals('NAS_ARCH', $result['source']);
        $this->assertStringEndsWith('.mp4', $result['filename']);
    }

    /** @test */
    public function list_uses_pad_when_arch_not_available()
    {
        $media = Media::factory()->create([
            'URI_NAS_ARCH' => null,
            'URI_NAS_PAD' => '/nas/pad/video.mxf',
            'chemin_local' => null,
        ]);

        $this->mock(FfastransService::class)
            ->shouldReceive('getFullStatusList')
            ->andReturn([]);

        $response = $this->actingAs($this->user)->get(route('admin.transferts.list'));

        $json = $response->json();
        $result = $json['results'][0];

        $this->assertEquals('ftp_pad', $result['disk']);
        $this->assertEquals('NAS_PAD', $result['source']);
        $this->assertStringEndsWith('.mxf', $result['filename']);
    }

    /** @test */
    public function startJob_submits_job_to_ffastrans()
    {
        $this->mock(FfastransService::class)
            ->shouldReceive('submitJob')
            ->once()
            ->andReturn(['job_id' => 'job-456']);

        $response = $this->actingAs($this->user)->postJson(route('admin.transferts.start'), [
            'path' => '/nas/arch/video.mp4',
            'disk' => 'nas_arch',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'job_id' => 'job-456',
        ]);
    }

    /** @test */
    public function startJob_handles_ffastrans_error()
    {
        $this->mock(FfastransService::class)
            ->shouldReceive('submitJob')
            ->once()
            ->andThrow(new \Exception('Failed to submit job'));

        $response = $this->actingAs($this->user)->postJson(route('admin.transferts.start'), [
            'path' => '/nas/arch/video.mp4',
            'disk' => 'nas_arch',
        ]);

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'message' => 'Failed to submit job',
        ]);
    }

    /** @test */
    public function checkStatus_returns_job_progress()
    {
        $this->mock(FfastransService::class)
            ->shouldReceive('getJobStatus')
            ->once()
            ->with('job-123')
            ->andReturn([
                'state' => 'processing',
                'progress' => 75,
            ]);

        $response = $this->actingAs($this->user)->get(route('admin.transfers.status', 'job-123'));

        $response->assertStatus(200);
        $response->assertJson([
            'progress' => 75,
            'label' => 'En cours',
            'finished' => false,
        ]);
    }

    /** @test */
    public function checkStatus_detects_success_state()
    {
        $this->mock(FfastransService::class)
            ->shouldReceive('getJobStatus')
            ->once()
            ->andReturn([
                'state' => 'Success',
                'progress' => 100,
            ]);

        $response = $this->actingAs($this->user)->get(route('admin.transfers.status', 'job-123'));

        $response->assertJson([
            'progress' => 100,
            'label' => 'Terminé',
            'finished' => true,
        ]);
    }

    /** @test */
    public function checkStatus_detects_error_state()
    {
        $this->mock(FfastransService::class)
            ->shouldReceive('getJobStatus')
            ->once()
            ->andReturn([
                'state' => 'Error',
                'progress' => 50,
            ]);

        $response = $this->actingAs($this->user)->get(route('admin.transfers.status', 'job-123'));

        $response->assertJson([
            'label' => 'Echoué',
            'finished' => true,
        ]);
    }

    /** @test */
    public function checkStatus_detects_cancelled_state()
    {
        $this->mock(FfastransService::class)
            ->shouldReceive('getJobStatus')
            ->once()
            ->andReturn([
                'state' => 'Cancelled',
                'progress' => 25,
            ]);

        $response = $this->actingAs($this->user)->get(route('admin.transfers.status', 'job-123'));

        $response->assertJson([
            'label' => 'Annulé',
            'finished' => true,
        ]);
    }

    /** @test */
    public function checkStatus_extracts_progress_from_variables()
    {
        $this->mock(FfastransService::class)
            ->shouldReceive('getJobStatus')
            ->once()
            ->andReturn([
                'state' => 'processing',
                'progress' => 0,
                'variables' => [
                    ['name' => 'progress', 'data' => 80],
                    ['name' => 'status', 'data' => 'encoding'],
                ],
            ]);

        $response = $this->actingAs($this->user)->get(route('admin.transfers.status', 'job-123'));

        $response->assertJson([
            'progress' => 80,
        ]);
    }

    /** @test */
    public function checkStatus_handles_connection_error()
    {
        $this->mock(FfastransService::class)
            ->shouldReceive('getJobStatus')
            ->once()
            ->andThrow(new \Exception('Connection failed'));

        $response = $this->actingAs($this->user)->get(route('admin.transfers.status', 'job-123'));

        $response->assertStatus(500);
        $response->assertJson([
            'error' => 'Erreur de connexion',
        ]);
    }

    /** @test */
    public function cancel_cancels_job_successfully()
    {
        $this->mock(FfastransService::class)
            ->shouldReceive('cancelJob')
            ->once()
            ->with('job-123')
            ->andReturn(true);

        $response = $this->actingAs($this->user)->post(route('admin.transfers.cancel', 'job-123'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    /** @test */
    public function cancel_handles_failure()
    {
        $this->mock(FfastransService::class)
            ->shouldReceive('cancelJob')
            ->once()
            ->with('job-123')
            ->andReturn(false);

        $response = $this->actingAs($this->user)->post(route('admin.transfers.cancel', 'job-123'));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }
}
