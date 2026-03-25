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
    /**
     * Nettoyage des mocks d'alias après chaque test.
     */
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * * GIVEN : 
     * - Un ID de scan 'test-scan-123' et un disque 'external_local'.
     * - Une liste fictive de 12 fichiers vidéo.
     * - Un Mock (alias) du FileExplorerService qui simule la découverte de ces 12 fichiers
     * via l'exécution du callback fourni au service.
     * * WHEN : 
     * - La méthode handle() du ScanDiskJob est exécutée.
     * * THEN : 
     * - Le statut du scan en cache doit être égal à 'done'.
     * - Le compteur global en cache ('count') doit afficher 12.
     * - La liste des résultats ('results') en cache doit contenir exactement 12 éléments,
     * prouvant que le buffer de 10 a été vidé et fusionné correctement avec les 2 restants.
     */
    #[Test]
    public function devrait_scanner_le_disque_et_mettre_a_jour_le_cache_par_paquets_de_dix()
    {
        // --- GIVEN ---
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
        $serviceMock = Mockery::mock('alias:' . FileExplorerService::class);
        $serviceMock->shouldReceive('scanDiskRecursive')
            ->once()
            ->with($disk, '/', Mockery::type('callable'))
            ->andReturnUsing(function ($d, $p, $callback) use ($fakeFiles) {
                foreach ($fakeFiles as $file) {
                    $callback($file);
                }
                return []; 
            });

        // --- WHEN ---
        $job = new ScanDiskJob($disk, '/', $scanId);
        $job->handle();

        // --- THEN ---

        $this->assertEquals('done', Cache::get("scan:{$scanId}:status"));
        
    
        $this->assertEquals(12, Cache::get("scan:{$scanId}:count"));

        $results = Cache::get("scan:{$scanId}:results");
        $this->assertCount(12, $results);
    }
}