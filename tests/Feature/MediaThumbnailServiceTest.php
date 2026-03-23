<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\File;
use App\Models\Media;
use App\Services\MediaThumbnailService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MediaThumbnailServiceTest extends TestCase
{
    use RefreshDatabase;

    protected string $thumbnailsDir;

    protected function setUp(): void
    {
        parent::setUp();

        // Dossier temporaire pour les thumbnails
        $this->thumbnailsDir = storage_path('app/public/thumbnails');
        if (!is_dir($this->thumbnailsDir)) {
            mkdir($this->thumbnailsDir, 0755, true);
        }

        // Nettoyer avant chaque test
        File::cleanDirectory($this->thumbnailsDir);
    }

    /**
     * Helper pour initialiser les propriétés protégées du service
     */
    protected function initializeService(MediaThumbnailService $service): MediaThumbnailService
    {
        $reflection = new \ReflectionClass($service);

        $prop = $reflection->getProperty('thumbnailsPath');
        $prop->setAccessible(true);
        $prop->setValue($service, $this->thumbnailsDir);

        $prop = $reflection->getProperty('ffmpegPath');
        $prop->setAccessible(true);
        $prop->setValue($service, '/usr/bin/true'); // commande factice

        $prop = $reflection->getProperty('ffprobePath');
        $prop->setAccessible(true);
        $prop->setValue($service, '/usr/bin/true'); // commande factice

        return $service;
    }

    /**
     * @test
     * GIVEN : un média avec une miniature déjà existante sur le disque
     * WHEN : on génère la miniature sans forcer la régénération
     * THEN : le chemin de la miniature existante est retourné sans régénération
     */
    public function it_returns_existing_thumbnail_without_regenerating_when_force_is_false()
    {
        $media = Media::factory()->create();
        $thumbnailPath = $this->thumbnailsDir . '/' . $media->id . '_miniature.jpg';
        file_put_contents($thumbnailPath, 'dummy');

        $service = $this->initializeService(new MediaThumbnailService());

        $result = $service->generateThumbnail($media);

        $this->assertEquals($thumbnailPath, $result);
    }

    /**
     * @test
     * GIVEN : un média avec un fichier vidéo local existant et ffmpeg mocké
     * WHEN : on force la génération de la miniature
     * THEN : une miniature est générée et son chemin contient l'identifiant du média
     */
    public function it_generates_thumbnail_from_local_video_file_when_forced()
    {
        $media = Media::factory()->create(['chemin_local' => 'videos/test.mp4']);
        $localFile = storage_path('app/videos/test.mp4');
        if (!is_dir(dirname($localFile))) mkdir(dirname($localFile), 0755, true);
        file_put_contents($localFile, 'dummy');

        $service = $this->initializeService(
            $this->partialMock(MediaThumbnailService::class)
                ->shouldAllowMockingProtectedMethods()
                ->makePartial()
        );

        // Mock de la méthode executeFfmpeg
        $service->shouldReceive('executeFfmpeg')->once()->andReturn(true);

        $result = $service->generateThumbnail($media, null, true);

        $this->assertStringContainsString($media->id . '_miniature.jpg', $result);
    }

    /**
     * @test
     * GIVEN : un média sans aucun chemin vidéo (local, archive, PAD)
     * WHEN : on tente de générer une miniature
     * THEN : le résultat est null
     */
    public function it_returns_null_when_no_video_path_is_available()
    {
        $media = Media::factory()->create([
            'chemin_local' => null,
            'URI_NAS_PAD' => null,
            'URI_NAS_ARCH' => null
        ]);

        $service = $this->initializeService(new MediaThumbnailService());

        $result = $service->generateThumbnail($media);

        $this->assertNull($result);
    }

    /**
     * @test
     * GIVEN : un média avec des chemins archive et PAD, et une configuration FTP archive
     * WHEN : on construit l'URL FTP pour le chemin archive
     * THEN : l'URL contient les identifiants et le chemin correct
     */
    public function it_correctly_builds_ftp_url_from_arch_and_pad_disks()
    {
        $media = Media::factory()->create([
            'URI_NAS_ARCH' => '/videos/arch.mp4',
            'URI_NAS_PAD' => '/videos/pad.mp4',
        ]);

        config()->set('filesystems.disks.ftp_arch', [
            'host' => 'arch.local',
            'username' => 'archuser',
            'password' => 'archpass',
        ]);

        $service = $this->initializeService(new MediaThumbnailService());

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('buildFtpUrl');
        $method->setAccessible(true);

        $ftpUrl = $method->invoke($service, $media, $media->URI_NAS_ARCH);

        $this->assertStringContainsString('ftp://archuser:archpass@arch.local/videos/arch.mp4', $ftpUrl);
    }

    /**
     * @test
     * GIVEN : un média avec une miniature existante sur le disque
     * WHEN : on supprime la miniature
     * THEN : la suppression retourne true et le fichier n'existe plus
     */
    public function it_deletes_existing_thumbnail_file_successfully()
    {
        $media = Media::factory()->create();
        $thumbnailPath = $this->thumbnailsDir . '/' . $media->id . '_miniature.jpg';
        file_put_contents($thumbnailPath, 'dummy');

        $service = $this->initializeService(new MediaThumbnailService());

        $result = $service->deleteThumbnail($media);

        $this->assertTrue($result);
        $this->assertFileDoesNotExist($thumbnailPath);
    }

    /**
     * @test
     * GIVEN : un média sans miniature existante sur le disque
     * WHEN : on tente de supprimer la miniature
     * THEN : la suppression retourne true (pas d'erreur)
     */
    public function it_returns_true_when_deleting_thumbnail_that_does_not_exist()
    {
        $media = Media::factory()->create();

        $service = $this->initializeService(new MediaThumbnailService());

        $result = $service->deleteThumbnail($media);

        $this->assertTrue($result);
    }

    /**
     * @test
     * GIVEN : deux médias dont un avec miniature existante et un sans
     * WHEN : on régénère les miniatures manquantes
     * THEN : une miniature est générée et l'autre est ignorée (skipped)
     */
    public function it_regenerates_missing_thumbnails_and_skips_existing_ones()
    {
        $media1 = Media::factory()->create(['chemin_local' => 'videos/test1.mp4']);
        $media2 = Media::factory()->create(['chemin_local' => 'videos/test2.mp4']);

        // Simule qu'un thumbnail existe déjà pour media1
        $thumbnail1 = $this->thumbnailsDir . '/' . $media1->id . '_miniature.jpg';
        file_put_contents($thumbnail1, 'dummy');

        $service = $this->initializeService(
            $this->partialMock(MediaThumbnailService::class)
                ->shouldAllowMockingProtectedMethods()
                ->makePartial()
        );

        $service->shouldReceive('generateThumbnail')
            ->once()
            ->andReturn($this->thumbnailsDir . '/' . $media2->id . '_miniature.jpg');

        $stats = $service->regenerateMissingThumbnails();

        $this->assertEquals(['success' => 1, 'failed' => 0, 'skipped' => 1], $stats);
    }

    /**
     * @test
     * GIVEN : un média avec un chemin local inexistant mais un chemin FTP archive disponible
     * WHEN : on force la génération de la miniature
     * THEN : la miniature est générée via le chemin archive
     */
    public function it_uses_archive_path_when_local_path_not_exists()
    {
        $media = Media::factory()->create([
            'chemin_local' => 'videos/nonexistent.mp4',
            'URI_NAS_ARCH' => '/nas/arch/video.mp4',
        ]);

        config()->set('filesystems.disks.ftp_arch', [
            'host' => 'arch.local',
            'username' => 'archuser',
            'password' => 'archpass',
        ]);

        $service = $this->initializeService(
            $this->partialMock(MediaThumbnailService::class)
                ->shouldAllowMockingProtectedMethods()
                ->makePartial()
        );

        $service->shouldReceive('getVideoDuration')->andReturn(120);
        $service->shouldReceive('executeFfmpeg')->once()->andReturn(true);

        $result = $service->generateThumbnail($media, null, true);

        $this->assertNotNull($result);
        $this->assertStringContainsString($media->id . '_miniature.jpg', $result);
    }

    /**
     * @test
     * GIVEN : un média avec uniquement un chemin FTP PAD disponible
     * WHEN : on force la génération de la miniature
     * THEN : la miniature est générée via le chemin PAD
     */
    public function it_uses_pad_path_when_archive_not_available()
    {
        $media = Media::factory()->create([
            'chemin_local' => null,
            'URI_NAS_ARCH' => null,
            'URI_NAS_PAD' => '/nas/pad/video.mxf',
        ]);

        config()->set('filesystems.disks.ftp_pad', [
            'host' => 'pad.local',
            'username' => 'paduser',
            'password' => 'padpass',
        ]);

        $service = $this->initializeService(
            $this->partialMock(MediaThumbnailService::class)
                ->shouldAllowMockingProtectedMethods()
                ->makePartial()
        );

        $service->shouldReceive('getVideoDuration')->andReturn(240);
        $service->shouldReceive('executeFfmpeg')->once()->andReturn(true);

        $result = $service->generateThumbnail($media, null, true);

        $this->assertNotNull($result);
    }

    /**
     * @test
     * GIVEN : des durées vidéo au format HH:MM:SS.ss
     * WHEN : on calcule le timecode (50% de la durée)
     * THEN : le timecode retourné correspond à la moitié de la durée en secondes
     */
    public function it_calculates_correct_timecode_from_duration()
    {
        $service = $this->initializeService(new MediaThumbnailService());

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('calculateTimecode');
        $method->setAccessible(true);

        // 50% de 120 secondes = 60 secondes
        $result = $method->invoke($service, '00:02:00.00');
        $this->assertEquals(60, $result);

        // 50% de 300 secondes = 150 secondes
        $result = $method->invoke($service, '00:05:00.00');
        $this->assertEquals(150, $result);
    }

    /**
     * @test
     * GIVEN : un média avec un chemin archive et ffmpeg configuré pour échouer
     * WHEN : on force la génération de la miniature
     * THEN : le résultat est null car ffmpeg a échoué
     */
    public function it_returns_null_when_ffmpeg_execution_fails()
    {
        $media = Media::factory()->create([
            'URI_NAS_ARCH' => '/nas/arch/video.mp4',
        ]);

        config()->set('filesystems.disks.ftp_arch', [
            'host' => 'arch.local',
            'username' => 'archuser',
            'password' => 'archpass',
        ]);

        $service = $this->initializeService(
            $this->partialMock(MediaThumbnailService::class)
                ->shouldAllowMockingProtectedMethods()
                ->makePartial()
        );

        $service->shouldReceive('getVideoDuration')->andReturn(120);
        $service->shouldReceive('executeFfmpeg')->once()->andReturn(false);

        $result = $service->generateThumbnail($media, null, true);

        $this->assertNull($result);
    }

    /**
     * @test
     * GIVEN : un média avec un chemin archive et une durée indéterminable (null)
     * WHEN : on force la génération de la miniature
     * THEN : ffmpeg est appelé avec le timecode par défaut (5 secondes)
     */
    public function it_uses_default_timecode_when_duration_cannot_be_determined()
    {
        $media = Media::factory()->create([
            'URI_NAS_ARCH' => '/nas/arch/video.mp4',
        ]);

        config()->set('filesystems.disks.ftp_arch', [
            'host' => 'arch.local',
            'username' => 'archuser',
            'password' => 'archpass',
        ]);

        $service = $this->initializeService(
            $this->partialMock(MediaThumbnailService::class)
                ->shouldAllowMockingProtectedMethods()
                ->makePartial()
        );

        $service->shouldReceive('getVideoDuration')->andReturn(null);
        $service->shouldReceive('executeFfmpeg')
            ->once()
            ->withArgs(function ($source, $dest, $timecode) {
                return $timecode === 5; // Default timecode
            })
            ->andReturn(true);

        $result = $service->generateThumbnail($media, null, true);

        $this->assertNotNull($result);
    }

    /**
     * @test
     * GIVEN : un média avec un chemin vidéo personnalisé passé en paramètre
     * WHEN : on force la génération de la miniature avec ce chemin personnalisé
     * THEN : la miniature est générée avec succès
     */
    public function it_generates_thumbnail_with_custom_video_path()
    {
        $media = Media::factory()->create();

        $service = $this->initializeService(
            $this->partialMock(MediaThumbnailService::class)
                ->shouldAllowMockingProtectedMethods()
                ->makePartial()
        );

        config()->set('filesystems.disks.ftp_arch', [
            'host' => 'custom.local',
            'username' => 'user',
            'password' => 'pass',
        ]);

        $media->URI_NAS_ARCH = '/custom/path.mp4';

        $service->shouldReceive('getVideoDuration')->andReturn(100);
        $service->shouldReceive('executeFfmpeg')->once()->andReturn(true);

        $result = $service->generateThumbnail($media, '/custom/path.mp4', true);

        $this->assertNotNull($result);
    }

    /**
     * @test
     * GIVEN : un média avec un chemin archive mais sans configuration FTP
     * WHEN : on force la génération de la miniature
     * THEN : le résultat est null car la configuration FTP est absente
     */
    public function it_returns_null_when_ftp_config_is_missing()
    {
        $media = Media::factory()->create([
            'URI_NAS_ARCH' => '/nas/arch/video.mp4',
        ]);

        config()->set('filesystems.disks.ftp_arch', null);

        $service = $this->initializeService(new MediaThumbnailService());

        $result = $service->generateThumbnail($media, null, true);

        $this->assertNull($result);
    }

    /**
     * @test
     * GIVEN : deux médias sans miniature, dont un sans chemin vidéo et un avec un fichier local
     * WHEN : on régénère les miniatures manquantes
     * THEN : les statistiques contiennent les compteurs de succès et d'échecs
     */
    public function regenerateMissingThumbnails_tracks_failed_generations()
    {
        $media1 = Media::factory()->create(['chemin_local' => null, 'URI_NAS_ARCH' => null, 'URI_NAS_PAD' => null]);
        $media2 = Media::factory()->create(['chemin_local' => 'videos/test.mp4']);

        // Create local file for media2
        $localFile = storage_path('app/videos/test.mp4');
        if (!is_dir(dirname($localFile))) mkdir(dirname($localFile), 0755, true);
        file_put_contents($localFile, 'dummy');

        $service = $this->initializeService(
            $this->partialMock(MediaThumbnailService::class)
                ->shouldAllowMockingProtectedMethods()
                ->makePartial()
        );

        $service->shouldReceive('getVideoDuration')->andReturn(120);
        $service->shouldReceive('executeFfmpeg')->andReturn(true);

        $stats = $service->regenerateMissingThumbnails();

        $this->assertArrayHasKey('failed', $stats);
        $this->assertArrayHasKey('success', $stats);
    }
}