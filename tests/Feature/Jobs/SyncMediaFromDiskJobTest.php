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
     * TEST : devrait_synchroniser_les_medias_du_disque_et_supprimer_les_orphelins
     * * [STRUCTURE GIVEN / WHEN / THEN]
     * GIVEN : 
     * - Un média orphelin "titre different" existe en BDD avec un chemin ARCH.
     * - Le scan disque ne trouve qu'un NOUVEAU fichier "New Movie.mp4".
     * WHEN : 
     * - On lance la synchronisation du disque 'ftp_arch'.
     * THEN : 
     * - Le nouveau média doit être créé.
     * - L'ancien média (orphelin) doit être SUPPRIMÉ de la BDD car il n'a plus aucun chemin valide.
     */
    #[Test]
    public function devrait_synchroniser_les_medias_du_disque_et_supprimer_les_orphelins()
    {
        // --- GIVEN ---
        $tableName = (new Media())->getTable();

        // 1. Création de l'orphelin
        $orphan = Media::create([
            'mtd_tech_titre' => 'titre completement different',
            'URI_NAS_ARCH' => '/old/path.mp4',
            'type' => 'video'
        ]);

        // 2. Mock du service pour simuler la découverte d'un AUTRE fichier
        $serviceMock = Mockery::mock('alias:' . FileExplorerService::class);
        $serviceMock->shouldReceive('scanDiskRecursive')
            ->once()
            ->andReturnUsing(function ($d, $p, $callback) {
                $callback([
                    'name' => 'New Movie.mp4', 
                    'type' => 'video', 
                    'path' => '/new/path.mp4'
                ]);
            });

        // --- WHEN ---
        $job = new SyncMediaFromDiskJob('ftp_arch', '/');
        $job->handle();

        // --- THEN ---
        
        // Vérification 1 : Le nouveau média est bien créé
        $this->assertDatabaseHas($tableName, [
            'mtd_tech_titre' => 'new movie',
            'URI_NAS_ARCH' => '/new/path.mp4'
        ]);

        // Vérification 2 : L'ancien média est SUPPRIMÉ
        // Puisque URI_NAS_ARCH n'existe plus sur le disque et que les autres chemins (PAD, LOCAL) 
        // sont vides, ton code exécute $media->delete().
        $this->assertDatabaseMissing($tableName, [
            'id' => $orphan->id
        ]);
    }
}