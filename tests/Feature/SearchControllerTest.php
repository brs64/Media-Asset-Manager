<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\MediaService;
use App\Models\Media;
use App\Models\Projet;
use App\Models\Professeur;
use App\Models\User;

class SearchControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * GIVEN : aucun paramètre de recherche fourni
     * WHEN : on accède à la page de recherche
     * THEN : la page s'affiche avec les variables de vue nécessaires
     */
    public function index_displays_search_page_without_results_when_no_query()
    {
        $response = $this->get(route('search'));

        $response->assertStatus(200);
        $response->assertViewIs('recherche');
        $response->assertViewHas(['medias', 'listeProjet', 'listeProf', 'description']);
    }

    /**
     * @test
     * GIVEN : un média avec une description unique et trois autres médias
     * WHEN : on recherche par le mot 'unique' dans la description
     * THEN : seul le média correspondant est retourné
     */
    public function index_searches_with_description_parameter()
    {
        $media = Media::factory()->create(['description' => 'Test description unique', 'chemin_local' => '/local/unique.mp4']);
        Media::factory()->count(3)->create(['chemin_local' => '/local/other.mp4']);

        $response = $this->get(route('search', ['description' => 'unique']));

        $response->assertStatus(200);
        $response->assertViewIs('recherche');

        $medias = $response->viewData('medias');
        $this->assertCount(1, $medias);
        $this->assertEquals($media->id, $medias->first()->id);
    }

    /**
     * @test
     * GIVEN : un média avec un titre spécifique et trois autres médias
     * WHEN : on recherche par mot-clé correspondant au titre
     * THEN : seul le média correspondant est retourné
     */
    public function index_searches_with_motCle_parameter()
    {
        $media = Media::factory()->create(['mtd_tech_titre' => 'VideoTest123', 'chemin_local' => '/local/test.mp4']);
        Media::factory()->count(3)->create(['chemin_local' => '/local/other.mp4']);

        $response = $this->get(route('search', ['motCle' => 'VideoTest123']));

        $response->assertStatus(200);

        $medias = $response->viewData('medias');
        $this->assertCount(1, $medias);
        $this->assertEquals($media->id, $medias->first()->id);
    }

    /**
     * @test
     * GIVEN : un média avec une description correspondant au terme de recherche
     * WHEN : on fournit à la fois description et motCle comme paramètres
     * THEN : la description est prioritaire sur le mot-clé
     */
    public function index_prioritizes_description_over_motCle()
    {
        $media = Media::factory()->create(['description' => 'SearchTerm', 'chemin_local' => '/local/test.mp4']);

        $response = $this->get(route('search', [
            'description' => 'SearchTerm',
            'motCle' => 'OtherTerm',
        ]));

        $response->assertStatus(200);
        $description = $response->viewData('description');
        $this->assertEquals('SearchTerm', $description);

        $medias = $response->viewData('medias');
        $this->assertEquals(1, $medias->total());
        $this->assertEquals($media->id, $medias->first()->id);
    }

    /**
     * @test
     * GIVEN : 3 projets et 5 professeurs en base de données
     * WHEN : on accède à la page de recherche
     * THEN : les listes de projets et professeurs sont disponibles dans la vue
     */
    public function index_loads_projets_and_professeurs_for_filters()
    {
        Projet::factory()->count(3)->create();
        Professeur::factory()->count(5)->create();

        $response = $this->get(route('search'));

        $response->assertStatus(200);

        $projets = $response->viewData('listeProjet');
        $profs = $response->viewData('listeProf');

        $this->assertCount(3, $projets);
        $this->assertCount(5, $profs);
    }

    /**
     * @test
     * GIVEN : 25 médias avec la même description
     * WHEN : on recherche cette description
     * THEN : les résultats sont paginés via LengthAwarePaginator
     */
    public function index_paginates_results()
    {
        Media::factory()->count(25)->create(['description' => 'Common description', 'chemin_local' => '/local/common.mp4']);

        $response = $this->get(route('search', ['description' => 'Common']));

        $medias = $response->viewData('medias');
        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $medias);
    }

    /**
     * @test
     * GIVEN : 25 médias avec la même description
     * WHEN : on recherche cette description et on consulte la pagination
     * THEN : les paramètres de recherche sont conservés dans les liens de pagination
     */
    public function index_appends_query_parameters_to_pagination()
    {
        Media::factory()->count(25)->create(['description' => 'Test', 'chemin_local' => '/local/test.mp4']);

        $response = $this->get(route('search', ['description' => 'Test']));

        $medias = $response->viewData('medias');
        $this->assertStringContainsString('description=Test', $medias->url(2));
    }

    /**
     * @test
     * GIVEN : trois médias dont deux avec un titre commençant par 'Documentaire'
     * WHEN : on lance l'autocomplétion avec le terme 'Documentaire'
     * THEN : seuls les deux médias correspondants sont retournés
     */
    public function autocomplete_returns_matching_titles()
    {
        $user = User::factory()->create();
        Media::factory()->create(['mtd_tech_titre' => 'DocumentaireNature']);
        Media::factory()->create(['mtd_tech_titre' => 'DocumentaireHistoire']);
        Media::factory()->create(['mtd_tech_titre' => 'FictionDrame']);

        $response = $this->actingAs($user)->getJson(route('search.autocomplete', ['term' => 'Documentaire']));

        $response->assertStatus(200);
        $response->assertJsonCount(2);
        $response->assertJsonFragment(['DocumentaireNature']);
        $response->assertJsonFragment(['DocumentaireHistoire']);
    }

    /**
     * @test
     * GIVEN : 15 médias avec le même titre
     * WHEN : on lance l'autocomplétion avec un terme correspondant
     * THEN : le résultat est limité à 10 éléments maximum
     */
    public function autocomplete_limits_results_to_10()
    {
        $user = User::factory()->create();
        Media::factory()->count(15)->create(['mtd_tech_titre' => 'TestVideo']);

        $response = $this->actingAs($user)->getJson(route('search.autocomplete', ['term' => 'Test']));

        $response->assertStatus(200);
        $response->assertJsonCount(10);
    }

    /**
     * @test
     * GIVEN : 5 médias sans titre correspondant au terme recherché
     * WHEN : on lance l'autocomplétion avec un terme inexistant
     * THEN : un tableau vide est retourné
     */
    public function autocomplete_returns_empty_array_when_no_matches()
    {
        $user = User::factory()->create();
        Media::factory()->count(5)->create();

        $response = $this->actingAs($user)->getJson(route('search.autocomplete', ['term' => 'NonExistentTerm']));

        $response->assertStatus(200);
        $response->assertJsonCount(0);
    }

    /**
     * @test
     * GIVEN : 15 médias en base de données
     * WHEN : on lance l'autocomplétion avec un terme vide
     * THEN : jusqu'à 10 résultats sont retournés
     */
    public function autocomplete_handles_empty_term()
    {
        $user = User::factory()->create();
        Media::factory()->count(15)->create(['mtd_tech_titre' => 'Video']);

        $response = $this->actingAs($user)->getJson(route('search.autocomplete', ['term' => '']));

        $response->assertStatus(200);
        // Should return up to 10 results
        $response->assertJsonCount(10);
    }

    /**
     * @test
     * GIVEN : un média avec un titre en casse mixte
     * WHEN : on recherche le même titre en minuscules
     * THEN : le média est trouvé malgré la différence de casse
     */
    public function autocomplete_is_case_insensitive()
    {
        $user = User::factory()->create();
        Media::factory()->create(['mtd_tech_titre' => 'VideoTest']);

        $response = $this->actingAs($user)->getJson(route('search.autocomplete', ['term' => 'videotest']));

        $response->assertStatus(200);
        $response->assertJsonFragment(['VideoTest']);
    }

    /**
     * @test
     * GIVEN : un média avec un titre long
     * WHEN : on recherche une sous-chaîne du titre
     * THEN : le média est trouvé par correspondance partielle
     */
    public function autocomplete_searches_partial_matches()
    {
        $user = User::factory()->create();
        Media::factory()->create(['mtd_tech_titre' => 'MyLongVideoTitle']);

        $response = $this->actingAs($user)->getJson(route('search.autocomplete', ['term' => 'Long']));

        $response->assertStatus(200);
        $response->assertJsonFragment(['MyLongVideoTitle']);
    }

    /**
     * @test
     * GIVEN : un MediaService mocké configuré pour répondre à une recherche
     * WHEN : on effectue une recherche par description
     * THEN : le service est appelé et la page s'affiche correctement
     */
    public function index_uses_media_service_for_search()
    {
        $media = Media::factory()->create(['mtd_tech_titre' => 'ServiceTest']);

        $mockService = $this->mock(MediaService::class);
        $mockService->shouldReceive('searchMedia')
            ->once()
            ->with(\Mockery::on(fn($arg) => is_array($arg) && ($arg['keyword'] ?? null) === 'ServiceTest'))
            ->andReturn(new \Illuminate\Pagination\LengthAwarePaginator(
                [$media],
                1,
                20
            ));

        $response = $this->get(route('search', ['description' => 'ServiceTest']));

        $response->assertStatus(200);
    }

    /**
     * @test
     * GIVEN : 10 médias en base de données
     * WHEN : on accède à la recherche sans terme de recherche
     * THEN : tous les médias sont retournés
     */
    public function index_returns_all_medias_when_no_search_term()
    {
        Media::factory()->count(10)->create(['chemin_local' => '/local/video.mp4']);

        $response = $this->get(route('search'));

        $medias = $response->viewData('medias');
        $this->assertEquals(10, $medias->total());
    }
}
