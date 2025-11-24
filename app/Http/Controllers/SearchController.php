<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Media;
use App\Services\MediaService;

class SearchController extends Controller
{
    protected $mediaService;

    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    /**
     * Affiche la page de recherche
     */
    public function index()
    {
        $projets = \App\Models\Projet::all();
        $professeurs = \App\Models\Professeur::all();

        return view('recherche', compact('projets', 'professeurs'));
    }

    /**
     * Effectue la recherche
     */
    public function search(Request $request)
    {
        // Utiliser le service pour la recherche
        $filtres = [
            'titre' => $request->mtd_tech_titre,
            'projet_id' => $request->projet_id,
            'type' => $request->type,
            'theme' => $request->theme,
            'promotion' => $request->promotion,
            'professeur_id' => $request->professeur_id,
            'description' => $request->description,
        ];

        $medias = $this->mediaService->rechercherMedias($filtres);

        // Charger les données pour les filtres
        $projets = \App\Models\Projet::all();
        $professeurs = \App\Models\Professeur::all();

        return view('recherche', compact('medias', 'projets', 'professeurs'));
    }

    /**
     * API pour autocomplete
     */
    public function autocomplete(Request $request)
    {
        $term = $request->get('term', '');

        $results = Media::where('mtd_tech_titre', 'like', '%' . $term . '%')
            ->limit(10)
            ->pluck('mtd_tech_titre');

        return response()->json($results);
    }
}
