<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Media;
use App\Models\Category;

class AdminController extends Controller
{
    /**
     * Affiche le tableau de bord administrateur
     */
    public function index()
    {
        $stats = [
            'total_professeurs' => \App\Models\Professeur::count(),
            'total_eleves' => \App\Models\Eleve::count(),
            'total_medias' => Media::count(),
            'total_projets' => \App\Models\Projet::count(),
            'recent_medias' => Media::with(['projet', 'professeur'])->orderBy('created_at', 'desc')->take(10)->get(),
        ];

        return view('pageAdministration', compact('stats'));
    }

    /**
     * Gestion des professeurs
     */
    public function professeurs()
    {
        $professeurs = \App\Models\Professeur::withCount('media')->paginate(20);
        return view('admin.professeurs', compact('professeurs'));
    }

    /**
     * Créer un professeur
     */
    public function createProfesseur(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'identifiant' => 'required|string|unique:professeurs',
            'mot_de_passe' => 'required|min:8',
        ]);

        $validated['mot_de_passe'] = bcrypt($validated['mot_de_passe']);

        \App\Models\Professeur::create($validated);

        return back()->with('success', 'Professeur créé avec succès!');
    }

    /**
     * Supprimer un professeur
     */
    public function deleteProfesseur($id)
    {
        $professeur = \App\Models\Professeur::findOrFail($id);

        if ($professeur->media()->count() > 0) {
            return back()->withErrors('Impossible de supprimer un professeur référent de médias.');
        }

        $professeur->delete();

        return back()->with('success', 'Professeur supprimé avec succès!');
    }

    /**
     * Gestion des élèves
     */
    public function eleves()
    {
        $eleves = \App\Models\Eleve::withCount('media')->paginate(20);
        return view('admin.eleves', compact('eleves'));
    }

    /**
     * Créer un élève
     */
    public function createEleve(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
        ]);

        \App\Models\Eleve::create($validated);

        return back()->with('success', 'Élève créé avec succès!');
    }

    /**
     * Supprimer un élève
     */
    public function deleteEleve($id)
    {
        $eleve = \App\Models\Eleve::findOrFail($id);
        $eleve->delete();

        return back()->with('success', 'Élève supprimé avec succès!');
    }

    /**
     * Gestion des projets
     */
    public function projets()
    {
        $projets = \App\Models\Projet::withCount('media')->paginate(20);
        return view('admin.projets', compact('projets'));
    }

    /**
     * Créer un projet
     */
    public function createProjet(Request $request)
    {
        $validated = $request->validate([
            'libelle' => 'required|string|max:255',
        ]);

        \App\Models\Projet::create($validated);

        return back()->with('success', 'Projet créé avec succès!');
    }

    /**
     * Supprimer un projet
     */
    public function deleteProjet($id)
    {
        $projet = \App\Models\Projet::findOrFail($id);

        if ($projet->media()->count() > 0) {
            return back()->withErrors('Impossible de supprimer un projet contenant des médias.');
        }

        $projet->delete();

        return back()->with('success', 'Projet supprimé avec succès!');
    }
}
