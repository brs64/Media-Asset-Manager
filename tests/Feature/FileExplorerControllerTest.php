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

        $this->actingAs(User::factory()->create());

    }
    /**
     * @test
     * GIVEN : un utilisateur authentifié
     * WHEN : on lance un scan avec un nom de disque invalide
     * THEN : la réponse retourne un statut 403
     */
    public function scan_aborts_for_invalid_disk()
    {
        $response = $this->get('/explorer/scan?disk=invalid_disk');

        $response->assertStatus(403);
    }

    /**
     * @test
     * GIVEN : un utilisateur authentifié avec les queues simulées
     * WHEN : on démarre un scan sur le disque ftp_pad
     * THEN : un job ScanDiskJob est dispatché et un scan_id est retourné
     */
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

    /**
     * @test
     * GIVEN : un scan en cours avec un statut et un compteur stockés en cache
     * WHEN : on consulte le statut de ce scan
     * THEN : la réponse retourne le statut 'running' et le compteur 5
     */
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

    /**
     * @test
     * GIVEN : un scan toujours en cours sans résultats finaux
     * WHEN : on demande les résultats de ce scan
     * THEN : la réponse retourne un statut 'running' avec des résultats vides
     */
    public function scan_results_returns_empty_if_not_done()
    {
        $scanId = (string) Str::uuid();
        Cache::put("scan:{$scanId}:status", 'running');

        $this->mock(FfastransService::class);

        $response = $this->getJson(route('admin.scan.results', $scanId));

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'running',
                'count'  => 0,
                'results'=> []
            ]);
    }

    /**
     * @test
     * GIVEN : un scan terminé avec deux vidéos, dont une déjà importée et une en cours de transfert
     * WHEN : on demande les résultats de ce scan
     * THEN : seule la vidéo non importée est retournée avec ses informations de job
     */
    public function scan_results_returns_processed_data()
    {
        $scanId = (string) Str::uuid();

        $files = [
            ['type' => 'video', 'name' => 'Vid1.mp4', 'path' => '/Vid1.mp4'],
            ['type' => 'video', 'name' => 'Vid2.mp4', 'path' => '/Vid2.mp4']
        ];

        Media::factory()->create(['chemin_local' => '/Vid1.mp4']);

        Cache::put("scan:{$scanId}:status", 'done');
        Cache::put("scan:{$scanId}:results", $files);

        $this->mock(FfastransService::class, function ($mock) {
            $mock->shouldReceive('getFullStatusList')
                ->once()
                ->andReturn([
                    ['filename' => 'Vid2.mp4', 'id' => 'job1', 'status' => 'running', 'progress' => 50, 'is_finished' => false]
                ]);
        });

        $response = $this->getJson(route('admin.scan.results', $scanId));

        $response->assertStatus(200);

        $json = $response->json();

        $this->assertEquals('done', $json['status']);
        $this->assertEquals(1, $json['count']);
        $this->assertEquals('Vid2.mp4', $json['results'][0]['filename']);
        $this->assertEquals('job1', $json['results'][0]['job_id']);
        $this->assertEquals('running', $json['results'][0]['status']);
    }
}