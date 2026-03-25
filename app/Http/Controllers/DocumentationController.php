<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DocumentationController extends Controller
{
    /**
     * @brief Vérifie que l'utilisateur connecté a accès à la documentation admin.
     *
     * Certaines sections de la documentation sont réservées aux administrateurs
     * et professeurs uniquement. Cette méthode contrôle l'accès avant affichage.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * Erreur 403 si l'utilisateur n'est pas connecté ou n'a pas les permissions
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
     * @brief Affiche la page d'accueil de la documentation.
     *
     * Point d'entrée principal de la documentation utilisateur.
     * Contient les liens vers toutes les sections disponibles.
     *
     * @return \Illuminate\View\View Vue "documentation.index"
     */
    public function index()
    {
        return view('documentation.index');
    }

    /**
     * @brief Affiche le guide de démarrage rapide.
     *
     * Documentation pour les nouveaux utilisateurs expliquant
     * les fonctionnalités de base de l'application.
     *
     * @return \Illuminate\View\View Vue "documentation.getting-started"
     */
    public function gettingStarted()
    {
        return view('documentation.getting-started');
    }

    /**
     * @brief Documentation de la page d'accueil.
     *
     * Explique l'utilisation et les fonctionnalités de la page principale
     * de l'application (grille de médias, navigation, etc.).
     *
     * @return \Illuminate\View\View Vue "documentation.interface.home-page"
     */
    public function interfaceHomePage()
    {
        return view('documentation.interface.home-page');
    }

    /**
     * @brief Documentation de la barre de navigation.
     *
     * Détaille les différentes sections accessibles depuis le menu principal
     * et leur utilité.
     *
     * @return \Illuminate\View\View Vue "documentation.interface.navbar"
     */
    public function interfaceNavbar()
    {
        return view('documentation.interface.navbar');
    }

    /**
     * @brief Documentation du lecteur vidéo.
     *
     * Explique les contrôles du lecteur, les fonctionnalités de lecture
     * et les options disponibles pour visualiser les médias.
     *
     * @return \Illuminate\View\View Vue "documentation.interface.video-player"
     */
    public function interfaceVideoPlayer()
    {
        return view('documentation.interface.video-player');
    }

    /**
     * @brief Documentation du système de recherche.
     *
     * Guide l'utilisateur sur l'utilisation des filtres, des mots-clés
     * et des critères de recherche avancés.
     *
     * @return \Illuminate\View\View Vue "documentation.interface.search"
     */
    public function interfaceSearch()
    {
        return view('documentation.interface.search');
    }

    /**
     * @brief Vue d'ensemble de l'interface d'administration.
     *
     * Section réservée aux administrateurs et professeurs.
     * Présente les différents outils de gestion disponibles.
     *
     * @return \Illuminate\View\View Vue "documentation.admin.overview"
     */
    public function adminOverview()
    {
        $this->checkAdminAccess();
        return view('documentation.admin.overview');
    }

    /**
     * @brief Documentation de la gestion de base de données.
     *
     * Explique les fonctionnalités de sauvegarde, restauration
     * et maintenance de la base de données.
     *
     * @return \Illuminate\View\View Vue "documentation.admin.database"
     */
    public function adminDatabase()
    {
        $this->checkAdminAccess();
        return view('documentation.admin.database');
    }

    /**
     * @brief Documentation des transferts et du transcodage.
     *
     * Guide sur l'utilisation de FFAStrans pour transcoder les médias,
     * suivre les jobs et gérer les transferts.
     *
     * @return \Illuminate\View\View Vue "documentation.admin.transfers"
     */
    public function adminTransfers()
    {
        $this->checkAdminAccess();
        return view('documentation.admin.transfers');
    }

    /**
     * @brief Documentation de la réconciliation.
     *
     * Explique le processus de synchronisation entre les fichiers physiques
     * et les enregistrements en base de données.
     *
     * @return \Illuminate\View\View Vue "documentation.admin.reconciliation"
     */
    public function adminReconciliation()
    {
        $this->checkAdminAccess();
        return view('documentation.admin.reconciliation');
    }

    /**
     * @brief Documentation des paramètres système.
     *
     * Guide de configuration des URIs, connexions FTP, paramètres de sauvegarde
     * et autres options système.
     *
     * @return \Illuminate\View\View Vue "documentation.admin.settings"
     */
    public function adminSettings()
    {
        $this->checkAdminAccess();
        return view('documentation.admin.settings');
    }

    /**
     * @brief Documentation de la gestion des utilisateurs.
     *
     * Explique la création, modification et suppression des comptes utilisateurs,
     * ainsi que la gestion des rôles et permissions.
     *
     * @return \Illuminate\View\View Vue "documentation.admin.users"
     */
    public function adminUsers()
    {
        $this->checkAdminAccess();
        return view('documentation.admin.users');
    }
}
