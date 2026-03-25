<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * @brief Affiche le formulaire de connexion.
     *
     * Page de login permettant à l'utilisateur de s'authentifier
     * avec son identifiant et son mot de passe.
     *
     * @return View Vue "auth.login" contenant le formulaire de connexion
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * @brief Traite une requête d'authentification.
     *
     * Fonctionnalités :
     * - Valide les identifiants de connexion (via LoginRequest)
     * - Authentifie l'utilisateur si les credentials sont valides
     * - Régénère l'ID de session pour prévenir les attaques de fixation
     * - Redirige vers la page d'accueil ou la page initialement demandée
     *
     * @param LoginRequest $request Requête d'authentification validée
     * @return RedirectResponse Redirection vers la page d'accueil ou page initialement demandée
     *
     * @throws \Illuminate\Validation\ValidationException
     * Si les identifiants sont invalides ou l'authentification échoue
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('home', absolute: false));
    }

    /**
     * @brief Déconnecte l'utilisateur et détruit la session.
     *
     * Fonctionnalités :
     * - Déconnecte l'utilisateur du guard web
     * - Invalide complètement la session actuelle
     * - Régénère le token CSRF pour prévenir les attaques
     * - Redirige vers la page d'accueil publique
     *
     * @param Request $request Requête HTTP
     * @return RedirectResponse Redirection vers la page d'accueil
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
