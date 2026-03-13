<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

use App\Models\User;
use App\Models\Media;
use App\Jobs\ScanDiskJob;
use App\Services\FileExplorerService;
use App\Services\FfastransService;

class FileExplorerControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Simule un utilisateur connecté
        $this->actingAs(User::factory()->create());
    }

    /** @test */
    public function index_displays_items()
    {
        $this->mock(FileExplorerService::class)
            ->shouldReceive('scan')
            ->once()
            ->andReturn([
                ['type' => 'folder', 'name' => 'Folder1', 'path' => '/Folder1'],
                ['type' => 'video', 'name' => 'Vid1.mp4', 'path' => '/Vid1.mp4']
            ]);

        $response = $this->get(route('explorer.index'));

        $response->assertStatus(200);
        $response->assertViewIs('explorer.index');
        $response->assertViewHas('items');
    }

    /** @test */
    /** @test */
    /** @test */
    /** @test */
    public function scan_returns_tree_items_with_media_linked()
    {
        $videoPath = '/Vid1.mp4';

        // Crée le media correspondant dans la base
        $media = \App\Models\Media::factory()->create(['chemin_local' => $videoPath]);

        // Mock du service FileExplorerService
        $this->mock(\App\Services\FileExplorerService::class)
            ->shouldReceive('scanDisk')
            ->once()
            ->andReturn([
                ['type' => 'video', 'name' => 'Vid1.mp4', 'path' => $videoPath],
                ['type' => 'folder', 'name' => 'Folder1', 'path' => '/Folder1']
            ]);

        // Appel de la route scan
        $response = $this->get(route('explorer.scan', [
            'disk' => 'external_local',
            'path' => '/'
        ]));

        // Vérifie le statut HTTP
        $response->assertStatus(200);

        $content = $response->getContent();

        // Vérifie que le rendu contient le fichier vidéo et le dossier
        $this->assertStringContainsString('Vid1.mp4', $content);
        $this->assertStringContainsString('Folder1', $content);

        // Vérifie que le Media est bien en base
        $this->assertDatabaseHas('medias', [
            'chemin_local' => $videoPath
        ]);
    }

    /** @test */
    public function scan_aborts_for_invalid_disk()
    {
        $response = $this->get(route('explorer.scan', ['disk' => 'invalid_disk']));

        $response->assertStatus(403);
    }

    /** @test */
    public function start_scan_dispatches_job_and_returns_scan_id()
    {
        Queue::fake();
        Cache::flush();

        $response = $this->postJson(route('admin.scan.start', ['disk' => 'ftp_pad', 'path' => '/myFolder']));

        $response->assertStatus(200)
            ->assertJsonStructure(['scan_id', 'status']);

        $data = $response->json();
        $this->assertNotEmpty($data['scan_id']);

        Queue::assertPushed(ScanDiskJob::class, function ($job) use ($data) {
            return $job->scanId === $data['scan_id'];
        });
    }

    /** @test */
    public function scan_status_returns_correct_values()
    {
        $scanId = (string) Str::uuid();
        Cache::put("scan:{$scanId}:status", 'running');
        Cache::put("scan:{$scanId}:count", 5);

        $response = $this->getJson(route('admin.scan.status', $scanId));

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'running',
                'count' => 5
            ]);
    }

    /** @test */
    public function scan_results_returns_empty_if_not_done()
    {
        $scanId = (string) Str::uuid();
        Cache::put("scan:{$scanId}:status", 'running');

        $ffastrans = $this->mock(FfastransService::class);

        $response = $this->getJson(route('admin.scan.results', $scanId));

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'running',
                'count'  => 0,
                'results'=> []
            ]);
    }

    /** @test */
    /** @test */
    public function scan_results_returns_processed_data()
    {
        $scanId = (string) \Illuminate\Support\Str::uuid();

        $files = [
            ['type' => 'video', 'name' => 'Vid1.mp4', 'path' => '/Vid1.mp4'],
            ['type' => 'video', 'name' => 'Vid2.mp4', 'path' => '/Vid2.mp4']
        ];

        // Vid1 est déjà archivé
        $media = \App\Models\Media::factory()->create(['chemin_local' => '/Vid1.mp4']);

        Cache::put("scan:{$scanId}:status", 'done');
        Cache::put("scan:{$scanId}:results", $files);

        $ffastrans = $this->mock(\App\Services\FfastransService::class)
            ->shouldReceive('getFullStatusList')
            ->once()
            ->andReturn([
                ['filename' => 'Vid2.mp4', 'id' => 'job1', 'status' => 'running', 'progress' => 50, 'is_finished' => false]
            ])
            ->getMock();

        $response = $this->getJson(route('admin.scan.results', $scanId));

        $response->assertStatus(200);

        $json = $response->json();

        // Vérifie que le status est correct
        $this->assertEquals('done', $json['status']);

        // Vérifie qu'il ne reste que les fichiers non archivés
        $this->assertEquals(1, $json['count']);

        // Vérifie le contenu exact du fichier restant
        $this->assertEquals('Vid2.mp4', $json['results'][0]['filename']);
        $this->assertEquals('job1', $json['results'][0]['job_id']);
        $this->assertEquals('running', $json['results'][0]['status']);
    }
}