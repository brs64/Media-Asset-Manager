<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserController extends Controller
{
    /**
     * Affiche le formulaire de connexion
     */
    public function showLoginForm()
    {
        return view('compte');
    }

    /**
     * Traite la connexion
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'loginUser' => 'required|string',
            'passwordUser' => 'required|string',
        ]);

        // Tenter la connexion avec username ou email
        $fieldType = filter_var($credentials['loginUser'], FILTER_VALIDATE_EMAIL) ? 'email' : 'name';

        if (Auth::attempt([$fieldType => $credentials['loginUser'], 'password' => $credentials['passwordUser']])) {
            $request->session()->regenerate();
            return redirect()->intended('/')->with('success', 'Connexion réussie!');
        }

        return back()->withErrors([
            'loginUser' => 'Les identifiants ne correspondent pas.',
        ])->onlyInput('loginUser');
    }

    /**
     * Déconnexion
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'Déconnexion réussie!');
    }

    /**
     * Affiche le profil de l'utilisateur
     */
    public function profile()
    {
        return view('user.profile', ['user' => Auth::user()]);
    }

    /**
     * Met à jour le profil
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:8|confirmed',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return back()->with('success', 'Profil mis à jour avec succès!');
    }
}
