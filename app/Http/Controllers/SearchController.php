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
        // 1. Préparation des filtres pour le Service
        $filtres = [];

        // Filtre : Description
        if ($request->filled('description')) {
            $filtres['description'] = $request->description;
        }

        // Filtre : Promotion
        if ($request->filled('promotion')) {
            $filtres['promotion'] = $request->promotion;
        }

        // Filtre : Projet (Conversion Nom -> ID)
        if ($request->filled('projet')) {
            // Votre vue envoie l'intitulé (string), le service veut l'ID
            $projetModel = Projet::where('intitule', $request->projet)->first();
            if ($projetModel) {
                $filtres['projet_id'] = $projetModel->id;
            }
        }

        // Filtre : Professeur (Conversion Référent -> ID)
        if ($request->filled('prof')) {
            // Votre vue envoie 'professeurReferent', le service veut 'professeur_id'
            // Je suppose que 'professeurReferent' est une colonne unique dans la table profs ou users
            // Adaptez 'professeurReferent' ci-dessous au nom réel de la colonne dans votre BDD
            $profModel = Professeur::where('professeurReferent', $request->prof)->first(); 
            
            // Si jamais 'professeurReferent' n'existe pas et que c'est juste le nom :
            // $profModel = Professeur::where('nom', $request->prof)->first();

            if ($profModel) {
                $filtres['professeur_id'] = $profModel->id;
            }
        }

        // 2. Appel du Service pour récupérer les médias
        // Le service renvoie déjà un objet Paginator, parfait pour la vue
        $medias = $this->mediaService->searchMedia($filtres);

        // On s'assure que les paramètres de recherche restent dans l'URL lors de la pagination
        $medias->appends($request->all());

        // 3. Charger les listes pour les menus déroulants
        $listeProjet = Projet::orderBy('intitule')->get();
        $listeProf = Professeur::orderBy('nom')->get();

        // 4. Récupérer les valeurs actuelles pour pré-remplir le formulaire
        $description = $request->description;
        $prof = $request->prof;
        $projet = $request->projet;
        $promotion = $request->promotion;

        // 5. Renvoyer la vue
        return view('recherche', compact(
            'medias', 
            'listeProjet', 
            'listeProf', 
            'description', 
            'prof', 
            'projet', 
            'promotion'
        ));
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