<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Projet;
use App\Models\Professeur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class SearchMediaTest extends TestCase
{
    //TEST POUR LA RECHERCHE DE MEDIA DANS LA BARRE DE RECHERCHE
    use RefreshDatabase;

    /**
     * Ptite fonction helper pour logger proprement dans le terminal
     */
    private function logTest($message) {
        fwrite(STDOUT, "\n[DEBUG] " . $message . "\n");
    }

    #[Test]
    public function devrait_afficher_un_media_lorsque_le_titre_correspond_au_mot_cle()
    {
        $this->logTest("Début test : Recherche par mot-clé");
        
        Media::create([
            'mtd_tech_titre' => 'Le tutoriel Laravel',
            'type' => 'video',
            'promotion' => '2026',
            'chemin_local' => '/local/tutoriel.mp4',
        ]);

        $this->logTest("Action : GET /recherche?description=tutoriel");
        $response = $this->get(route('search', ['description' => 'tutoriel']));

        $response->assertStatus(200);
        $response->assertSee('Le tutoriel Laravel');
        $this->logTest("Succès : Média trouvé via mot-clé.");
    }

    #[Test]
    public function devrait_filtrer_les_medias_par_projet()
    {
        $this->logTest("Début test : Filtrage par Projet");

        $projet = Projet::create(['libelle' => 'Projet Web Série']);
        $media = Media::create([
            'mtd_tech_titre' => 'Video Projet A',
            'type' => 'video',
            'promotion' => '2026',
            'chemin_local' => '/local/projetA.mp4',
        ]);
        $media->projets()->attach($projet->id);
        $this->logTest("Média lié au projet ID: " . $projet->id);

        Media::create(['mtd_tech_titre' => 'Video Hors Projet', 'type' => 'video', 'promotion' => '2026', 'chemin_local' => '/local/hors.mp4']);

        $this->logTest("Action : GET /recherche?projet=" . $projet->id);
        $response = $this->get(route('search', ['projet' => $projet->id]));

        $response->assertStatus(200);
        $response->assertSee('Video Projet A');
        $response->assertDontSee('Video Hors Projet');
        $this->logTest("Succès : Seule la vidéo du projet est affichée.");
    }
#[Test]
public function devrait_trouver_un_media_en_tapant_le_nom_du_prof_dans_la_barre_de_recherche()
{
    $this->logTest("Début test : Recherche Nom Prof dans Keyword");

    $user = User::factory()->create();
    $prof = Professeur::create([
        'nom' => 'Martin', 
        'prenom' => 'Sophie',
        'user_id' => $user->id 
    ]);
    
    Media::create([
        'mtd_tech_titre' => 'Video de Sophie',
        'professeur_id' => $prof->id,
        'type' => 'video',
        'promotion' => '2026',
        'chemin_local' => '/local/sophie.mp4',
    ]);

    $this->logTest("Action : GET /recherche?description=Martin");
    $response = $this->get(route('search', ['description' => 'Martin']));

    $response->assertStatus(200);
    $response->assertSee('Video de Sophie');
    $this->logTest("Succès : Le média a été trouvé via le nom du prof dans le mot-clé.");
}
    #[Test]
    public function devrait_trouver_un_media_en_cherchant_le_nom_du_professeur()
    {
        $this->logTest("Début test : Recherche par nom de professeur via mot-clé");

        $user = User::factory()->create();
        $prof = Professeur::create([
            'nom' => 'Dupont',
            'prenom' => 'Jean',
            'user_id' => $user->id
        ]);

        Media::create([
            'mtd_tech_titre' => 'Cours de Montage',
            'professeur_id' => $prof->id,
            'type' => 'video',
            'promotion' => '2026',
            'chemin_local' => '/local/montage.mp4',
        ]);

        Media::create([
            'mtd_tech_titre' => 'Autre Video',
            'type' => 'video',
            'promotion' => '2026',
            'chemin_local' => '/local/autre.mp4',
        ]);

        $this->logTest("Action : GET /recherche?description=Dupont");
        $response = $this->get(route('search', ['description' => 'Dupont']));

        $response->assertStatus(200);
        $response->assertSee('Cours de Montage');
        $response->assertDontSee('Autre Video');
        $this->logTest("Succès : Recherche par nom de professeur OK.");
    }

    #[Test]
    public function devrait_filtrer_les_medias_par_promotion()
    {
        $this->logTest("Début test : Filtrage par Promotion");

        Media::create(['mtd_tech_titre' => 'Film 2024', 'promotion' => '2024', 'type' => 'video', 'chemin_local' => '/local/2024.mp4']);
        Media::create(['mtd_tech_titre' => 'Film 2026', 'promotion' => '2026', 'type' => 'video', 'chemin_local' => '/local/2026.mp4']);

        $this->logTest("Action : GET /recherche?promotion=2024");
        $response = $this->get(route('search', ['promotion' => '2024']));

        $response->assertStatus(200);
        $response->assertSee('Film 2024');
        $response->assertDontSee('Film 2026');
        $this->logTest("Succès : Filtrage promotion strict OK.");
    }
}