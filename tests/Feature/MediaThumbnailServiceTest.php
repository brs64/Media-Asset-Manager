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

    /** @test */
    public function it_returns_existing_thumbnail_without_regenerating_when_force_is_false()
    {
        $media = Media::factory()->create();
        $thumbnailPath = $this->thumbnailsDir . '/' . $media->id . '_miniature.jpg';
        file_put_contents($thumbnailPath, 'dummy');

        $service = $this->initializeService(new MediaThumbnailService());

        $result = $service->generateThumbnail($media);

        $this->assertEquals($thumbnailPath, $result);
    }

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
    public function it_returns_true_when_deleting_thumbnail_that_does_not_exist()
    {
        $media = Media::factory()->create();

        $service = $this->initializeService(new MediaThumbnailService());

        $result = $service->deleteThumbnail($media);

        $this->assertTrue($result);
    }

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
    public function it_calculates_correct_timecode_from_duration()
    {
        $service = $this->initializeService(new MediaThumbnailService());

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('calculateTimecode');
        $method->setAccessible(true);

        // 50% de 120 secondes = 60 secondes
        $result = $method->invoke($service, 120);
        $this->assertEquals(60, $result);

        // 50% de 300 secondes = 150 secondes
        $result = $method->invoke($service, 300);
        $this->assertEquals(150, $result);
    }

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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