<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Media;

use App\Services\MediaService;
use App\Services\FileExplorerService;

use App\Jobs\SyncMediaFromDiskJob;

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
        // List all media with pagination via service
        $medias = $this->mediaService->searchMedia(request()->all());

        return view('home', compact('medias'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $projets = \App\Models\Projet::orderBy('libelle')->get();
        $professeurs = \App\Models\Professeur::orderBy('nom', 'prenom')->get();
        $eleves = \App\Models\Eleve::orderBy('nom', 'prenom')->get();
        $roles = \App\Models\Role::orderBy('libelle')->get();

        return view('formulaireMetadonnees', compact('projets', 'professeurs', 'eleves', 'roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'mtd_tech_titre' => 'required|string|max:255',
            'promotion' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'theme' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:5000',
            'URI_NAS_ARCH' => 'nullable|string|max:2048',
            'URI_NAS_PAD' => 'nullable|string|max:2048',
            'chemin_local' => 'nullable|string|max:2048',
            'projet_id' => 'nullable|exists:projets,id',
            'professeur_id' => 'nullable|exists:professeurs,id',
            'eleves' => 'nullable|array',
            'eleves.*' => 'exists:eleves,id',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
        ]);

        // Sanitize single-line fields: remove newlines
        foreach (['mtd_tech_titre', 'promotion', 'type', 'theme'] as $field) {
            if (!empty($validated[$field])) {
                $validated[$field] = preg_replace('/[\r\n]+/', ' ', trim($validated[$field]));
            }
        }

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

        return redirect()->route('medias.show', $media->id)
            ->with('success', 'Média ajouté avec succès!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $mediaInfo = $this->mediaService->getMediaInfo($id);

        if (!$mediaInfo) {
            abort(404, 'Media not found');
        }

        return view('video', $mediaInfo);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $media = \App\Models\Media::with(['professeur', 'projets', 'participations.eleve', 'participations.role'])
            ->findOrFail($id);

        // Get dropdown data
        $projets = \App\Models\Projet::orderBy('libelle')->get();
        $professeurs = \App\Models\Professeur::orderBy('nom')->orderBy('prenom')->get();
        $eleves = \App\Models\Eleve::orderBy('nom')->orderBy('prenom')->get();
        $roles = \App\Models\Role::orderBy('libelle')->get();

        return view('formulaireMetadonnees', compact('media', 'projets', 'professeurs', 'eleves', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $media = \App\Models\Media::findOrFail($id);

        $validated = $request->validate([
            // Basic media fields (URIs are managed elsewhere, not in this form)
            'mtd_tech_titre' => 'required|string|max:255',
            'promotion' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'theme' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:5000',

            // Foreign keys
            'professeur_id' => 'nullable|exists:professeurs,id',
            'projet_ids' => 'nullable|array',
            'projet_ids.*' => 'exists:projets,id',

            // Participations (array of student-role pairs)
            'participations' => 'nullable|array',
            'participations.*.eleve_id' => 'required|exists:eleves,id',
            'participations.*.role_id' => 'required|exists:roles,id',
        ]);

        // Sanitize single-line fields: remove newlines
        foreach (['mtd_tech_titre', 'promotion', 'type', 'theme'] as $field) {
            if (!empty($validated[$field])) {
                $validated[$field] = preg_replace('/[\r\n]+/', ' ', trim($validated[$field]));
            }
        }

        \DB::beginTransaction();
        try {
            // Update only the metadata fields (don't touch URIs - they're managed elsewhere)
            $media->update([
                'mtd_tech_titre' => $validated['mtd_tech_titre'],
                'promotion' => $validated['promotion'] ?? null,
                'type' => $validated['type'] ?? null,
                'theme' => $validated['theme'] ?? null,
                'description' => $validated['description'] ?? null,
                'professeur_id' => $validated['professeur_id'] ?? null,
            ]);

            // Sync projets (many-to-many)
            if (isset($validated['projet_ids'])) {
                $media->projets()->sync($validated['projet_ids']);
            } else {
                $media->projets()->sync([]);
            }

            // Update participations: delete old, create new
            // We don't use sync() here cause we have 3 columns
            $media->participations()->delete();
            if (!empty($validated['participations'])) {
                foreach ($validated['participations'] as $participation) {
                    \App\Models\Participation::create([
                        'media_id' => $media->id,
                        'eleve_id' => $participation['eleve_id'],
                        'role_id' => $participation['role_id'],
                    ]);
                }
            }

            \DB::commit();

            return redirect()->route('medias.show', $media->id)
                ->with('success', 'Média modifié avec succès!');

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Error updating media: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Une erreur est survenue lors de la modification du média.']);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $success = $this->mediaService->deleteMedia($id);

        if ($success) {
            return redirect()->route('medias.index')
                ->with('success', 'Media deleted successfully!');
        }

        return redirect()->route('medias.index')
            ->withErrors('Error deleting media');
    }

    public function sync()
    {
        foreach ([
            'ftp_arch',
            'ftp_pad',
            'external_local',
        ] as $disk) {
            SyncMediaFromDiskJob::dispatch($disk, '/');
        }

        return back()->with('success', 'Synchronisation BD lancée 🚀');
    }

    public function syncLocalPath(Request $request, MediaService $mediaService)
    {
        $request->validate([
            'path' => ['required', 'string'],
        ]);

        $success = $mediaService->syncLocalPath($request->path);

        if (!$success) {
            return response()->json([
                'message' => 'Media non trouvé',
            ], 404);
        }

        return response()->json([
            'message' => 'Chemin local synchronisé',
        ]);
    }
}
