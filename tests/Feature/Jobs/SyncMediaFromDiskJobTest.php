<?php

namespace Tests\Feature\Jobs;

use App\Jobs\SyncMediaFromDiskJob;
use App\Models\Media;
use App\Services\FileExplorerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;

class SyncMediaFromDiskJobTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * GIVEN : un média orphelin en BDD et un nouveau fichier découvert sur le disque
     * WHEN : on lance la synchronisation du disque 'ftp_arch'
     * THEN : le nouveau média est créé et l'orphelin est supprimé
     */
    #[Test]
    public function devrait_synchroniser_les_medias_du_disque_et_supprimer_les_orphelins()
    {
        $tableName = (new Media())->getTable();

        $orphan = Media::create([
            'mtd_tech_titre' => 'titre completement different',
            'URI_NAS_ARCH' => '/old/path.mp4',
            'type' => 'video'
        ]);

        $serviceMock = Mockery::mock(FileExplorerService::class);
        $serviceMock->shouldReceive('scanDiskRecursive')
            ->once()
            ->andReturnUsing(function ($d, $p, $callback) {
                $callback([
                    'name' => 'New Movie.mp4',
                    'type' => 'video',
                    'path' => '/new/path.mp4'
                ]);
            });

        $job = new SyncMediaFromDiskJob('ftp_arch', '/');

        // Inject the mock via reflection (constructor uses new FileExplorerService())
        $ref = new \ReflectionClass($job);
        $prop = $ref->getProperty('fileExplorerService');
        $prop->setAccessible(true);
        $prop->setValue($job, $serviceMock);

        $job->handle();

        // Le nouveau média est créé
        $this->assertDatabaseHas($tableName, [
            'mtd_tech_titre' => 'new movie',
            'URI_NAS_ARCH' => '/new/path.mp4'
        ]);

        // L'orphelin est supprimé (aucun chemin valide restant)
        $this->assertDatabaseMissing($tableName, [
            'id' => $orphan->id
        ]);
    }
}
