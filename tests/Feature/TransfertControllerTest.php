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
        config()->set('btsplay.uris.nas_pad', '/nas/pad');
        config()->set('btsplay.uris.nas_arch', '/nas/arch');
        config()->set('btsplay.uris.nas_arch_win', '\\\\NAS\\arch');
        config()->set('btsplay.uris.nas_pad_win', '\\\\NAS\\pad');
    }

    /**
     * @test
     * GIVEN : un utilisateur authentifié
     * WHEN : on accède à la page des transferts
     * THEN : la page s'affiche avec la variable maxConcurrent
     */
    public function index_displays_transfert_page()
    {
        $response = $this->actingAs($this->user)->get(route('admin.transferts'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.transferts');
        $response->assertViewHas('maxConcurrent');
    }

    /**
     * @test
     * GIVEN : des médias sur NAS sans chemin local et un média avec chemin local
     * WHEN : on récupère la liste des transferts
     * THEN : seuls les médias sans chemin local et non terminés sont retournés
     */
    public function list_returns_media_without_local_paths()
    {
        Media::factory()->create([
            'URI_NAS_ARCH' => '/nas/arch/video1.mp4',
            'URI_NAS_PAD' => null,
            'chemin_local' => null,
            'transcode_status' => 'disponible',
        ]);

        Media::factory()->create([
            'URI_NAS_ARCH' => null,
            'URI_NAS_PAD' => '/nas/pad/video2.mxf',
            'chemin_local' => null,
            'transcode_status' => 'disponible',
        ]);

        // Exclu : a un chemin local
        Media::factory()->create([
            'URI_NAS_ARCH' => '/nas/arch/video3.mp4',
            'chemin_local' => '/local/video3.mp4',
        ]);

        // Exclu : transcodage terminé
        Media::factory()->create([
            'URI_NAS_ARCH' => '/nas/arch/video4.mp4',
            'chemin_local' => null,
            'transcode_status' => 'termine',
        ]);

        $response = $this->actingAs($this->user)->get(route('admin.transferts.list'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'results' => [
                '*' => ['id', 'filename', 'path', 'disk', 'available_paths', 'job_id', 'status', 'progress', 'finished', 'is_queued']
            ]
        ]);

        $json = $response->json();
        $this->assertCount(2, $json['results']);
    }

    /**
     * @test
     * GIVEN : un média en cours de transcodage en BD
     * WHEN : on récupère la liste des transferts
     * THEN : le statut et l'état du transcodage proviennent de la BD
     */
    public function list_shows_db_transcode_status()
    {
        $media = Media::factory()->create([
            'URI_NAS_ARCH' => '/nas/arch/TestVideo.mp4',
            'chemin_local' => null,
            'transcode_status' => 'en_cours',
            'transcode_job_id' => 'job-123',
        ]);

        $response = $this->actingAs($this->user)->get(route('admin.transferts.list'));

        $json = $response->json();
        $result = collect($json['results'])->firstWhere('id', $media->id);

        $this->assertEquals('job-123', $result['job_id']);
        $this->assertEquals('Démarrage...', $result['status']);
        $this->assertFalse($result['finished']);
    }

    /**
     * @test
     * GIVEN : un média en file d'attente
     * WHEN : on récupère la liste des transferts
     * THEN : le statut indique la file d'attente et is_queued est true
     */
    public function list_shows_queued_status()
    {
        $media = Media::factory()->create([
            'URI_NAS_ARCH' => '/nas/arch/video.mp4',
            'chemin_local' => null,
            'transcode_status' => 'en_attente',
        ]);

        $response = $this->actingAs($this->user)->get(route('admin.transferts.list'));

        $json = $response->json();
        $result = collect($json['results'])->firstWhere('id', $media->id);

        $this->assertEquals("En file d'attente", $result['status']);
        $this->assertTrue($result['is_queued']);
        $this->assertFalse($result['finished']);
    }

    /**
     * @test
     * GIVEN : un média avec à la fois un chemin NAS_ARCH et NAS_PAD
     * WHEN : on récupère la liste des transferts
     * THEN : le disque NAS_ARCH est utilisé en priorité et les deux chemins sont disponibles
     */
    public function list_prioritizes_arch_over_pad()
    {
        Media::factory()->create([
            'URI_NAS_ARCH' => '/nas/arch/video.mp4',
            'URI_NAS_PAD' => '/nas/pad/video.mxf',
            'chemin_local' => null,
            'transcode_status' => 'disponible',
        ]);

        $response = $this->actingAs($this->user)->get(route('admin.transferts.list'));

        $json = $response->json();
        $result = $json['results'][0];

        $this->assertEquals('nas_arch', $result['disk']);
        $this->assertStringEndsWith('.mp4', $result['filename']);
        $this->assertCount(2, $result['available_paths']);
    }

    /**
     * @test
     * GIVEN : un média avec uniquement un chemin NAS_PAD
     * WHEN : on récupère la liste des transferts
     * THEN : le disque NAS_PAD est utilisé comme source
     */
    public function list_uses_pad_when_arch_not_available()
    {
        Media::factory()->create([
            'URI_NAS_ARCH' => null,
            'URI_NAS_PAD' => '/nas/pad/video.mxf',
            'chemin_local' => null,
            'transcode_status' => 'disponible',
        ]);

        $response = $this->actingAs($this->user)->get(route('admin.transferts.list'));

        $json = $response->json();
        $result = $json['results'][0];

        $this->assertEquals('ftp_pad', $result['disk']);
        $this->assertStringEndsWith('.mxf', $result['filename']);
        $this->assertCount(1, $result['available_paths']);
    }

    /**
     * @test
     * GIVEN : un média existant sur NAS et un service FFAStrans mocké
     * WHEN : on lance un transcodage direct (sans action queue)
     * THEN : le job FFAStrans est soumis et le job_id est retourné
     */
    public function startJob_submits_job_to_ffastrans()
    {
        $media = Media::factory()->create([
            'URI_NAS_ARCH' => '/nas/arch/video.mp4',
            'chemin_local' => null,
            'transcode_status' => 'disponible',
        ]);

        $this->mock(FfastransService::class)
            ->shouldReceive('submitJob')
            ->once()
            ->andReturn(['job_id' => 'job-456']);

        $response = $this->actingAs($this->user)->postJson(route('admin.transferts.start'), [
            'id' => $media->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'job_id' => 'job-456']);

        $media->refresh();
        $this->assertEquals('en_cours', $media->transcode_status);
        $this->assertEquals('job-456', $media->transcode_job_id);
    }

    /**
     * @test
     * GIVEN : un service FFAStrans mocké qui lève une exception
     * WHEN : on tente de soumettre un job de transfert
     * THEN : une erreur 500 est retournée et le statut passe à 'echoue'
     */
    public function startJob_handles_ffastrans_error()
    {
        $media = Media::factory()->create([
            'URI_NAS_ARCH' => '/nas/arch/video.mp4',
            'chemin_local' => null,
            'transcode_status' => 'disponible',
        ]);

        $this->mock(FfastransService::class)
            ->shouldReceive('submitJob')
            ->once()
            ->andThrow(new \Exception('FFAStrans error'));

        $response = $this->actingAs($this->user)->postJson(route('admin.transferts.start'), [
            'id' => $media->id,
        ]);

        $response->assertStatus(500);
        $response->assertJson(['success' => false]);

        $media->refresh();
        $this->assertEquals('echoue', $media->transcode_status);
    }

    /**
     * @test
     * GIVEN : un job FFAStrans actif en cours de traitement
     * WHEN : on vérifie le statut du job
     * THEN : la progression et le label de traitement sont retournés
     */
    public function checkStatus_returns_active_job_progress()
    {
        $this->mock(FfastransService::class)
            ->shouldReceive('getJobStatus')
            ->once()
            ->with('job-123')
            ->andReturn([
                'source' => 'active',
                'state' => 'processing',
                'progress' => 75,
                'steps' => '2/4',
                'proc' => 'Encoding H264',
            ]);

        $response = $this->actingAs($this->user)->get(route('admin.transfers.status', 'job-123'));

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertEquals(75, $json['progress']);
        $this->assertEquals('Node 2/4: Encoding H264', $json['label']);
        $this->assertFalse($json['finished']);
    }

    /**
     * @test
     * GIVEN : un job FFAStrans terminé dans l'historique
     * WHEN : on vérifie le statut du job
     * THEN : le statut 'Terminé' est retourné avec finished à true
     */
    public function checkStatus_detects_success_state()
    {
        $this->mock(FfastransService::class)
            ->shouldReceive('getJobStatus')
            ->once()
            ->andReturn([
                'source' => 'history',
                'state' => 'success',
                'progress' => 100,
            ]);

        $response = $this->actingAs($this->user)->get(route('admin.transfers.status', 'job-123'));

        $response->assertJson([
            'progress' => 100,
            'label' => 'Terminé',
            'finished' => true,
        ]);
    }

    /**
     * @test
     * GIVEN : un job FFAStrans en état d'erreur
     * WHEN : on vérifie le statut du job
     * THEN : le statut 'Echoué' est retourné avec finished à true
     */
    public function checkStatus_detects_error_state()
    {
        $this->mock(FfastransService::class)
            ->shouldReceive('getJobStatus')
            ->once()
            ->andReturn([
                'source' => 'history',
                'state' => 'error',
                'progress' => 100,
            ]);

        $response = $this->actingAs($this->user)->get(route('admin.transfers.status', 'job-123'));

        $response->assertJson([
            'label' => 'Echoué',
            'finished' => true,
        ]);
    }

    /**
     * @test
     * GIVEN : un job FFAStrans annulé
     * WHEN : on vérifie le statut du job
     * THEN : le statut 'Annulé' est retourné avec finished à true
     */
    public function checkStatus_detects_cancelled_state()
    {
        $this->mock(FfastransService::class)
            ->shouldReceive('getJobStatus')
            ->once()
            ->andReturn([
                'source' => 'history',
                'state' => 'aborted',
                'progress' => 0,
            ]);

        $response = $this->actingAs($this->user)->get(route('admin.transfers.status', 'job-123'));

        $response->assertJson([
            'label' => 'Annulé',
            'finished' => true,
        ]);
    }

    /**
     * @test
     * GIVEN : un job en attente d'ingestion par l'API FFAStrans
     * WHEN : on vérifie le statut du job
     * THEN : un statut de synchronisation est retourné sans bloquer le polling
     */
    public function checkStatus_handles_pending_state()
    {
        $this->mock(FfastransService::class)
            ->shouldReceive('getJobStatus')
            ->once()
            ->andReturn([
                'source' => 'pending',
                'state' => 'Initializing',
            ]);

        $response = $this->actingAs($this->user)->get(route('admin.transfers.status', 'job-123'));

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertNull($json['progress']);
        $this->assertFalse($json['finished']);
        $this->assertTrue($json['is_pending']);
    }

    /**
     * @test
     * GIVEN : un service FFAStrans qui lève une exception de connexion
     * WHEN : on vérifie le statut d'un job
     * THEN : une réponse 200 de reconnexion est retournée pour ne pas casser le polling JS
     */
    public function checkStatus_handles_connection_error()
    {
        $this->mock(FfastransService::class)
            ->shouldReceive('getJobStatus')
            ->once()
            ->andThrow(new \Exception('Connection failed'));

        $response = $this->actingAs($this->user)->get(route('admin.transfers.status', 'job-123'));

        $response->assertStatus(200);
        $response->assertJson([
            'progress' => null,
            'label' => 'Reconnexion...',
            'finished' => false,
        ]);
    }

    /**
     * @test
     * GIVEN : un service FFAStrans prêt à annuler un job actif
     * WHEN : on annule le job
     * THEN : le job est annulé et le statut en BD est mis à jour
     */
    public function cancel_cancels_ffastrans_job_successfully()
    {
        $media = Media::factory()->create([
            'transcode_job_id' => 'job-123',
            'transcode_status' => 'en_cours',
        ]);

        $this->mock(FfastransService::class)
            ->shouldReceive('cancelJob')
            ->once()
            ->with('job-123')
            ->andReturn(true);

        $response = $this->actingAs($this->user)->post(route('admin.transfers.cancel', 'job-123'));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $media->refresh();
        $this->assertEquals('annule', $media->transcode_status);
    }

    /**
     * @test
     * GIVEN : un média en file d'attente avec un ID préfixé 'queue-'
     * WHEN : on annule le job de la file
     * THEN : le statut en BD est mis à jour sans appeler FFAStrans
     */
    public function cancel_cancels_queued_job()
    {
        $media = Media::factory()->create([
            'transcode_status' => 'en_attente',
        ]);

        // Pas de mock FFAStrans nécessaire — le cancel en file ne l'appelle pas
        $response = $this->actingAs($this->user)->post(route('admin.transfers.cancel', 'queue-' . $media->id));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $media->refresh();
        $this->assertEquals('annule', $media->transcode_status);
        $this->assertNull($media->transcode_job_id);
    }

    /**
     * @test
     * GIVEN : un service FFAStrans qui échoue à annuler un job
     * WHEN : on tente d'annuler le job
     * THEN : une erreur 500 est retournée
     */
    public function cancel_handles_failure()
    {
        $this->mock(FfastransService::class)
            ->shouldReceive('cancelJob')
            ->once()
            ->with('job-123')
            ->andReturn(false);

        $response = $this->actingAs($this->user)->post(route('admin.transfers.cancel', 'job-123'));

        $response->assertStatus(500);
        $response->assertJson(['success' => false]);
    }

    /**
     * @test
     * GIVEN : un média en cours de transcodage en BD
     * WHEN : on demande le statut BD de ce média
     * THEN : le job_id et le statut traduit sont retournés
     */
    public function getDbStatus_returns_status_for_existing_media()
    {
        $media = Media::factory()->create([
            'transcode_status' => 'en_cours',
            'transcode_job_id' => 'job-789',
        ]);

        $response = $this->actingAs($this->user)->getJson(route('admin.transferts.dbStatus', $media->id));

        $response->assertStatus(200);
        $response->assertJson([
            'job_id' => 'job-789',
            'status' => 'Démarrage...',
            'is_finished' => false,
        ]);
    }

    /**
     * @test
     * GIVEN : un média dont le transcodage est terminé
     * WHEN : on demande le statut BD
     * THEN : is_finished est true et le statut est 'Terminé'
     */
    public function getDbStatus_returns_finished_for_completed_media()
    {
        $media = Media::factory()->create([
            'transcode_status' => 'termine',
            'transcode_job_id' => 'job-done',
        ]);

        $response = $this->actingAs($this->user)->getJson(route('admin.transferts.dbStatus', $media->id));

        $response->assertStatus(200);
        $response->assertJson([
            'job_id' => 'job-done',
            'status' => 'Terminé',
            'is_finished' => true,
        ]);
    }

    /**
     * @test
     * GIVEN : aucun média avec l'identifiant donné
     * WHEN : on demande le statut BD
     * THEN : un statut 'Disponible' est retourné avec job_id null
     */
    public function getDbStatus_returns_default_for_nonexistent_media()
    {
        $response = $this->actingAs($this->user)->getJson(route('admin.transferts.dbStatus', 999999));

        $response->assertStatus(200);
        $response->assertJson([
            'job_id' => null,
            'status' => 'Disponible',
        ]);
    }

    /**
     * @test
     * GIVEN : un média avec le statut 'echoue'
     * WHEN : on demande le statut BD
     * THEN : le statut est 'Echoué' et is_finished est true
     */
    public function getDbStatus_returns_echoue_status()
    {
        $media = Media::factory()->create([
            'transcode_status' => 'echoue',
        ]);

        $response = $this->actingAs($this->user)->getJson(route('admin.transferts.dbStatus', $media->id));

        $response->assertJson([
            'status' => 'Echoué',
            'is_finished' => true,
        ]);
    }

    /**
     * @test
     * GIVEN : un média avec le statut 'annule'
     * WHEN : on demande le statut BD
     * THEN : le statut est 'Annulé' et is_finished est true
     */
    public function getDbStatus_returns_annule_status()
    {
        $media = Media::factory()->create([
            'transcode_status' => 'annule',
        ]);

        $response = $this->actingAs($this->user)->getJson(route('admin.transferts.dbStatus', $media->id));

        $response->assertJson([
            'status' => 'Annulé',
            'is_finished' => true,
        ]);
    }

    /**
     * @test
     * GIVEN : un identifiant de média inexistant
     * WHEN : on tente de lancer un job
     * THEN : une erreur 404 est retournée
     */
    public function startJob_returns_404_for_nonexistent_media()
    {
        $response = $this->actingAs($this->user)->postJson(route('admin.transferts.start'), [
            'id' => 999999,
        ]);

        $response->assertStatus(404);
        $response->assertJson(['success' => false, 'message' => 'Media introuvable']);
    }
}
