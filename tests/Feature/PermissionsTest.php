<?php

namespace Tests\Feature;

use App\Models\Professeur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);
        \Spatie\Permission\Models\Role::create(['name' => 'professeur']);
    }

    /**
     * @test
     * GIVEN : un utilisateur avec le rôle professeur
     * WHEN : il accède à la page des réglages admin
     * THEN : l'accès est autorisé (statut 200)
     */
    public function un_professeur_peut_acceder_aux_reglages_admin()
    {
        $user = User::factory()->create();
        $user->assignRole('professeur');
        Professeur::factory()->create(['user_id' => $user->id]);

        // Professeurs can access admin (checkAdminAccess allows them)
        $response = $this->actingAs($user)->get('/admin/settings');

        $response->assertStatus(200);
    }

    /**
     * @test
     * GIVEN : un utilisateur sans aucun rôle assigné
     * WHEN : il tente d'accéder à la page des réglages admin
     * THEN : l'accès est refusé (statut 403)
     */
    public function un_utilisateur_sans_role_ne_peut_pas_acceder_a_ladmin()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin/settings');

        $response->assertStatus(403);
    }

    /**
     * @test
     * GIVEN : un utilisateur avec le rôle admin
     * WHEN : il accède à la page des réglages admin
     * THEN : l'accès est autorisé (statut 200)
     */
    public function l_admin_peut_acceder_aux_reglages()
    {
        $adminUser = User::factory()->create();
        $adminUser->assignRole('admin');

        $response = $this->actingAs($adminUser)->get('/admin/settings');

        $response->assertStatus(200);
    }
}
