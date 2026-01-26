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
    $filtres = [];

    // On récupère le mot saisi (soit du header 'motCle', soit de la page 'description')
    $searchTerm = $request->input('description') ?? $request->input('motCle');

    if ($searchTerm) {
        // On envoie le mot-clé au service
        $filtres['keyword'] = $searchTerm;
    }

    // On appelle le service (qui va maintenant inclure le thème)
    $medias = $this->mediaService->searchMedia($filtres);

    $medias->appends($request->all());

    // On définit les variables pour éviter les erreurs dans la vue
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