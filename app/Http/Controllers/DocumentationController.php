<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DocumentationController extends Controller
{
    /**
     * Vérifie que l'utilisateur est admin ou professeur
     */
    private function checkAdminAccess()
    {
        if (!auth()->check()) {
            abort(403, 'Vous devez être connecté pour accéder à cette page.');
        }

        $user = auth()->user();
        if (!$user->hasRole('admin') && !$user->hasRole('professeur')) {
            abort(403, 'Accès réservé aux administrateurs et professeurs.');
        }
    }

    /**
     * Page d'accueil de la documentation
     */
    public function index()
    {
        return view('documentation.index');
    }

    /**
     * Guide de démarrage
     */
    public function gettingStarted()
    {
        return view('documentation.getting-started');
    }

    /**
     * Interface - Page d'accueil
     */
    public function interfaceHomePage()
    {
        return view('documentation.interface.home-page');
    }

    /**
     * Interface - Barre de navigation
     */
    public function interfaceNavbar()
    {
        return view('documentation.interface.navbar');
    }

    /**
     * Interface - Lecteur vidéo
     */
    public function interfaceVideoPlayer()
    {
        return view('documentation.interface.video-player');
    }

    /**
     * Interface - Recherche
     */
    public function interfaceSearch()
    {
        return view('documentation.interface.search');
    }

    /**
     * Admin - Vue d'ensemble
     */
    public function adminOverview()
    {
        $this->checkAdminAccess();
        return view('documentation.admin.overview');
    }

    /**
     * Admin - Base de données
     */
    public function adminDatabase()
    {
        $this->checkAdminAccess();
        return view('documentation.admin.database');
    }

    /**
     * Admin - Transferts et transcodage
     */
    public function adminTransfers()
    {
        $this->checkAdminAccess();
        return view('documentation.admin.transfers');
    }

    /**
     * Admin - Réconciliation
     */
    public function adminReconciliation()
    {
        $this->checkAdminAccess();
        return view('documentation.admin.reconciliation');
    }

    /**
     * Admin - Paramètres
     */
    public function adminSettings()
    {
        $this->checkAdminAccess();
        return view('documentation.admin.settings');
    }

    /**
     * Admin - Gestion des utilisateurs
     */
    public function adminUsers()
    {
        $this->checkAdminAccess();
        return view('documentation.admin.users');
    }
}
