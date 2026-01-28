<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class MediaManagerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TEST : SCRUM-28 - Modification métadonnées (Prof)
     * Je veux tester la méthode d'édition pour m'assurer que les corrections 
     * apportées par un professeur sont bien persistées en base de données.
     */
    public function test_professor_can_update_video_metadata()
    {
        // Simulation d'un utilisateur avec le rôle Professeur
        $prof = User::factory()->create(['role' => 'professeur']);
        
        // On tente de modifier les informations d'une vidéo (Action à tester)
        $response = $this->actingAs($prof)->put('/videos/1', [
            'title' => 'Nouveau Titre BTSPlay',
            'description' => 'Description mise à jour'
        ]);

        // Résultat attendu : Les enrichissements sont sauvegardés
        $response->assertStatus(200);
        $this->assertDatabaseHas('videos', ['title' => 'Nouveau Titre BTSPlay']);
    }

    /**
     * TEST : SCRUM-31 - Déplacement de vidéo (Prof)
     * Je veux tester la méthode de déplacement pour vérifier que la vidéo 
     * devient effectivement disponible pour le public cible (diffusion).
     */
    public function test_video_is_dispatched_to_diffusion_server()
    {
        $prof = User::factory()->create(['role' => 'professeur']);

        // Action : Déplacer vers le NAS de diffusion
        $response = $this->actingAs($prof)->post('/videos/1/move-to-diffusion');

        // Résultat attendu : Disponibilité pour le public
        $response->assertStatus(200);
        // On vérifie que le statut de diffusion a changé
        $this->assertDatabaseHas('videos', ['status' => 'public']);
    }

    /**
     * TEST : SCRUM-33 - Sauvegarde BDD (Admin)
     * Je veux tester la méthode de backup pour m'assurer que les données 
     * sont sécurisées contre la perte.
     */
    public function test_database_backup_execution()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Action : Lancer la sauvegarde de la base de données
        $response = $this->actingAs($admin)->post('/admin/backup');

        // Résultat attendu : Les données sont sécurisées
        $response->assertStatus(200);
    }
}