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
        // Récupérer les derniers médias pour la grille principale (Pagination de 16)
        $medias = Media::with(['projets'])
            ->orderBy('created_at', 'desc')
            ->paginate(16);

        return view('home', compact('medias'));
    }
}
