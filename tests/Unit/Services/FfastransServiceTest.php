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

    /**
     * @test
     * GIVEN : un chemin Linux valide configuré avec un path_local
     * WHEN : on appelle translatePath avec ce chemin
     * THEN : le chemin est converti en format Windows UNC
     */
    public function translatePath_converts_linux_to_windows_path()
    {
        $linuxPath = '/var/www/storage/app/videos/2024/MonVideo.mp4';

        $result = $this->service->translatePath($linuxPath);

        $this->assertEquals('\\\\server\\share\\videos\\2024\\MonVideo.mp4', $result);
    }

    /**
     * @test
     * GIVEN : aucun path_local configuré et un chemin relatif
     * WHEN : on appelle translatePath avec un chemin relatif
     * THEN : le chemin est préfixé par le path_remote et converti en backslashes
     */
    public function translatePath_handles_relative_paths()
    {
        Config::set('services.ffastrans.path_local', null);
        Config::set('services.ffastrans.path_remote', '\\\\server\\share');
        $this->service = new FfastransService();

        $result = $this->service->translatePath('videos/test.mp4');

        $this->assertEquals('\\\\server\\share\\videos\\test.mp4', $result);
    }

    /**
     * @test
     * GIVEN : aucun path_remote configuré
     * WHEN : on appelle translatePath avec un chemin quelconque
     * THEN : le chemin est retourné avec les slashes convertis en backslashes uniquement
     */
    public function translatePath_returns_original_if_no_remote_root()
    {
        Config::set('services.ffastrans.path_remote', null);
        $this->service = new FfastransService();

        $result = $this->service->translatePath('/some/path/video.mp4');

        $this->assertEquals('\\some\\path\\video.mp4', $result);
    }

    /**
     * @test
     * GIVEN : un chemin déjà au format Windows
     * WHEN : on appelle translatePath avec ce chemin
     * THEN : la structure du chemin Windows est préservée
     */
    public function translatePath_preserves_windows_paths()
    {
        $windowsPath = 'C:\\Videos\\test.mp4';

        $result = $this->service->translatePath($windowsPath);

        // Should convert forward slashes if any but preserve structure
        $this->assertStringContainsString('C:\\Videos\\test.mp4', $result);
    }

    /**
     * @test
     * GIVEN : un chemin contenant des slashes Linux
     * WHEN : on appelle translatePath avec ce chemin
     * THEN : tous les slashes sont remplacés par des backslashes
     */
    public function translatePath_replaces_forward_slashes_with_backslashes()
    {
        $result = $this->service->translatePath('/var/www/storage/app/videos/test.mp4');

        $this->assertStringNotContainsString('/', $result);
        $this->assertStringContainsString('\\', $result);
    }

    /**
     * @test
     * GIVEN : une API FFAStrans simulée qui retourne un job_id
     * WHEN : on soumet un job avec un fichier, un workflow et des variables
     * THEN : le payload envoyé contient les bons paramètres et le job_id est retourné
     */
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

    /**
     * @test
     * GIVEN : un chemin UNC Windows et une API simulée
     * WHEN : on soumet un job avec ce chemin UNC
     * THEN : le chemin UNC est envoyé tel quel dans le champ inputfile
     */
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

    /**
     * @test
     * GIVEN : une API FFAStrans qui retourne une erreur 400
     * WHEN : on soumet un job
     * THEN : une exception est levée avec le code d'erreur
     */
    public function submitJob_throws_exception_on_failed_response()
    {
        Http::fake([
            'ffastrans.local/api/json/v2/jobs' => Http::response(['error' => 'Bad request'], 400),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to submit job: 400');

        $this->service->submitJob('/path/to/video.mp4', 'workflow-456');
    }

    /**
     * @test
     * GIVEN : un job actif et un job dans l'historique sur l'API
     * WHEN : on appelle getFullStatusList
     * THEN : les deux jobs sont retournés avec les bons statuts et noms de fichiers
     */
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

    /**
     * @test
     * GIVEN : deux jobs dans l'historique avec des dates différentes
     * WHEN : on appelle getFullStatusList
     * THEN : les jobs sont triés du plus récent au plus ancien
     */
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

    /**
     * @test
     * GIVEN : des jobs avec différents statuts anglais (Success, Error, Cancelled, Failed)
     * WHEN : on appelle getFullStatusList
     * THEN : les statuts sont traduits en français (Terminé, Echoué, Annulé, En cours)
     */
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

    /**
     * @test
     * GIVEN : une API FFAStrans qui retourne des erreurs 500
     * WHEN : on appelle getFullStatusList
     * THEN : un tableau vide est retourné sans lever d'exception
     */
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

    /**
     * @test
     * GIVEN : un job actif en cours de traitement à 75%
     * WHEN : on appelle getJobStatus avec son identifiant
     * THEN : le statut et la progression du job sont retournés
     */
    public function getJobStatus_returns_active_job_status()
    {
        Http::fake([
            'ffastrans.local/api/json/v2/jobs/job-123' => Http::response([
                'jobs' => [
                    [
                        'job_id' => 'job-123',
                        'state' => 'Processing',
                        'progress' => 75,
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getJobStatus('job-123');

        $this->assertEquals('active', $result['source']);
        $this->assertEquals('Processing', $result['state']);
        $this->assertEquals(75, $result['progress']);
    }

    /**
     * @test
     * GIVEN : un job absent des actifs mais présent dans l'historique
     * WHEN : on appelle getJobStatus avec son identifiant
     * THEN : le statut est récupéré depuis l'historique avec une progression à 100%
     */
    public function getJobStatus_falls_back_to_history_if_not_active()
    {
        Http::fake([
            'ffastrans.local/api/json/v2/jobs/job-123' => Http::response(['jobs' => []], 200),
            'ffastrans.local/api/json/v2/history/job-123' => Http::response([
                'history' => [
                    [
                        'job_id' => 'job-123',
                        'result' => 'Success',
                        'state' => 1,
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getJobStatus('job-123');

        $this->assertEquals('history', $result['source']);
        $this->assertEquals('success', $result['state']);
        $this->assertEquals(100, $result['progress']);
    }

    /**
     * @test
     * GIVEN : un job qui n'existe ni dans les actifs ni dans l'historique
     * WHEN : on appelle getJobStatus avec son identifiant
     * THEN : un statut d'erreur 'Job not found' est retourné
     */
    public function getJobStatus_returns_pending_when_job_not_found()
    {
        Http::fake([
            'ffastrans.local/api/json/v2/jobs/job-999' => Http::response([], 404),
            'ffastrans.local/api/json/v2/history/job-999' => Http::response([], 404),
        ]);

        $result = $this->service->getJobStatus('job-999');

        $this->assertEquals('pending', $result['source']);
        $this->assertEquals('Initializing', $result['state']);
    }

    /**
     * @test
     * GIVEN : un job actif et une API qui accepte la suppression
     * WHEN : on appelle cancelJob avec son identifiant
     * THEN : une requête DELETE est envoyée et true est retourné
     */
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

    /**
     * @test
     * GIVEN : une API qui retourne une erreur 500 lors de l'annulation
     * WHEN : on appelle cancelJob
     * THEN : false est retourné
     */
    public function cancelJob_returns_false_on_failure()
    {
        Http::fake([
            'ffastrans.local/api/json/v2/jobs/job-123' => Http::response([], 500),
        ]);

        $result = $this->service->cancelJob('job-123');

        $this->assertFalse($result);
    }

    /**
     * @test
     * GIVEN : une erreur réseau lors de l'appel à l'API
     * WHEN : on appelle cancelJob
     * THEN : une exception est levée
     */
    public function cancelJob_throws_on_network_error()
    {
        Http::fake(function () {
            throw new \Exception('Network error');
        });

        $this->expectException(\Exception::class);
        $this->service->cancelJob('job-123');
    }

    /**
     * @test
     * GIVEN : un utilisateur et un mot de passe configurés pour FFAStrans
     * WHEN : on effectue un appel à l'API
     * THEN : le header Authorization est présent dans la requête
     */
    public function client_uses_basic_auth_when_configured()
    {
        Http::fake([
            'ffastrans.local/api/json/v2/jobs' => Http::response(['jobs' => []], 200),
            'ffastrans.local/api/json/v2/history*' => Http::response(['history' => []], 200),
        ]);

        $this->service->getFullStatusList();

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization');
        });
    }

    /**
     * @test
     * GIVEN : aucun identifiant d'authentification configuré
     * WHEN : on effectue un appel à l'API
     * THEN : la requête est envoyée sans authentification et fonctionne
     */
    public function client_works_without_auth_when_not_configured()
    {
        Config::set('services.ffastrans.user', null);
        Config::set('services.ffastrans.password', null);
        $this->service = new FfastransService();

        Http::fake([
            'ffastrans.local/api/json/v2/jobs' => Http::response(['jobs' => []], 200),
            'ffastrans.local/api/json/v2/history*' => Http::response(['history' => []], 200),
        ]);

        $this->service->getFullStatusList();

        // Should still work
        Http::assertSent(function ($request) {
            return $request->url() === 'http://ffastrans.local/api/json/v2/jobs';
        });
    }
}
