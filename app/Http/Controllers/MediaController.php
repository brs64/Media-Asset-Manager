<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MediaService;
use App\Helpers\VideoHelper;

class MediaController extends Controller
{
    protected $mediaService;

    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Liste tous les médias avec pagination via le service
        $medias = $this->mediaService->rechercherMedias(request()->all());

        return view('media.index', compact('medias'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $projets = \App\Models\Projet::all();
        $professeurs = \App\Models\Professeur::all();
        $eleves = \App\Models\Eleve::all();
        $roles = \App\Models\Role::all();

        return view('formulaireMetadonnees', compact('projets', 'professeurs', 'eleves', 'roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'mtd_tech_titre' => 'required|max:255',
            'promotion' => 'nullable|string',
            'type' => 'nullable|string',
            'theme' => 'nullable|string',
            'description' => 'nullable|string',
            'mtd_tech_fps' => 'nullable|string',
            'mtd_tech_resolution' => 'nullable|string',
            'mtd_tech_duree' => 'nullable|string',
            'mtd_tech_format' => 'nullable|string',
            'URI_NAS_ARCH' => 'nullable|string',
            'URI_NAS_PAD' => 'nullable|string',
            'URI_NAS_MPEG' => 'nullable|string',
            'projet_id' => 'required|exists:projets,id',
            'professeur_id' => 'required|exists:professeurs,id',
            'eleves' => 'nullable|array',
            'eleves.*' => 'exists:eleves,id',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
        ]);

        // Création du média
        $media = \App\Models\Media::create($validated);

        // Ajout des participations élèves
        if ($request->has('eleves') && $request->has('roles')) {
            foreach ($request->eleves as $index => $eleveId) {
                $roleId = $request->roles[$index] ?? null;
                if ($roleId) {
                    \App\Models\Participation::create([
                        'media_id' => $media->id,
                        'eleve_id' => $eleveId,
                        'role_id' => $roleId,
                    ]);
                }
            }
        }

        return redirect()->route('media.show', $media->id)
            ->with('success', 'Média ajouté avec succès!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $mediaInfos = $this->mediaService->getMediaInfos($id);

        if (!$mediaInfos) {
            abort(404, 'Média introuvable');
        }

        return view('video', $mediaInfos);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $media = \App\Models\Media::with('participations')->findOrFail($id);
        $projets = \App\Models\Projet::all();
        $professeurs = \App\Models\Professeur::all();
        $eleves = \App\Models\Eleve::all();
        $roles = \App\Models\Role::all();

        return view('media.edit', compact('media', 'projets', 'professeurs', 'eleves', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $media = \App\Models\Media::findOrFail($id);

        $validated = $request->validate([
            'mtd_tech_titre' => 'required|max:255',
            'promotion' => 'nullable|string',
            'type' => 'nullable|string',
            'theme' => 'nullable|string',
            'description' => 'nullable|string',
            'mtd_tech_fps' => 'nullable|string',
            'mtd_tech_resolution' => 'nullable|string',
            'mtd_tech_duree' => 'nullable|string',
            'mtd_tech_format' => 'nullable|string',
            'URI_NAS_ARCH' => 'nullable|string',
            'URI_NAS_PAD' => 'nullable|string',
            'URI_NAS_MPEG' => 'nullable|string',
            'projet_id' => 'required|exists:projets,id',
            'professeur_id' => 'required|exists:professeurs,id',
        ]);

        $media->update($validated);

        // Mise à jour des participations
        if ($request->has('eleves') && $request->has('roles')) {
            // Supprimer les anciennes participations
            $media->participations()->delete();

            // Ajouter les nouvelles
            foreach ($request->eleves as $index => $eleveId) {
                $roleId = $request->roles[$index] ?? null;
                if ($roleId) {
                    \App\Models\Participation::create([
                        'media_id' => $media->id,
                        'eleve_id' => $eleveId,
                        'role_id' => $roleId,
                    ]);
                }
            }
        }

        return redirect()->route('media.show', $media->id)
            ->with('success', 'Média modifié avec succès!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $success = $this->mediaService->supprimerMedia($id);

        if ($success) {
            return redirect()->route('media.index')
                ->with('success', 'Média supprimé avec succès!');
        }

        return redirect()->route('media.index')
            ->withErrors('Erreur lors de la suppression du média');
    }
}
