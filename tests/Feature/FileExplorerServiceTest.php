<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use App\Services\FileExplorerService;

class FileExplorerServiceTest extends TestCase
{
    protected string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        // Crée un dossier temporaire pour les tests locaux
        $this->tempDir = sys_get_temp_dir() . '/fileexplorer_test';
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0777, true);
        }

        // Nettoie à chaque setup
        File::cleanDirectory($this->tempDir);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tempDir);
        parent::tearDown();
    }

    /**
     * @test
     * GIVEN : un répertoire temporaire vide
     * WHEN : on scanne ce répertoire
     * THEN : le résultat est un tableau vide
     */
    public function scanDisk_returns_empty_for_empty_directory()
    {
        $service = new FileExplorerService();
        $results = $service->scanDisk('external_local', $this->tempDir);

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    /**
     * @test
     * GIVEN : un répertoire contenant un dossier, une vidéo .mp4 et un fichier .txt
     * WHEN : on scanne ce répertoire
     * THEN : seuls le dossier et la vidéo sont retournés, le fichier texte est ignoré
     */
    public function scanDisk_returns_folders_and_videos_only()
    {
        // Création de dossiers et fichiers
        mkdir($this->tempDir . '/folder1');
        file_put_contents($this->tempDir . '/video1.mp4', 'dummy');
        file_put_contents($this->tempDir . '/text.txt', 'dummy');

        $service = new FileExplorerService();
        $results = $service->scanDisk('external_local', $this->tempDir);

        $this->assertCount(2, $results);

        $types = collect($results)->pluck('type')->all();
        $this->assertContains('folder', $types);
        $this->assertContains('video', $types);

        $names = collect($results)->pluck('name')->all();
        $this->assertContains('folder1', $names);
        $this->assertContains('video1.mp4', $names);
        $this->assertNotContains('text.txt', $names);
    }

    /**
     * @test
     * GIVEN : un disque Laravel simulé contenant un sous-dossier, une vidéo .mkv et un fichier .txt
     * WHEN : on scanne ce disque
     * THEN : seuls le dossier et la vidéo sont retournés, le fichier texte est ignoré
     */
    public function scanDisk_handles_laravel_disks()
    {
        Storage::fake('test_disk');

        Storage::disk('test_disk')->makeDirectory('subfolder');
        Storage::disk('test_disk')->put('video.mkv', 'dummy');
        Storage::disk('test_disk')->put('hidden.txt', 'dummy');

        $service = new FileExplorerService();
        $results = $service->scanDisk('test_disk', '/');

        $this->assertCount(2, $results);

        $types = collect($results)->pluck('type')->all();
        $this->assertContains('folder', $types);
        $this->assertContains('video', $types);

        $names = collect($results)->pluck('name')->all();
        $this->assertContains('subfolder', $names);
        $this->assertContains('video.mkv', $names);
        $this->assertNotContains('hidden.txt', $names);
    }

    /**
     * @test
     * GIVEN : un répertoire contenant un dossier et deux fichiers vidéo
     * WHEN : on scanne récursivement ce répertoire avec un callback
     * THEN : le callback est appelé pour chaque élément trouvé (dossier et vidéos)
     */
    public function scanDiskRecursive_calls_callback_for_all_items()
    {
        // Setup dossiers et fichiers
        mkdir($this->tempDir . '/folder1');
        file_put_contents($this->tempDir . '/video1.mp4', 'dummy');
        file_put_contents($this->tempDir . '/video2.mkv', 'dummy');

        $service = new FileExplorerService();

        $found = [];
        $service->scanDiskRecursive('external_local', $this->tempDir, function ($item) use (&$found) {
            $found[] = $item['name'];
        });

        $this->assertContains('folder1', $found);
        $this->assertContains('video1.mp4', $found);
        $this->assertContains('video2.mkv', $found);
    }

    /**
     * @test
     * GIVEN : des listes de noms de fichiers vidéo et non-vidéo
     * WHEN : on vérifie chaque fichier avec la méthode isVideo
     * THEN : les fichiers vidéo retournent true, les autres retournent false
     */
    public function isVideo_returns_true_for_video_extensions()
    {
        $service = new FileExplorerService();

        $videos = ['a.mp4', 'b.MKV', 'c.mov', 'd.avi', 'e.m4v', 'f.webm', 'g.mxf'];
        foreach ($videos as $file) {
            $this->assertTrue($service->isVideo($file));
        }

        $nonVideos = ['a.txt', 'b.pdf', 'c.jpg', 'd.docx'];
        foreach ($nonVideos as $file) {
            $this->assertFalse($service->isVideo($file));
        }
    }
}