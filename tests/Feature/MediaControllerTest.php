<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

use App\Models\Media;
use App\Models\Role;

use App\Jobs\SyncMediaFromDiskJob;
use App\Models\Eleve;
use App\Models\Professeur;
use App\Models\Projet;
use App\Models\User;
use App\Services\MediaService;

class MediaControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * GIVEN : trois médias existent en base
     * WHEN : on accède à la liste des médias
     * THEN : la page s'affiche avec la liste des médias
     */
    public function index_displays_media_list()
    {
        Media::factory()->count(3)->create(['chemin_local' => '/local/v.mp4']);

        $response = $this->get(route('medias.index'));

        $response->assertStatus(200);
        $response->assertViewIs('home');

        $medias = $response->viewData('medias');
        $this->assertEquals(3, $medias->total());
    }

    /**
     * @test
     * GIVEN : des rôles existent et des données valides avec participations
     * WHEN : on soumet le formulaire de création d'un média
     * THEN : le média, l'élève et la participation sont créés en base
     */
    public function store_creates_media_and_participations()
    {
        $response = $this->post(route('medias.store'), [
            'mtd_tech_titre' => "Titre\ntest",
            'promotion' => '2024',
            'type' => 'Court-métrage',
            'theme' => 'Fiction',
            'description' => 'Description',
            'URI_NAS_ARCH' => null,
            'URI_NAS_PAD' => null,
            'chemin_local' => null,
            'professeur_id' => null,
            'participations' => [
                [
                    'eleve_nom' => 'Doe John',
                    'role_nom' => 'Réalisateur',
                ],
            ],
        ]);

        $media = Media::where('mtd_tech_titre', 'Titre test')->first();
        $this->assertNotNull($media);
        $response->assertRedirect(route('medias.show', $media->id));

        $this->assertDatabaseHas('eleves', [
            'nom' => 'Doe',
            'prenom' => 'John',
        ]);

        $this->assertDatabaseHas('roles', [
            'libelle' => 'Réalisateur',
        ]);

        $role = Role::where('libelle', 'Réalisateur')->first();
        $this->assertDatabaseHas('participations', [
            'media_id' => $media->id,
            'role_id' => $role->id,
        ]);
    }

    /**
     * @test
     * GIVEN : aucune donnée fournie
     * WHEN : on soumet le formulaire de création d'un média
     * THEN : une erreur de validation est retournée pour le titre
     */
    public function store_requires_title()
    {
        $response = $this->post(route('medias.store'), []);

        $response->assertSessionHasErrors('mtd_tech_titre');
    }

    /**
     * @test
     * GIVEN : un média existant en base
     * WHEN : on accède à la page de détail de ce média
     * THEN : la page vidéo s'affiche correctement
     */
    public function show_displays_media()
    {
        $media = Media::factory()->create();

        $response = $this->get(route('medias.show', $media->id));

        $response->assertStatus(200);
        $response->assertViewIs('video');
    }

    /**
     * @test
     * GIVEN : aucun média avec l'identifiant 999999 n'existe
     * WHEN : on tente d'accéder à ce média
     * THEN : une erreur 404 est retournée
     */
    public function show_returns_404_when_media_not_found()
    {
        $response = $this->get(route('medias.show', 999999));

        $response->assertStatus(404);
    }

    /**
     * @test
     * GIVEN : un média existant et des rôles disponibles
     * WHEN : on met à jour le titre et les participations du média
     * THEN : le média est modifié et les nouvelles participations sont enregistrées
     */
    public function update_updates_media_and_participations()
    {
        $media = Media::factory()->create();

        $response = $this->put(route('medias.update', $media->id), [
            'mtd_tech_titre' => "Nouveau\ntitre",
            'participations' => [
                [
                    'eleve_nom' => 'Smith Anna',
                    'role_nom' => 'Cadreur',
                ],
            ],
        ]);

        $response->assertRedirect(route('medias.show', $media->id));

        $this->assertDatabaseHas('medias', [
            'id' => $media->id,
            'mtd_tech_titre' => 'Nouveau titre',
        ]);

        $this->assertDatabaseHas('roles', ['libelle' => 'Cadreur']);
        $this->assertDatabaseHas('eleves', ['nom' => 'Smith', 'prenom' => 'Anna']);

        $this->assertDatabaseHas('participations', [
            'media_id' => $media->id,
            'role_id' => Role::where('libelle', 'Cadreur')->first()->id,
        ]);
    }

    /**
     * @test
     * GIVEN : un média existant et le service de suppression mocké
     * WHEN : on supprime le média
     * THEN : l'utilisateur est redirigé vers la liste avec un message de succès
     */
    public function destroy_redirects_on_success()
    {
        $media = Media::factory()->create();

        $mock = $this->mock(MediaService::class);
        $mock->shouldReceive('clearLocalFiles')
            ->once()
            ->andReturn(true);
        $mock->shouldReceive('deleteMedia')
            ->once()
            ->andReturn(true);

        $response = $this->delete(route('medias.destroy', $media->id));

        $response->assertRedirect(route('medias.index'));
        $response->assertSessionHas('success');
    }

    /**
     * @test
     * GIVEN : des projets, professeurs, élèves et rôles existent en base
     * WHEN : on accède au formulaire de création
     * THEN : la page s'affiche avec les données des listes déroulantes
     */
    public function create_displays_form_with_dropdown_data()
    {
        Projet::factory()->count(2)->create();
        Professeur::factory()->count(3)->create();

        $response = $this->get(route('medias.create'));

        $response->assertStatus(200);
        $response->assertViewIs('formulaireMetadonnees');
        $response->assertViewHas(['projets', 'professeurs', 'eleves', 'roles']);
    }

    /**
     * @test
     * GIVEN : un média existant avec un professeur et des participations
     * WHEN : on accède au formulaire d'édition
     * THEN : la page s'affiche avec le média et ses relations chargées
     */
    public function edit_displays_form_with_media_data()
    {
        $user = User::factory()->create();
        $prof = Professeur::factory()->create(['user_id' => $user->id]);
        $media = Media::factory()->create(['professeur_id' => $prof->id]);

        $response = $this->get(route('medias.edit', $media->id));

        $response->assertStatus(200);
        $response->assertViewIs('formulaireMetadonnees');
        $response->assertViewHas('media');

        $viewMedia = $response->viewData('media');
        $this->assertEquals($media->id, $viewMedia->id);
    }

    /**
     * @test
     * GIVEN : un média inexistant
     * WHEN : on tente d'accéder au formulaire d'édition
     * THEN : une erreur 404 est retournée
     */
    public function edit_returns_404_for_nonexistent_media()
    {
        $response = $this->get(route('medias.edit', 999999));

        $response->assertStatus(404);
    }

    /**
     * @test
     * GIVEN : des données valides avec des projets par nom
     * WHEN : on soumet le formulaire de création
     * THEN : les projets sont créés automatiquement et associés au média
     */
    public function store_creates_projects_by_name()
    {
        $response = $this->post(route('medias.store'), [
            'mtd_tech_titre' => 'Video avec projets',
            'promotion' => null,
            'type' => null,
            'theme' => null,
            'description' => null,
            'URI_NAS_ARCH' => null,
            'URI_NAS_PAD' => null,
            'chemin_local' => null,
            'professeur_id' => null,
            'projet_noms' => ['Projet Alpha', 'Projet Beta'],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('projets', ['libelle' => 'Projet Alpha']);
        $this->assertDatabaseHas('projets', ['libelle' => 'Projet Beta']);

        $media = Media::where('mtd_tech_titre', 'Video avec projets')->first();
        $this->assertEquals(2, $media->projets()->count());
    }

    /**
     * @test
     * GIVEN : des données valides avec des propriétés personnalisées
     * WHEN : on soumet le formulaire de création
     * THEN : les propriétés sont enregistrées dans le champ JSON du média
     */
    public function store_saves_custom_properties()
    {
        $response = $this->post(route('medias.store'), [
            'mtd_tech_titre' => 'Video avec props',
            'promotion' => null,
            'type' => null,
            'theme' => null,
            'description' => null,
            'URI_NAS_ARCH' => null,
            'URI_NAS_PAD' => null,
            'chemin_local' => null,
            'professeur_id' => null,
            'properties' => [
                ['key' => 'camera', 'value' => 'Sony A7III'],
                ['key' => 'lieu', 'value' => 'Paris'],
                ['key' => '', 'value' => 'ignored'],
            ],
        ]);

        $response->assertRedirect();

        $media = Media::where('mtd_tech_titre', 'Video avec props')->first();
        $this->assertEquals('Sony A7III', $media->properties['camera']);
        $this->assertEquals('Paris', $media->properties['lieu']);
        $this->assertArrayNotHasKey('', $media->properties);
    }

    /**
     * @test
     * GIVEN : un média existant avec des participations
     * WHEN : on met à jour avec de nouvelles participations
     * THEN : les anciennes participations sont remplacées par les nouvelles
     */
    public function update_replaces_old_participations()
    {
        $media = Media::factory()->create();
        $oldRole = \App\Models\Role::factory()->create(['libelle' => 'Ancien']);
        $oldEleve = Eleve::factory()->create(['nom' => 'Vieux', 'prenom' => 'Test']);
        \App\Models\Participation::create([
            'media_id' => $media->id,
            'eleve_id' => $oldEleve->id,
            'role_id' => $oldRole->id,
        ]);

        $this->assertEquals(1, $media->participations()->count());

        $response = $this->put(route('medias.update', $media->id), [
            'mtd_tech_titre' => $media->mtd_tech_titre,
            'participations' => [
                ['eleve_nom' => 'Nouveau Participant', 'role_nom' => 'Acteur'],
            ],
        ]);

        $response->assertRedirect(route('medias.show', $media->id));

        $this->assertEquals(1, $media->participations()->count());
        $newParticipation = $media->participations()->first();
        $this->assertEquals('Acteur', $newParticipation->role->libelle);
        $this->assertDatabaseMissing('participations', [
            'media_id' => $media->id,
            'role_id' => $oldRole->id,
        ]);
    }

    /**
     * @test
     * GIVEN : un service de suppression qui échoue
     * WHEN : on supprime le média
     * THEN : l'utilisateur est redirigé avec un message d'erreur
     */
    public function destroy_shows_error_on_failure()
    {
        $media = Media::factory()->create();

        $mock = $this->mock(MediaService::class);
        $mock->shouldReceive('clearLocalFiles')->once()->andReturn(true);
        $mock->shouldReceive('deleteMedia')->once()->andReturn(false);

        $response = $this->delete(route('medias.destroy', $media->id));

        $response->assertRedirect(route('medias.index'));
        $response->assertSessionHasErrors();
    }

    /**
     * @test
     * GIVEN : un média existant en base
     * WHEN : on demande ses métadonnées techniques
     * THEN : la structure JSON des métadonnées est retournée
     */
    public function technicalMetadata_returns_json_for_existing_media()
    {
        $media = Media::factory()->create();

        $mock = $this->mock(MediaService::class);
        $mock->shouldReceive('getTechnicalMetadata')
            ->once()
            ->andReturn([
                'duree_format' => '00:05:30',
                'fps' => '25',
                'resolution' => '1920x1080',
                'codec_video' => 'H.264',
                'taille_format' => '150 MB',
                'bitrate' => 8000000,
            ]);

        $response = $this->getJson(route('media.technicalMetadata', $media->id));

        $response->assertStatus(200);
        $response->assertJson([
            'mtdTech' => [
                'mtd_tech_duree' => '00:05:30',
                'mtd_tech_fps' => '25',
                'mtd_tech_resolution' => '1920x1080',
                'mtd_tech_format' => 'H.264',
                'mtd_tech_taille' => '150 MB',
                'mtd_tech_bitrate' => '8000 kbps',
            ],
        ]);
    }

    /**
     * @test
     * GIVEN : aucun média avec l'identifiant 999999
     * WHEN : on demande ses métadonnées techniques
     * THEN : une erreur 404 est retournée avec mtdTech à null
     */
    public function technicalMetadata_returns_404_for_nonexistent_media()
    {
        $response = $this->getJson(route('media.technicalMetadata', 999999));

        $response->assertStatus(404);
        $response->assertJson(['mtdTech' => null]);
    }

    /**
     * @test
     * GIVEN : un média existant mais sans métadonnées techniques disponibles
     * WHEN : on demande ses métadonnées techniques
     * THEN : les champs retournent tous 'N/A'
     */
    public function technicalMetadata_returns_na_when_no_metadata()
    {
        $media = Media::factory()->create();

        $mock = $this->mock(MediaService::class);
        $mock->shouldReceive('getTechnicalMetadata')
            ->once()
            ->andReturn(null);

        $response = $this->getJson(route('media.technicalMetadata', $media->id));

        $response->assertStatus(200);
        $response->assertJson([
            'mtdTech' => [
                'mtd_tech_duree' => 'N/A',
                'mtd_tech_fps' => 'N/A',
                'mtd_tech_resolution' => 'N/A',
                'mtd_tech_format' => 'N/A',
                'mtd_tech_taille' => 'N/A',
                'mtd_tech_bitrate' => 'N/A',
            ],
        ]);
    }

    /**
     * @test
     * GIVEN : un utilisateur authentifié et la file de jobs simulée
     * WHEN : il lance la synchronisation des médias
     * THEN : trois jobs de synchronisation sont dispatched
     */
    public function sync_dispatches_jobs()
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);
        Queue::fake();

        $response = $this->post(route('admin.media.sync'));

        Queue::assertPushed(SyncMediaFromDiskJob::class, 3);

        $dispatched = [];
        Queue::assertPushed(SyncMediaFromDiskJob::class, function ($job) use (&$dispatched) {
            $dispatched[] = $job->disk;
            return true;
        });
        $this->assertContains('ftp_arch', $dispatched);
        $this->assertContains('ftp_pad', $dispatched);
        $this->assertContains('external_local', $dispatched);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    /**
     * @test
     * GIVEN : un utilisateur authentifié et le service de synchronisation mocké avec succès
     * WHEN : il envoie un chemin local à synchroniser
     * THEN : la synchronisation réussit et un message de confirmation est retourné
     */
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

    /**
     * @test
     * GIVEN : un utilisateur authentifié et le service de synchronisation retournant false
     * WHEN : il envoie un chemin local inexistant à synchroniser
     * THEN : une erreur 404 est retournée avec le message 'Media non trouvé'
     */
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