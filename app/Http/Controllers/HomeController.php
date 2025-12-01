<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Media;

class HomeController extends Controller
{
    /**
     * Affiche la page d'accueil
     */
    public function index()
    {
        $medias = Media::with(['projets'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        // TODO: Implémenter la logique du dernier projet
        $tabDernierProjet = [];

        return view('home', compact('medias', 'tabDernierProjet'));
    }
}
