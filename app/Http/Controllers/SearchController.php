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

    /**
     * @brief Initialise le contrôleur avec le service de gestion des médias.
     *
     * Utilise l'injection de dépendance pour déléguer la logique métier
     * de recherche au service MediaService.
     *
     * @param MediaService $mediaService Service de gestion et recherche des médias
     */
    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    /**
     * @brief Gère l'affichage et le traitement de la recherche de médias.
     *
     * Cette méthode centralise la récupération des filtres envoyés par la requête
     * (formulaire ou query params), puis délègue la logique de recherche
     * au MediaService.
     *
     * Fonctionnalités :
     * - Support d’un mot-clé global (description ou motCle)
     * - Transmission de filtres multiples au service
     * - Pagination avec conservation des paramètres
     * - Chargement des données nécessaires à l’affichage (projets, professeurs)
     *
     * @param Request $request Requête HTTP contenant les filtres de recherche
     * @return \Illuminate\View\View Vue "recherche" avec les résultats et filtres associés
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
     * @brief Fournit une API d’autocomplétion pour les titres de médias.
     *
     * Recherche les titres correspondant au terme saisi et retourne
     * une liste limitée de suggestions pour alimenter un champ de recherche dynamique.
     *
     * @param Request $request Requête HTTP contenant le terme de recherche ("term")
     * @return \Illuminate\Http\JsonResponse Liste des titres correspondants au format JSON
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