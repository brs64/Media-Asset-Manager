<?php

namespace Tests\Feature;

use App\Models\Professeur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function un_professeur_ne_peut_pas_acceder_aux_reglages_admin()
    {
        // 1. Création d'un User classique (le compte)
        $user = User::factory()->create(['is_admin' => false]);

        // 2. Création du Professeur lié à ce compte
        $prof = Professeur::factory()->create(['user_id' => $user->id]);

        // 3. Test de l'accès
        $response = $this->actingAs($user)->get('/admin/settings');
        
        $response->assertStatus(403); // Doit être bloqué
    }

    /** @test */
    public function l_admin_unique_peut_acceder_aux_reglages()
    {
        // On simule l'admin "en dur" via le compte User
        $adminUser = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($adminUser)->get('/admin/settings');

        $response->assertStatus(200); // Doit passer
    }
}