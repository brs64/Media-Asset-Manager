<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * GIVEN : aucun utilisateur connecté
     * WHEN : on accède à la page de connexion
     * THEN : la page s'affiche avec un statut 200
     */
    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    /**
     * GIVEN : un utilisateur existant en base de données
     * WHEN : il soumet le formulaire de connexion avec des identifiants valides
     * THEN : il est authentifié et redirigé vers le tableau de bord
     */
    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'name' => $user->name,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    /**
     * GIVEN : un utilisateur existant en base de données
     * WHEN : il soumet le formulaire de connexion avec un mot de passe incorrect
     * THEN : il reste non authentifié (invité)
     */
    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'name' => $user->name,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    /**
     * GIVEN : un utilisateur authentifié
     * WHEN : il envoie une requête de déconnexion
     * THEN : il est déconnecté et redirigé vers la page d'accueil
     */
    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
