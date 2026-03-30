<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Storage;
use App\Services\FileExplorerService;

class FileExplorerServiceTest extends TestCase
{
    /**
     * @test
     * GIVEN : un disque vide
     * WHEN : on scanne ce disque
     * THEN : le résultat est un tableau vide
     */
    public function scanDisk_returns_empty_for_empty_directory()
    {
        Storage::fake('test_disk');

        $service = new FileExplorerService();
        $results = $service->scanDisk('test_disk', '/');

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    /**
     * @test
     * GIVEN : un disque contenant un dossier, une vidéo .mp4 et un fichier .txt
     * WHEN : on scanne ce disque
     * THEN : seuls le dossier et la vidéo sont retournés, le fichier texte est ignoré
     */
    public function scanDisk_returns_folders_and_videos_only()
    {
        Storage::fake('test_disk');

        Storage::disk('test_disk')->makeDirectory('folder1');
        Storage::disk('test_disk')->put('video1.mp4', 'dummy');
        Storage::disk('test_disk')->put('text.txt', 'dummy');

        $service = new FileExplorerService();
        $results = $service->scanDisk('test_disk', '/');

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
     * GIVEN : un disque contenant un dossier et deux fichiers vidéo
     * WHEN : on scanne récursivement ce disque avec un callback
     * THEN : le callback est appelé pour chaque élément trouvé (dossier et vidéos)
     */
    public function scanDiskRecursive_calls_callback_for_all_items()
    {
        Storage::fake('test_disk');

        Storage::disk('test_disk')->makeDirectory('folder1');
        Storage::disk('test_disk')->put('video1.mp4', 'dummy');
        Storage::disk('test_disk')->put('video2.mkv', 'dummy');

        $service = new FileExplorerService();

        $found = [];
        $service->scanDiskRecursive('test_disk', '/', function ($item) use (&$found) {
            $found[] = $item['name'];
        });

        $this->assertContains('folder1', $found);
        $this->assertContains('video1.mp4', $found);
        $this->assertContains('video2.mkv', $found);
    }

    /**
     * @test
     * GIVEN : un disque contenant des fichiers cachés et un .gitkeep
     * WHEN : on scanne ce disque
     * THEN : les fichiers cachés et .gitkeep sont ignorés
     */
    public function scanDisk_ignores_hidden_files_and_gitkeep()
    {
        Storage::fake('test_disk');

        Storage::disk('test_disk')->put('.gitkeep', '');
        Storage::disk('test_disk')->put('.hidden_file', 'data');
        Storage::disk('test_disk')->put('video.mp4', 'dummy');

        $service = new FileExplorerService();
        $results = $service->scanDisk('test_disk', '/');

        $this->assertCount(1, $results);
        $this->assertEquals('video.mp4', $results[0]['name']);
    }

    /**
     * @test
     * GIVEN : un disque avec une arborescence de sous-dossiers contenant des vidéos
     * WHEN : on scanne récursivement
     * THEN : les vidéos dans les sous-dossiers sont trouvées via le callback
     */
    public function scanDiskRecursive_finds_nested_videos()
    {
        Storage::fake('test_disk');

        Storage::disk('test_disk')->makeDirectory('level1');
        Storage::disk('test_disk')->makeDirectory('level1/level2');
        Storage::disk('test_disk')->put('level1/video_l1.mp4', 'dummy');
        Storage::disk('test_disk')->put('level1/level2/video_l2.mov', 'dummy');
        Storage::disk('test_disk')->put('root_video.avi', 'dummy');

        $service = new FileExplorerService();

        $found = [];
        $service->scanDiskRecursive('test_disk', '/', function ($item) use (&$found) {
            if ($item['type'] === 'video') {
                $found[] = $item['name'];
            }
        });

        $this->assertContains('root_video.avi', $found);
        $this->assertContains('video_l1.mp4', $found);
        $this->assertContains('video_l2.mov', $found);
        $this->assertCount(3, $found);
    }

    /**
     * @test
     * GIVEN : un disque contenant des vidéos avec différentes extensions
     * WHEN : on scanne ce disque
     * THEN : toutes les extensions vidéo supportées sont détectées
     */
    public function scanDisk_detects_all_video_extensions()
    {
        Storage::fake('test_disk');

        $extensions = ['mp4', 'mov', 'avi', 'mkv', 'webm', 'm4v', 'mxf'];
        foreach ($extensions as $ext) {
            Storage::disk('test_disk')->put("video.{$ext}", 'dummy');
        }
        Storage::disk('test_disk')->put('document.pdf', 'dummy');

        $service = new FileExplorerService();
        $results = $service->scanDisk('test_disk', '/');

        $this->assertCount(count($extensions), $results);
        foreach ($results as $result) {
            $this->assertEquals('video', $result['type']);
            $this->assertEquals('test_disk', $result['disk']);
            $this->assertNull($result['id']);
        }
    }

    /**
     * @test
     * GIVEN : un disque dont le répertoire demandé n'existe pas
     * WHEN : on scanne ce répertoire
     * THEN : un tableau vide est retourné sans lever d'exception
     */
    public function scanDisk_returns_empty_on_storage_error()
    {
        Storage::fake('test_disk');

        $service = new FileExplorerService();
        $results = $service->scanDisk('test_disk', '/nonexistent/deep/path');

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    /**
     * @test
     * GIVEN : un disque contenant un sous-dossier à un seul niveau
     * WHEN : on scanne ce disque (non récursif)
     * THEN : le contenu du sous-dossier n'est pas inclus
     */
    public function scanDisk_does_not_recurse_into_subdirectories()
    {
        Storage::fake('test_disk');

        Storage::disk('test_disk')->makeDirectory('subfolder');
        Storage::disk('test_disk')->put('subfolder/nested_video.mp4', 'dummy');
        Storage::disk('test_disk')->put('root_video.mp4', 'dummy');

        $service = new FileExplorerService();
        $results = $service->scanDisk('test_disk', '/');

        $names = collect($results)->pluck('name')->all();
        $this->assertContains('subfolder', $names);
        $this->assertContains('root_video.mp4', $names);
        $this->assertNotContains('nested_video.mp4', $names);
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
