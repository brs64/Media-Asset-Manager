<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * @brief Affiche le formulaire d'édition du profil utilisateur.
     *
     * Charge les informations actuelles de l'utilisateur connecté
     * et les transmet à la vue pour modification.
     *
     * @param Request $request Requête HTTP contenant l'utilisateur authentifié
     * @return View Vue "profile.edit" avec les données utilisateur
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * @brief Met à jour les informations du profil utilisateur.
     *
     * Valide et enregistre les modifications apportées au profil
     * (nom, email, etc.) via le ProfileUpdateRequest.
     *
     * @param ProfileUpdateRequest $request Requête de mise à jour validée
     * @return RedirectResponse Redirection vers le formulaire avec message de succès
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());
        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * @brief Supprime le compte utilisateur de manière définitive.
     *
     * Fonctionnalités :
     * - Vérifie le mot de passe de l'utilisateur avant suppression
     * - Déconnecte l'utilisateur de la session
     * - Supprime le compte de la base de données
     * - Invalide la session et régénère le token CSRF
     * - Redirige vers la page d'accueil
     *
     * @param Request $request Requête HTTP contenant le mot de passe de confirmation
     * @return RedirectResponse Redirection vers la page d'accueil
     *
     * @throws \Illuminate\Validation\ValidationException
     * Si le mot de passe est manquant ou incorrect
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ], [
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.current_password' => 'Le mot de passe est incorrect.',
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
