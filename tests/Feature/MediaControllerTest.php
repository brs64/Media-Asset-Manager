<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

use App\Models\Media;
use App\Models\Role;

use App\Jobs\SyncMediaFromDiskJob;
use App\Services\MediaService;
use Database\Seeders\RoleSeeder;

class MediaControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function index_displays_media_list()
    {
        Media::factory()->count(3)->create();

        $response = $this->get(route('medias.index'));

        $response->assertStatus(200);
        $response->assertViewIs('home');
        $response->assertViewHas('medias');
    }

    /** @test */
    public function store_creates_media_and_participations()
    {
        $this->seed(RoleSeeder::class);
        $role = Role::first();

        $response = $this->post(route('medias.store'), [
            'mtd_tech_titre' => "Titre\ntest",
            'description' => 'Description',
            'participations' => [
                [
                    'eleve_nom' => 'Doe John',
                    'role_id' => $role->id,
                ],
            ],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('medias', [
            'mtd_tech_titre' => 'Titre test',
        ]);

        $this->assertDatabaseHas('eleves', [
            'nom' => 'Doe',
            'prenom' => 'John',
        ]);

        $this->assertDatabaseHas('participations', [
            'role_id' => $role->id,
        ]);
    }

    /** @test */
    public function store_requires_title()
    {
        $response = $this->post(route('medias.store'), []);

        $response->assertSessionHasErrors('mtd_tech_titre');
    }

    /** @test */
    public function show_displays_media()
    {
        $media = Media::factory()->create();

        $response = $this->get(route('medias.show', $media->id));

        $response->assertStatus(200);
        $response->assertViewIs('video');
    }

    /** @test */
    public function show_returns_404_when_media_not_found()
    {
        $response = $this->get(route('medias.show', 999999));

        $response->assertStatus(404);
    }

    /** @test */
    public function update_updates_media_and_participations()
    {
        $this->seed(RoleSeeder::class);
        $role = Role::first();

        $media = Media::factory()->create();

        $response = $this->put(route('medias.update', $media->id), [
            'mtd_tech_titre' => "Nouveau\ntitre",
            'participations' => [
                [
                    'eleve_nom' => 'Smith Anna',
                    'role_id' => $role->id,
                ],
            ],
        ]);

        $response->assertRedirect(route('medias.show', $media->id));

        $this->assertDatabaseHas('medias', [
            'id' => $media->id,
            'mtd_tech_titre' => 'Nouveau titre',
        ]);

        $this->assertDatabaseHas('participations', [
            'media_id' => $media->id,
            'role_id' => $role->id,
        ]);
    }

    /** @test */
    public function destroy_redirects_on_success()
    {
        $media = Media::factory()->create();

        $this->mock(MediaService::class)
            ->shouldReceive('deleteMedia')
            ->once()
            ->andReturn(true);

        $response = $this->delete(route('medias.destroy', $media->id));

        $response->assertRedirect(route('medias.index'));
        $response->assertSessionHas('success');
    }

    /** @test */
    public function sync_dispatches_jobs()
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);
        Queue::fake();

        $response = $this->post(route('admin.media.sync'));

        Queue::assertPushed(SyncMediaFromDiskJob::class, 3);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    /** @test */
    public function sync_local_path_success()
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        $this->mock(MediaService::class)
            ->shouldReceive('syncLocalPath')
            ->once()
            ->andReturn(true);

        $response = $this->postJson(route('admin.media.addLocalPath'), [
            'path' => '/test/path',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Chemin local synchronisé',
            ]);
    }

    /** @test */
    public function sync_local_path_returns_404_when_not_found()
    {

        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        $this->mock(MediaService::class)
            ->shouldReceive('syncLocalPath')
            ->once()
            ->andReturn(false);

        $response = $this->postJson(route('admin.media.addLocalPath'), [
            'path' => '/unknown/path',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Media non trouvé',
            ]);
    }
}