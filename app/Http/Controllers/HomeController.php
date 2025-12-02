<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Media;
use App\Models\Projet;

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

        
       // Récupérer le "Dernier Projet" et ses vidéos
       $tabDernierProjet = [];
        
       // On récupère le projet le plus récent avec ses médias associés
       // Note: Adaptez 'created_at' si vous utilisez une autre date de référence
       $dernierProjet = Projet::with('medias')->orderBy('created_at', 'desc')->first();

       if ($dernierProjet) {
           foreach ($dernierProjet->medias as $media) {
               $tabDernierProjet[] = [
                   'id' => $media->id,
                   'projet' => $dernierProjet->nom, // ou $dernierProjet->titre
                   'titre' => $media->mtd_tech_titre,
                   'titreVideo' => $media->mtd_tech_titre, // Ou une autre colonne si nécessaire
               ];
           }
       }

       return view('home', compact('medias', 'tabDernierProjet'));
    }
}
