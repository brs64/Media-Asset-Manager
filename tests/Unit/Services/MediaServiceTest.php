<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\MediaService;
use App\Models\Media;
use App\Models\Professeur;
use App\Models\Projet;
use App\Models\Eleve;
use App\Models\Role;
use App\Models\Participation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MediaServiceTest extends TestCase
{
    use RefreshDatabase;

    protected MediaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MediaService();
    }

    /**
     * @test
     * GIVEN : un nom de fichier au format standard 'année_projet_titre.mp4'
     * WHEN : on appelle extractVideoTitle avec ce nom
     * THEN : seul le titre 'TitreVideo' est extrait
     */
    public function extractVideoTitle_extracts_title_from_standard_format()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('extractVideoTitle');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, '2024_MonProjet_TitreVideo.mp4');

        $this->assertEquals('TitreVideo', $result);
    }

    /**
     * @test
     * GIVEN : un nom de fichier sans le format standard (pas de underscores)
     * WHEN : on appelle extractVideoTitle avec ce nom
     * THEN : le nom du fichier sans extension est retourné
     */
    public function extractVideoTitle_returns_filename_when_no_pattern_match()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('extractVideoTitle');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 'SimpleVideo.mp4');

        $this->assertEquals('SimpleVideo', $result);
    }

    /**
     * @test
     * GIVEN : des noms complets au format 'Nom Prénom' simples et composés
     * WHEN : on appelle extractFirstName avec ces noms
     * THEN : le prénom (dernier mot) est correctement extrait
     */
    public function extractFirstName_extracts_first_name_correctly()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('extractFirstName');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 'Dupont Jean');
        $this->assertEquals('Jean', $result);

        $result = $method->invoke($this->service, 'Martin De La Pierre Jacques');
        $this->assertEquals('Jacques', $result);
    }

    /**
     * @test
     * GIVEN : des noms complets simples et avec nom de famille composé
     * WHEN : on appelle extractLastName avec ces noms
     * THEN : le nom de famille (tout sauf le dernier mot) est correctement extrait
     */
    public function extractLastName_extracts_last_name_correctly()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('extractLastName');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 'Dupont Jean');
        $this->assertEquals('Dupont', $result);

        $result = $method->invoke($this->service, 'Martin De La Pierre Jacques');
        $this->assertEquals('Martin De La Pierre', $result);
    }

    /**
     * @test
     * GIVEN : un nom composé d'un seul mot
     * WHEN : on appelle extractLastName avec ce nom
     * THEN : le mot unique est retourné tel quel
     */
    public function extractLastName_returns_full_name_when_single_word()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('extractLastName');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 'Unique');

        $this->assertEquals('Unique', $result);
    }

    /**
     * @test
     * GIVEN : un média avec une participation associée en base
     * WHEN : on appelle deleteMedia avec l'id du média
     * THEN : le média et ses participations sont supprimés de la base
     */
    public function deleteMedia_deletes_media_and_participations()
    {
        $media = Media::factory()->create();
        $eleve = Eleve::factory()->create();
        $role = Role::factory()->create();

        Participation::create([
            'media_id' => $media->id,
            'eleve_id' => $eleve->id,
            'role_id' => $role->id,
        ]);

        $result = $this->service->deleteMedia($media->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('medias', ['id' => $media->id]);
        $this->assertDatabaseMissing('participations', ['media_id' => $media->id]);
    }

    /**
     * @test
     * GIVEN : un identifiant de média inexistant
     * WHEN : on appelle deleteMedia avec cet identifiant
     * THEN : false est retourné et la transaction est annulée
     */
    public function deleteMedia_rolls_back_on_error()
    {
        $result = $this->service->deleteMedia(999999);

        $this->assertFalse($result);
    }

    /**
     * @test
     * GIVEN : un média existant et une liste de deux élèves
     * WHEN : on appelle assignRoles avec le rôle 'Réalisateur'
     * THEN : le rôle, les élèves et les participations sont créés en base
     */
    public function assignRoles_creates_roles_and_participations()
    {
        $media = Media::factory()->create();

        $this->service->assignRoles($media->id, 'Réalisateur', ['Dupont Jean', 'Martin Sophie']);

        $this->assertDatabaseHas('roles', ['libelle' => 'Réalisateur']);
        $this->assertDatabaseHas('eleves', ['nom' => 'Dupont', 'prenom' => 'Jean']);
        $this->assertDatabaseHas('eleves', ['nom' => 'Martin', 'prenom' => 'Sophie']);

        $role = Role::where('libelle', 'Réalisateur')->first();
        $this->assertEquals(2, Participation::where('media_id', $media->id)->where('role_id', $role->id)->count());
    }

    /**
     * @test
     * GIVEN : un média avec une participation existante pour un élève et un rôle
     * WHEN : on réassigne le même rôle au même élève
     * THEN : aucune participation en double n'est créée
     */
    public function assignRoles_updates_existing_participation()
    {
        $media = Media::factory()->create();
        $eleve = Eleve::factory()->create(['nom' => 'Dupont', 'prenom' => 'Jean']);
        $role = Role::factory()->create(['libelle' => 'Réalisateur']);

        Participation::create([
            'media_id' => $media->id,
            'eleve_id' => $eleve->id,
            'role_id' => $role->id,
        ]);

        // Reassign same role
        $this->service->assignRoles($media->id, 'Réalisateur', ['Dupont Jean']);

        $this->assertEquals(1, Participation::where('media_id', $media->id)->count());
    }

    /**
     * @test
     * GIVEN : deux médias en base dont un avec le titre 'Video Test 123'
     * WHEN : on recherche avec le mot-clé 'Test'
     * THEN : seul le média correspondant est retourné
     */
    public function searchMedia_finds_by_title()
    {
        Media::factory()->create(['mtd_tech_titre' => 'Video Test 123', 'chemin_local' => '/local/test.mp4']);
        Media::factory()->create(['mtd_tech_titre' => 'Another Video', 'chemin_local' => '/local/other.mp4']);

        $results = $this->service->searchMedia(['keyword' => 'Test']);

        $this->assertCount(1, $results);
        $this->assertEquals('Video Test 123', $results->first()->mtd_tech_titre);
    }

    /**
     * @test
     * GIVEN : deux médias en base dont un avec 'description test' dans sa description
     * WHEN : on recherche avec le mot-clé 'description test'
     * THEN : seul le média correspondant est retourné
     */
    public function searchMedia_finds_by_description()
    {
        Media::factory()->create(['description' => 'Ceci est une description test', 'chemin_local' => '/local/test.mp4']);
        Media::factory()->create(['description' => 'Autre description', 'chemin_local' => '/local/other.mp4']);

        $results = $this->service->searchMedia(['keyword' => 'description test']);

        $this->assertCount(1, $results);
        $this->assertStringContainsString('description test', $results->first()->description);
    }

    /**
     * @test
     * GIVEN : deux médias avec des thèmes différents ('Documentaire' et 'Fiction')
     * WHEN : on recherche avec le mot-clé 'Documentaire'
     * THEN : seul le média avec le thème 'Documentaire' est retourné
     */
    public function searchMedia_finds_by_theme()
    {
        Media::factory()->create(['theme' => 'Documentaire', 'chemin_local' => '/local/doc.mp4']);
        Media::factory()->create(['theme' => 'Fiction', 'chemin_local' => '/local/fic.mp4']);

        $results = $this->service->searchMedia(['keyword' => 'Documentaire']);

        $this->assertCount(1, $results);
        $this->assertEquals('Documentaire', $results->first()->theme);
    }

    /**
     * @test
     * GIVEN : un média associé au professeur 'Dupont' et un autre sans professeur
     * WHEN : on recherche avec le mot-clé 'Dupont'
     * THEN : seul le média du professeur Dupont est retourné
     */
    public function searchMedia_finds_by_professor_name()
    {
        $user = User::factory()->create();
        $prof = Professeur::factory()->create([
            'user_id' => $user->id,
            'nom' => 'Dupont',
            'prenom' => 'Jean',
        ]);
        Media::factory()->create(['professeur_id' => $prof->id, 'chemin_local' => '/local/prof.mp4']);
        Media::factory()->create(['chemin_local' => '/local/other.mp4']);

        $results = $this->service->searchMedia(['keyword' => 'Dupont']);

        $this->assertCount(1, $results);
        $this->assertEquals($prof->id, $results->first()->professeur_id);
    }

    /**
     * @test
     * GIVEN : un projet associé à un seul média parmi deux en base
     * WHEN : on filtre par projet sans mot-clé
     * THEN : seul le média du projet est retourné
     */
    public function searchMedia_filters_by_project_id()
    {
        $projet = Projet::factory()->create();
        $media1 = Media::factory()->create(['chemin_local' => '/local/a.mp4']);
        $media2 = Media::factory()->create(['chemin_local' => '/local/b.mp4']);

        $media1->projets()->attach($projet->id);

        $results = $this->service->searchMedia(['projet' => $projet->id]);

        $this->assertCount(1, $results);
        $this->assertEquals($media1->id, $results->first()->id);
    }

    /**
     * @test
     * GIVEN : un média associé au professeur 'Lemaire' et un autre sans professeur
     * WHEN : on recherche avec le mot-clé 'Lemaire'
     * THEN : seul le média du professeur est retourné
     */
    public function searchMedia_filters_by_professor_name_via_keyword()
    {
        $user = User::factory()->create();
        $prof = Professeur::factory()->create(['user_id' => $user->id, 'nom' => 'Lemaire', 'prenom' => 'Paul']);
        Media::factory()->create(['professeur_id' => $prof->id, 'chemin_local' => '/local/a.mp4']);
        Media::factory()->create(['chemin_local' => '/local/b.mp4']);

        $results = $this->service->searchMedia(['keyword' => 'Lemaire']);

        $this->assertCount(1, $results);
        $this->assertEquals($prof->id, $results->first()->professeur_id);
    }

    /**
     * @test
     * GIVEN : deux médias avec des promotions différentes ('2024' et '2023')
     * WHEN : on filtre par promotion '2024' sans mot-clé
     * THEN : seul le média de la promotion 2024 est retourné
     */
    public function searchMedia_filters_by_promotion()
    {
        Media::factory()->create(['promotion' => '2024', 'chemin_local' => '/local/a.mp4']);
        Media::factory()->create(['promotion' => '2023', 'chemin_local' => '/local/b.mp4']);

        $results = $this->service->searchMedia(['promotion' => '2024']);

        $this->assertCount(1, $results);
        $this->assertEquals('2024', $results->first()->promotion);
    }

    /**
     * @test
     * GIVEN : trois médias avec différentes promotions et thèmes
     * WHEN : on combine le mot-clé 'Documentaire' avec le filtre promotion '2024'
     * THEN : seul le média Documentaire de 2024 est retourné
     */
    public function searchMedia_combines_promotion_and_keyword()
    {
        Media::factory()->create([
            'promotion' => '2024',
            'theme' => 'Documentaire',
            'chemin_local' => '/local/a.mp4',
        ]);
        Media::factory()->create([
            'promotion' => '2024',
            'theme' => 'Fiction',
            'chemin_local' => '/local/b.mp4',
        ]);
        Media::factory()->create([
            'promotion' => '2023',
            'theme' => 'Documentaire',
            'chemin_local' => '/local/c.mp4',
        ]);

        $results = $this->service->searchMedia([
            'keyword' => 'Documentaire',
            'promotion' => '2024',
        ]);

        $this->assertCount(1, $results);
        $this->assertEquals('Documentaire', $results->first()->theme);
        $this->assertEquals('2024', $results->first()->promotion);
    }

    /**
     * @test
     * GIVEN : un média existant en base
     * WHEN : on met à jour ses métadonnées avec professeur, projet, description et rôles
     * THEN : toutes les métadonnées sont enregistrées et les relations créées
     */
    public function updateMetadata_updates_media_successfully()
    {
        $media = Media::factory()->create();

        $result = $this->service->updateMetadata(
            $media->id,
            'Dupont Jean',
            '2024',
            'Mon Projet',
            'Description test',
            ['Réalisateur' => 'Martin Sophie, Durand Pierre']
        );

        $this->assertTrue($result);

        $media->refresh();
        $this->assertEquals('2024', $media->promotion);
        $this->assertEquals('Description test', $media->description);
        $this->assertNotNull($media->professeur_id);

        $this->assertDatabaseHas('professeurs', ['nom' => 'Dupont', 'prenom' => 'Jean']);
        $this->assertDatabaseHas('projets', ['libelle' => 'Mon Projet']);
        $this->assertDatabaseHas('eleves', ['nom' => 'Martin', 'prenom' => 'Sophie']);
        $this->assertDatabaseHas('eleves', ['nom' => 'Durand', 'prenom' => 'Pierre']);
    }

    /**
     * @test
     * GIVEN : un média existant et un nom de professeur inconnu en base
     * WHEN : on appelle updateMetadata avec ce nom de professeur
     * THEN : le professeur est créé et associé au média
     */
    public function updateMetadata_creates_new_professor_if_not_exists()
    {
        $media = Media::factory()->create();

        $this->service->updateMetadata(
            $media->id,
            'Nouveau Professeur',
            null,
            null,
            null,
            []
        );

        $this->assertDatabaseHas('professeurs', ['nom' => 'Nouveau', 'prenom' => 'Professeur']);

        // Verify professor is linked to media
        $media->refresh();
        $this->assertNotNull($media->professeur_id);
        $this->assertEquals('Nouveau', $media->professeur->nom);
        $this->assertEquals('Professeur', $media->professeur->prenom);
    }

    /**
     * @test
     * GIVEN : un professeur 'Dupont Jean' déjà existant en base
     * WHEN : on appelle updateMetadata avec le même nom de professeur
     * THEN : le professeur existant est réutilisé sans créer de doublon
     */
    public function updateMetadata_reuses_existing_professor()
    {
        $user = User::factory()->create();
        $prof = Professeur::factory()->create([
            'user_id' => $user->id,
            'nom' => 'Dupont',
            'prenom' => 'Jean',
        ]);
        $media = Media::factory()->create();

        $this->service->updateMetadata(
            $media->id,
            'Dupont Jean',
            null,
            null,
            null,
            []
        );

        $media->refresh();
        $this->assertEquals($prof->id, $media->professeur_id);
        $this->assertEquals(1, Professeur::where('nom', 'Dupont')->where('prenom', 'Jean')->count());
    }

    /**
     * @test
     * GIVEN : un identifiant de média inexistant
     * WHEN : on appelle updateMetadata avec cet identifiant
     * THEN : false est retourné et la transaction est annulée
     */
    public function updateMetadata_rolls_back_on_error()
    {
        $result = $this->service->updateMetadata(
            999999,
            'Test Prof',
            null,
            null,
            null,
            []
        );

        $this->assertFalse($result);
    }

    /**
     * @test
     * GIVEN : un média avec une participation existante pour le rôle 'Réalisateur'
     * WHEN : on met à jour les métadonnées avec un nouveau rôle 'Acteur'
     * THEN : l'ancienne participation est supprimée et la nouvelle est créée
     */
    public function updateMetadata_replaces_old_participations()
    {
        $media = Media::factory()->create();
        $eleve = Eleve::factory()->create();
        $role = Role::factory()->create(['libelle' => 'Réalisateur']);

        Participation::create([
            'media_id' => $media->id,
            'eleve_id' => $eleve->id,
            'role_id' => $role->id,
        ]);

        $this->service->updateMetadata(
            $media->id,
            'Test Prof',
            null,
            null,
            null,
            ['Acteur' => 'Nouveau Participant']
        );

        $this->assertEquals(0, Participation::where('media_id', $media->id)->where('role_id', $role->id)->count());
        $this->assertDatabaseHas('roles', ['libelle' => 'Acteur']);
    }

    /**
     * @test
     * GIVEN : un média existant avec le titre 'TestVideo'
     * WHEN : on appelle syncLocalPath avec un chemin contenant 'TestVideo.mp4'
     * THEN : le chemin local du média est mis à jour en base
     * NOTE: syncLocalPath uses PATHINFO_FILENAME which removes extension
     */
    public function syncLocalPath_updates_existing_media()
    {
        $media = Media::factory()->create(['mtd_tech_titre' => 'TestVideo']);

        $result = $this->service->syncLocalPath('videos/local/TestVideo.mp4');

        $this->assertTrue($result);

        $media->refresh();
        $this->assertEquals('videos/local/TestVideo.mp4', $media->chemin_local);
    }

    /**
     * @test
     * GIVEN : un média avec le titre 'TestVideo' en base
     * WHEN : on appelle syncLocalPath avec 'testvideo.mp4' en minuscules
     * THEN : le média est trouvé et son chemin local est mis à jour
     * NOTE: syncLocalPath uses PATHINFO_FILENAME which removes extension
     */
    public function syncLocalPath_is_case_insensitive()
    {
        $media = Media::factory()->create(['mtd_tech_titre' => 'TestVideo']);

        $result = $this->service->syncLocalPath('videos/local/testvideo.mp4');

        $this->assertTrue($result);

        $media->refresh();
        $this->assertEquals('videos/local/testvideo.mp4', $media->chemin_local);
    }

    /**
     * @test
     * GIVEN : aucun média correspondant au fichier en base
     * WHEN : on appelle syncLocalPath avec un chemin vers un fichier inconnu
     * THEN : false est retourné
     */
    public function syncLocalPath_returns_false_when_media_not_found()
    {
        $result = $this->service->syncLocalPath('videos/nonexistent.mp4');

        $this->assertFalse($result);
    }

    /**
     * @test
     * GIVEN : un identifiant de média inexistant
     * WHEN : on appelle getMediaInfo avec cet identifiant
     * THEN : null est retourné
     */
    public function getMediaInfo_returns_null_for_nonexistent_media()
    {
        $result = $this->service->getMediaInfo(999999);

        $this->assertNull($result);
    }

    /**
     * @test
     * GIVEN : un média complet avec professeur, projet, élève et participation
     * WHEN : on appelle getMediaInfo avec l'id du média
     * THEN : toutes les informations sont retournées dans le format attendu
     */
    public function getMediaInfo_returns_complete_information()
    {
        $user = User::factory()->create();
        $prof = Professeur::factory()->create(['user_id' => $user->id, 'nom' => 'Dupont', 'prenom' => 'Jean']);
        $projet = Projet::factory()->create(['libelle' => 'Mon Projet']);
        $media = Media::factory()->create([
            'mtd_tech_titre' => '2024_MonProjet_TitreTest.mp4',
            'description' => 'Description test',
            'promotion' => '2024',
            'professeur_id' => $prof->id,
        ]);

        $media->projets()->attach($projet->id);

        $eleve = Eleve::factory()->create(['nom' => 'Martin', 'prenom' => 'Sophie']);
        $role = Role::factory()->create(['libelle' => 'Réalisateur']);
        Participation::create([
            'media_id' => $media->id,
            'eleve_id' => $eleve->id,
            'role_id' => $role->id,
        ]);

        $info = $this->service->getMediaInfo($media->id);

        $this->assertNotNull($info);
        $this->assertEquals($media->id, $info['idMedia']);
        $this->assertEquals('2024_MonProjet_TitreTest.mp4', $info['nomFichier']);
        $this->assertEquals('TitreTest', $info['titreVideo']);
        $this->assertEquals('Description test', $info['description']);
        $this->assertEquals('2024', $info['promotion']);
        $this->assertEquals('Jean Dupont', $info['mtdEdito']['professeur']);
        $this->assertEquals('Mon Projet', $info['mtdEdito']['projet']);
        $this->assertStringContainsString('Sophie Martin', $info['mtdEdito']['eleves']);
    }

    /**
     * @test
     * GIVEN : 30 médias en base
     * WHEN : on appelle getRecentMedia avec une limite de 10
     * THEN : seuls 10 médias sont retournés
     */
    public function getRecentMedia_returns_limited_results()
    {
        Media::factory()->count(30)->create();

        $results = $this->service->getRecentMedia(10);

        $this->assertCount(10, $results);
    }

    /**
     * @test
     * GIVEN : un média ancien et un média récent en base
     * WHEN : on appelle getRecentMedia
     * THEN : le média le plus récent est en première position
     */
    public function getRecentMedia_returns_most_recent_first()
    {
        $old = Media::factory()->create(['updated_at' => now()->subDays(5)]);
        $recent = Media::factory()->create(['updated_at' => now()]);

        $results = $this->service->getRecentMedia(10);

        $this->assertCount(2, $results);
        $this->assertEquals($recent->id, $results[0]['id']);
        $this->assertEquals($old->id, $results[1]['id']);
    }

    /**
     * @test
     * GIVEN : un texte contenant des sauts de ligne et retours chariot
     * WHEN : on appelle sanitizeForDisplay avec ce texte
     * THEN : tous les sauts de ligne sont remplacés par des espaces
     */
    public function sanitizeForDisplay_removes_newlines()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('sanitizeForDisplay');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, "Text with\nnewlines\rand\r\ncarriage returns");

        $this->assertEquals('Text with newlines and carriage returns', $result);
    }

    /**
     * @test
     * GIVEN : une valeur null
     * WHEN : on appelle sanitizeForDisplay avec null
     * THEN : une chaîne vide est retournée
     */
    public function sanitizeForDisplay_handles_null_values()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('sanitizeForDisplay');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, null);

        $this->assertEquals('', $result);
    }

    /**
     * @test
     * GIVEN : des tailles de fichiers en octets (500, 1024, 1048576, 1536000)
     * WHEN : on appelle formatFileSize avec chaque taille
     * THEN : les tailles sont formatées en unités lisibles (B, KB, MB)
     */
    public function formatFileSize_formats_bytes_correctly()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('formatFileSize');
        $method->setAccessible(true);

        $this->assertEquals('1 KB', $method->invoke($this->service, 1024));
        $this->assertEquals('1 MB', $method->invoke($this->service, 1048576));
        $this->assertEquals('1.46 MB', $method->invoke($this->service, 1536000));
        $this->assertEquals('500 B', $method->invoke($this->service, 500));
    }

    /**
     * @test
     * GIVEN : un média avec un chemin local défini
     * WHEN : on appelle clearLocalFiles
     * THEN : les fichiers vidéo et miniature sont supprimés et true est retourné
     */
    public function clearLocalFiles_deletes_video_and_thumbnail()
    {
        Storage::fake('local');

        $media = Media::factory()->create(['chemin_local' => 'videos/test.mp4']);

        $result = $this->service->clearLocalFiles($media->id);

        $this->assertTrue($result);
    }

    /**
     * @test
     * GIVEN : un média sans chemin local
     * WHEN : on appelle clearLocalFiles
     * THEN : true est retourné sans tenter de suppression
     */
    public function clearLocalFiles_returns_true_when_no_local_path()
    {
        $media = Media::factory()->create(['chemin_local' => null]);

        $result = $this->service->clearLocalFiles($media->id);

        $this->assertTrue($result);
    }

    /**
     * @test
     * GIVEN : un identifiant de média inexistant
     * WHEN : on appelle clearLocalFiles
     * THEN : false est retourné
     */
    public function clearLocalFiles_returns_false_on_error()
    {
        $result = $this->service->clearLocalFiles(999999);

        $this->assertFalse($result);
    }

    /**
     * @test
     * GIVEN : un média complet avec professeur, projets et participations
     * WHEN : on appelle getMediaInfo
     * THEN : les rôles sont formatés en tableau associatif [role => "noms"]
     */
    public function getMediaInfo_formats_participations_by_role()
    {
        $user = User::factory()->create();
        $prof = Professeur::factory()->create(['user_id' => $user->id, 'nom' => 'Dupont', 'prenom' => 'Jean']);
        $media = Media::factory()->create([
            'mtd_tech_titre' => 'TestVideo.mp4',
            'professeur_id' => $prof->id,
        ]);

        $role = Role::factory()->create(['libelle' => 'Réalisateur']);
        $eleve1 = Eleve::factory()->create(['nom' => 'Martin', 'prenom' => 'Sophie']);
        $eleve2 = Eleve::factory()->create(['nom' => 'Durand', 'prenom' => 'Pierre']);

        Participation::create(['media_id' => $media->id, 'eleve_id' => $eleve1->id, 'role_id' => $role->id]);
        Participation::create(['media_id' => $media->id, 'eleve_id' => $eleve2->id, 'role_id' => $role->id]);

        $info = $this->service->getMediaInfo($media->id);

        $this->assertArrayHasKey('mtdRoles', $info);
        $this->assertArrayHasKey('Réalisateur', $info['mtdRoles']);
        $this->assertStringContainsString('Martin', $info['mtdRoles']['Réalisateur']);
        $this->assertStringContainsString('Durand', $info['mtdRoles']['Réalisateur']);
    }

    /**
     * @test
     * GIVEN : un média avec des propriétés personnalisées JSON
     * WHEN : on appelle getMediaInfo
     * THEN : les propriétés sont retournées dans mtdCustom avec label et value nettoyés
     */
    public function getMediaInfo_returns_custom_properties()
    {
        $media = Media::factory()->create([
            'mtd_tech_titre' => 'TestCustom.mp4',
            'properties' => ['camera' => 'Sony A7', 'lieu' => "Paris\nFrance"],
        ]);

        $info = $this->service->getMediaInfo($media->id);

        $this->assertArrayHasKey('mtdCustom', $info);
        $this->assertCount(2, $info['mtdCustom']);

        $labels = collect($info['mtdCustom'])->pluck('label')->all();
        $this->assertContains('camera', $labels);
        $this->assertContains('lieu', $labels);

        $lieuProp = collect($info['mtdCustom'])->firstWhere('label', 'lieu');
        $this->assertStringNotContainsString("\n", $lieuProp['value']);
    }

    /**
     * @test
     * GIVEN : un média avec chemin local, ARCH et PAD
     * WHEN : on appelle getMediaInfo
     * THEN : la source vidéo est 'local' (priorité la plus haute)
     */
    public function getMediaInfo_prioritizes_local_source()
    {
        $media = Media::factory()->create([
            'mtd_tech_titre' => 'Multi.mp4',
            'chemin_local' => '/local/video.mp4',
            'URI_NAS_ARCH' => '/arch/video.mp4',
            'URI_NAS_PAD' => '/pad/video.mp4',
        ]);

        $info = $this->service->getMediaInfo($media->id);
        $this->assertEquals('local', $info['sourceVideo']);
    }

    /**
     * @test
     * GIVEN : un média avec seulement un chemin ARCH
     * WHEN : on appelle getMediaInfo
     * THEN : la source vidéo est 'arch'
     */
    public function getMediaInfo_falls_back_to_arch()
    {
        $media = Media::factory()->create([
            'mtd_tech_titre' => 'ArchOnly.mp4',
            'chemin_local' => null,
            'URI_NAS_ARCH' => '/arch/video.mp4',
            'URI_NAS_PAD' => null,
        ]);

        $info = $this->service->getMediaInfo($media->id);
        $this->assertEquals('arch', $info['sourceVideo']);
    }

    /**
     * @test
     * GIVEN : un média sans aucun chemin
     * WHEN : on appelle getMediaInfo
     * THEN : la source vidéo est null
     */
    public function getMediaInfo_returns_null_source_when_no_paths()
    {
        $media = Media::factory()->create([
            'mtd_tech_titre' => 'NoPaths.mp4',
            'chemin_local' => null,
            'URI_NAS_ARCH' => null,
            'URI_NAS_PAD' => null,
        ]);

        $info = $this->service->getMediaInfo($media->id);
        $this->assertNull($info['sourceVideo']);
    }
}
