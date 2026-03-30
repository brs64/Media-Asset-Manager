<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ScanDiskJob;
use App\Services\FileExplorerService;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Mockery;

class ScanDiskJobTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * GIVEN : un disque contenant 12 fichiers vidéo
     * WHEN : le job de scan s'exécute
     * THEN : le cache contient le statut 'done', 12 résultats, et le compteur à 12
     */
    #[Test]
    public function devrait_scanner_le_disque_et_mettre_a_jour_le_cache_par_paquets_de_dix()
    {
        $scanId = 'test-scan-123';
        $disk = 'external_local';

        $fakeFiles = [];
        for ($i = 1; $i <= 12; $i++) {
            $fakeFiles[] = [
                'name' => "video{$i}.mp4",
                'type' => 'video',
                'path' => "/path/video{$i}.mp4"
            ];
        }

        $serviceMock = Mockery::mock(FileExplorerService::class);
        $serviceMock->shouldReceive('scanDiskRecursive')
            ->once()
            ->with($disk, '/', Mockery::type('callable'))
            ->andReturnUsing(function ($d, $p, $callback) use ($fakeFiles) {
                foreach ($fakeFiles as $file) {
                    $callback($file);
                }
                return [];
            });

        $job = new ScanDiskJob($disk, '/', $scanId);

        // Inject the mock via reflection (constructor uses new FileExplorerService())
        $ref = new \ReflectionClass($job);
        $prop = $ref->getProperty('fileExplorerService');
        $prop->setAccessible(true);
        $prop->setValue($job, $serviceMock);

        $job->handle();

        $this->assertEquals('done', Cache::get("scan:{$scanId}:status"));
        $this->assertEquals(12, Cache::get("scan:{$scanId}:count"));

        $results = Cache::get("scan:{$scanId}:results");
        $this->assertCount(12, $results);
    }
}
