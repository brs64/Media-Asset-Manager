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
     * Display search page
     */
    public function index()
    {
        $projets = \App\Models\Projet::all();
        $professeurs = \App\Models\Professeur::all();

        return view('recherche', compact('projets', 'professeurs'));
    }

    /**
     * Perform search
     */
    public function search(Request $request)
    {
        // Use service for search
        $filters = [
            'titre' => $request->mtd_tech_titre,
            'projet_id' => $request->projet_id,
            'type' => $request->type,
            'theme' => $request->theme,
            'promotion' => $request->promotion,
            'professeur_id' => $request->professeur_id,
            'description' => $request->description,
        ];

        $medias = $this->mediaService->searchMedia($filters);

        // Load data for filters
        $projets = \App\Models\Projet::all();
        $professeurs = \App\Models\Professeur::all();

        return view('recherche', compact('medias', 'projets', 'professeurs'));
    }

    /**
     * API for autocomplete
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
