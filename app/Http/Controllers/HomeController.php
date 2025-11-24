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
        // Récupérer les derniers médias pour l'accueil
        $medias = Media::with(['projets'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return view('home', compact('medias'));
    }
}
