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

    /** @test */
    public function index_displays_search_page_without_results_when_no_query()
    {
        $response = $this->get(route('recherche.index'));

        $response->assertStatus(200);
        $response->assertViewIs('recherche');
        $response->assertViewHas(['medias', 'listeProjet', 'listeProf', 'description']);
    }

    /** @test */
    public function index_searches_with_description_parameter()
    {
        $media = Media::factory()->create(['description' => 'Test description unique']);
        Media::factory()->count(3)->create();

        $response = $this->get(route('recherche.index', ['description' => 'unique']));

        $response->assertStatus(200);
        $response->assertViewIs('recherche');

        $medias = $response->viewData('medias');
        $this->assertCount(1, $medias);
        $this->assertEquals($media->id, $medias->first()->id);
    }

    /** @test */
    public function index_searches_with_motCle_parameter()
    {
        $media = Media::factory()->create(['mtd_tech_titre' => 'VideoTest123']);
        Media::factory()->count(3)->create();

        $response = $this->get(route('recherche.index', ['motCle' => 'VideoTest123']));

        $response->assertStatus(200);

        $medias = $response->viewData('medias');
        $this->assertCount(1, $medias);
        $this->assertEquals($media->id, $medias->first()->id);
    }

    /** @test */
    public function index_prioritizes_description_over_motCle()
    {
        $media = Media::factory()->create(['description' => 'SearchTerm']);

        $response = $this->get(route('recherche.index', [
            'description' => 'SearchTerm',
            'motCle' => 'OtherTerm',
        ]));

        $description = $response->viewData('description');
        $this->assertEquals('SearchTerm', $description);
    }

    /** @test */
    public function index_loads_projets_and_professeurs_for_filters()
    {
        Projet::factory()->count(3)->create();
        Professeur::factory()->count(5)->create();

        $response = $this->get(route('recherche.index'));

        $response->assertStatus(200);

        $projets = $response->viewData('listeProjet');
        $profs = $response->viewData('listeProf');

        $this->assertCount(3, $projets);
        $this->assertCount(5, $profs);
    }

    /** @test */
    public function index_paginates_results()
    {
        Media::factory()->count(25)->create(['description' => 'Common description']);

        $response = $this->get(route('recherche.index', ['description' => 'Common']));

        $medias = $response->viewData('medias');
        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $medias);
    }

    /** @test */
    public function index_appends_query_parameters_to_pagination()
    {
        Media::factory()->count(25)->create(['description' => 'Test']);

        $response = $this->get(route('recherche.index', ['description' => 'Test']));

        $medias = $response->viewData('medias');
        $this->assertStringContainsString('description=Test', $medias->url(2));
    }

    /** @test */
    public function autocomplete_returns_matching_titles()
    {
        Media::factory()->create(['mtd_tech_titre' => 'DocumentaireNature']);
        Media::factory()->create(['mtd_tech_titre' => 'DocumentaireHistoire']);
        Media::factory()->create(['mtd_tech_titre' => 'FictionDrame']);

        $response = $this->getJson(route('recherche.autocomplete', ['term' => 'Documentaire']));

        $response->assertStatus(200);
        $response->assertJsonCount(2);
        $response->assertJsonFragment(['DocumentaireNature']);
        $response->assertJsonFragment(['DocumentaireHistoire']);
    }

    /** @test */
    public function autocomplete_limits_results_to_10()
    {
        Media::factory()->count(15)->create(['mtd_tech_titre' => 'TestVideo']);

        $response = $this->getJson(route('recherche.autocomplete', ['term' => 'Test']));

        $response->assertStatus(200);
        $response->assertJsonCount(10);
    }

    /** @test */
    public function autocomplete_returns_empty_array_when_no_matches()
    {
        Media::factory()->count(5)->create();

        $response = $this->getJson(route('recherche.autocomplete', ['term' => 'NonExistentTerm']));

        $response->assertStatus(200);
        $response->assertJsonCount(0);
    }

    /** @test */
    public function autocomplete_handles_empty_term()
    {
        Media::factory()->count(15)->create(['mtd_tech_titre' => 'Video']);

        $response = $this->getJson(route('recherche.autocomplete', ['term' => '']));

        $response->assertStatus(200);
        // Should return up to 10 results
        $response->assertJsonCount(10);
    }

    /** @test */
    public function autocomplete_is_case_insensitive()
    {
        Media::factory()->create(['mtd_tech_titre' => 'VideoTest']);

        $response = $this->getJson(route('recherche.autocomplete', ['term' => 'videotest']));

        $response->assertStatus(200);
        $response->assertJsonFragment(['VideoTest']);
    }

    /** @test */
    public function autocomplete_searches_partial_matches()
    {
        Media::factory()->create(['mtd_tech_titre' => 'MyLongVideoTitle']);

        $response = $this->getJson(route('recherche.autocomplete', ['term' => 'Long']));

        $response->assertStatus(200);
        $response->assertJsonFragment(['MyLongVideoTitle']);
    }

    /** @test */
    public function index_uses_media_service_for_search()
    {
        $media = Media::factory()->create(['mtd_tech_titre' => 'ServiceTest']);

        $mockService = $this->mock(MediaService::class);
        $mockService->shouldReceive('searchMedia')
            ->once()
            ->with(['keyword' => 'ServiceTest'])
            ->andReturn(new \Illuminate\Pagination\LengthAwarePaginator(
                [$media],
                1,
                20
            ));

        $response = $this->get(route('recherche.index', ['description' => 'ServiceTest']));

        $response->assertStatus(200);
    }

    /** @test */
    public function index_returns_all_medias_when_no_search_term()
    {
        Media::factory()->count(10)->create();

        $response = $this->get(route('recherche.index'));

        $medias = $response->viewData('medias');
        $this->assertEquals(10, $medias->total());
    }
}
