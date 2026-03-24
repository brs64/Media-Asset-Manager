<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Media;

class HomeController extends Controller
{
    /**
     * @brief Affiche la page d'accueil de l'application.
     *
     * Récupère les derniers médias ajoutés au système et les affiche
     * dans une grille paginée de 16 éléments par page.
     *
     * Les médias sont chargés avec leurs projets associés (eager loading)
     * pour optimiser les performances et éviter le problème N+1.
     *
     * @return \Illuminate\View\View Vue "home" avec la liste paginée des médias récents
     */
    public function index()
    {
        // Récupérer les derniers médias pour la grille principale (Pagination de 16)
        $medias = Media::with(['projets'])
            ->orderBy('created_at', 'desc')
            ->paginate(16);

        return view('home', compact('medias'));
    }
}
