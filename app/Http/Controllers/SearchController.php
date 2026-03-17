<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Media;
use App\Models\Projet;
use App\Models\Professeur;
use App\Services\MediaService; // Import du service

class SearchController extends Controller
{
    protected $mediaService;

    // Injection de dépendance du Service
    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    /**
     * Gère l'affichage ET le traitement de la recherche
     */
public function index(Request $request)
{
    // 1. On récupère TOUT ce qui arrive (utile pour les tests)
    $filtres = $request->all();

    // 2. On gère ton cas actuel : le mot saisi dans la barre de recherche
    $searchTerm = $request->input('description') ?? $request->input('motCle');

    if ($searchTerm) {
        $filtres['keyword'] = $searchTerm;
    }

    // 3. On envoie TOUT le tableau $filtres au service
    // S'il y a un 'keyword', le service cherchera partout.
    // S'il y a un 'projet' (envoyé par le test), le service filtrera par projet.
    $medias = $this->mediaService->searchMedia($filtres);

    $medias->appends($request->all());

    $description = $searchTerm;
    $listeProjet = Projet::orderBy('libelle')->get();
    $listeProf = Professeur::orderBy('nom')->get();

    return view('recherche', compact('medias', 'listeProjet', 'listeProf', 'description'));
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