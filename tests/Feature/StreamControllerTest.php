<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StreamControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('ftp_arch');
        Storage::fake('ftp_pad');
        Storage::fake('external_local');
        Storage::fake('app');

        putenv('FTP_PAD_HOST=test');
        putenv('FTP_ARCH_HOST=test');
    }

    /**
     * @test
     * GIVEN : aucun média n'existe avec l'identifiant 999
     * WHEN : on tente de streamer ce média inexistant
     * THEN : la réponse retourne un statut 404
     */
    public function it_returns_404_when_media_does_not_exist()
    {
        $response = $this->get('/stream/999');

        $response->assertStatus(404);
    }

    /**
     * @test
     * GIVEN : un média existe sans aucun chemin vidéo configuré
     * WHEN : on tente de streamer ce média
     * THEN : la réponse retourne un statut 404
     */
    public function it_returns_404_when_media_has_no_video_paths()
    {
        $media = Media::factory()->create([
            'chemin_local' => null,
            'URI_NAS_ARCH' => null,
            'URI_NAS_PAD' => null,
        ]);

        $response = $this->get("/stream/{$media->id}");

        $response->assertStatus(404);
    }

    /**
     * @test
     * GIVEN : un média avec un chemin local et un fichier vidéo présent sur le disque local
     * WHEN : on demande le streaming de ce média
     * THEN : la réponse retourne un statut 200 avec le header Accept-Ranges
     */
    public function it_streams_video_from_local_storage_when_chemin_local_exists()
    {
        $media = Media::factory()->create([
            'chemin_local' => 'video.mp4',
        ]);

        Storage::disk('external_local')->put('video.mp4', 'fake video');

        $response = $this->get("/stream/{$media->id}");

        $response->assertStatus(200);
        $response->assertHeader('Accept-Ranges', 'bytes');
    }

    /**
     * @test
     * GIVEN : un média avec un chemin local pointant vers un fichier inexistant
     * WHEN : on demande le streaming de ce média
     * THEN : la réponse retourne un statut 404
     */
    public function it_returns_404_if_local_video_does_not_exist()
    {
        $media = Media::factory()->create([
            'chemin_local' => 'missing.mp4',
        ]);

        $response = $this->get("/stream/{$media->id}");

        $response->assertStatus(404);
    }

    /**
     * @test
     * GIVEN : un média sans chemin local mais avec un chemin FTP archive et un fichier présent
     * WHEN : on demande le streaming de ce média
     * THEN : la réponse retourne un statut 200 avec le header Accept-Ranges
     */
    public function it_streams_video_from_ftp_arch_disk()
    {
        $media = Media::factory()->create([
            'chemin_local' => null,
            'URI_NAS_ARCH' => 'videos/test.mp4',
        ]);

        Storage::disk('ftp_arch')->put('videos/test.mp4', 'fake video');

        $response = $this->get("/stream/{$media->id}");

        $response->assertStatus(200);
        $response->assertHeader('Accept-Ranges', 'bytes');
    }

    /**
     * @test
     * GIVEN : un média avec uniquement un chemin FTP PAD et un fichier présent sur ce disque
     * WHEN : on demande le streaming de ce média
     * THEN : la réponse retourne un statut 200
     */
    public function it_streams_video_from_ftp_pad_disk_when_arch_not_available()
    {
        $media = Media::factory()->create([
            'chemin_local' => null,
            'URI_NAS_ARCH' => null,
            'URI_NAS_PAD' => 'videos/test.mp4',
        ]);

        Storage::disk('ftp_pad')->put('videos/test.mp4', 'fake video');

        $response = $this->get("/stream/{$media->id}");

        $response->assertStatus(200);
    }

    /**
     * @test
     * GIVEN : un média avec un chemin FTP archive mais le fichier n'existe pas sur le disque
     * WHEN : on demande le streaming de ce média
     * THEN : la réponse retourne un statut 404
     */
    public function it_returns_404_when_ftp_file_is_missing()
    {
        $media = Media::factory()->create([
            'chemin_local' => null,
            'URI_NAS_ARCH' => 'videos/missing.mp4',
        ]);

        $response = $this->get("/stream/{$media->id}");

        $response->assertStatus(404);
    }

    /**
     * @test
     * GIVEN : un média avec un fichier vidéo de 5000 octets sur le disque FTP archive
     * WHEN : on envoie une requête avec un header Range demandant les octets 0 à 100
     * THEN : la réponse retourne un statut 206 (Partial Content) avec le header Accept-Ranges
     */
    public function it_supports_http_range_requests()
    {
        $media = Media::factory()->create([
            'chemin_local' => null,
            'URI_NAS_ARCH' => 'videos/test.mp4',
        ]);

        Storage::disk('ftp_arch')->put('videos/test.mp4', str_repeat('A', 5000));

        $response = $this->withHeaders([
            'Range' => 'bytes=0-100'
        ])->get("/stream/{$media->id}");

        $response->assertStatus(206);
        $response->assertHeader('Accept-Ranges', 'bytes');
    }

    /**
     * @test
     * GIVEN : un média pointant vers un fichier playlist HLS (.m3u8) sur le disque FTP archive
     * WHEN : on demande le streaming de ce média
     * THEN : la réponse retourne un statut 200 avec le Content-Type HLS approprié
     */
    public function it_streams_hls_playlist_when_extension_is_m3u8()
    {
        $media = Media::factory()->create([
            'chemin_local' => null,
            'URI_NAS_ARCH' => 'videos/playlist.m3u8',
        ]);

        Storage::disk('ftp_arch')->put('videos/playlist.m3u8', '#EXTM3U');

        $response = $this->get("/stream/{$media->id}");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.apple.mpegurl');
    }

    /**
     * @test
     * GIVEN : un média HLS avec un segment .ts présent sur le disque FTP archive
     * WHEN : on demande le streaming du segment spécifique
     * THEN : la réponse retourne un statut 200 avec le Content-Type video/mp2t
     */
    public function it_streams_hls_segment()
    {
        $media = Media::factory()->create([
            'chemin_local' => null,
            'URI_NAS_ARCH' => 'videos/playlist.m3u8',
        ]);

        Storage::disk('ftp_arch')->put('videos/segment1.ts', 'segmentdata');

        $response = $this->get("/stream/{$media->id}/segment/segment1.ts");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'video/mp2t');
    }

    /**
     * @test
     * GIVEN : un média HLS sans le segment .ts demandé sur le disque
     * WHEN : on demande le streaming d'un segment inexistant
     * THEN : la réponse retourne un statut 404
     */
    public function it_returns_404_when_hls_segment_is_missing()
    {
        $media = Media::factory()->create([
            'chemin_local' => null,
            'URI_NAS_ARCH' => 'videos/playlist.m3u8',
        ]);

        $response = $this->get("/stream/{$media->id}/segment/missing.ts");

        $response->assertStatus(404);
    }
}