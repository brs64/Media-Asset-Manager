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

    /**
     * @brief Initialise le contrôleur avec le service de gestion des médias.
     *
     * Utilise l'injection de dépendance pour déléguer la logique métier
     * (recherche, suppression, synchronisation) au MediaService.
     *
     * @param MediaService $mediaService Service de gestion des médias
     */
    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    /**
     * @brief Affiche la liste des médias avec filtres et pagination.
     *
     * Récupère les paramètres de recherche depuis la requête HTTP et délègue
     * la récupération des résultats au MediaService.
     *
     * @return \Illuminate\View\View Vue "home" contenant la liste paginée des médias
     */
    public function index()
    {
        // List all media with pagination via service
        $medias = $this->mediaService->searchMedia(request()->all());

        return view('home', compact('medias'));
    }

    /**
     * @brief Affiche le formulaire de création d’un nouveau média.
     *
     * Charge les données nécessaires à la construction du formulaire :
     * projets, professeurs, élèves et rôles.
     *
     * @return \Illuminate\View\View Vue du formulaire de création de métadonnées
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
     * @brief Enregistre un nouveau média avec ses métadonnées et participations.
     *
     * Cette méthode :
     * - Valide les données du formulaire
     * - Nettoie certains champs texte
     * - Crée le média en base
     * - Gère les propriétés personnalisées
     * - Crée automatiquement les élèves si nécessaire
     * - Associe les participations (élève + rôle)
     *
     * @param Request $request Requête HTTP contenant les données du formulaire
     * @return \Illuminate\Http\RedirectResponse Redirection vers la page du média créé
     *
     * @throws \Illuminate\Validation\ValidationException
     * Si les données envoyées sont invalides
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
            'properties' => 'nullable|array',
            'properties.*.key' => 'nullable|string|max:255',
            'properties.*.value' => 'nullable',
            'participations' => 'nullable|array',
            'participations.*.eleve_nom' => 'required|string',
            'participations.*.role_id' => 'required|exists:roles,id',
        ], [
            'mtd_tech_titre.required' => 'Le titre est obligatoire.',
            'mtd_tech_titre.max' => 'Le titre ne doit pas dépasser 255 caractères.',
            'description.max' => 'La description ne doit pas dépasser 5000 caractères.',
            'projet_id.exists' => 'Le projet sélectionné est invalide.',
            'professeur_id.exists' => 'Le professeur sélectionné est invalide.',
            'participations.*.eleve_nom.required' => "Le nom de l'élève est obligatoire.",
            'participations.*.role_id.required' => 'Le rôle est obligatoire.',
            'participations.*.role_id.exists' => 'Le rôle sélectionné est invalide.',
        ]);

        $properties = collect($request->input('properties', []))
            ->filter(fn ($item) => !empty($item['key']))
            ->mapWithKeys(fn ($item) => [
                trim((string) $item['key']) => $item['value'] ?? null
            ])
            ->toArray();

        // Sanitize single-line fields: remove newlines
        foreach (['mtd_tech_titre', 'promotion', 'type', 'theme'] as $field) {
            if (!empty($validated[$field])) {
                $validated[$field] = preg_replace('/[\r\n]+/', ' ', trim($validated[$field]));
            }
        }

        // Création du média
        $media = \App\Models\Media::create($validated);

        // Ajout des participations élèves
        // --- LOGIQUE AUTO-CRÉATION ÉLÈVES ---
        if (!empty($request->participations)) {
            foreach ($request->participations as $item) {
                $nomSaisi = trim($item['eleve_nom']);

                if ($nomSaisi) {
                    // Séparation Nom/Prénom (le dernier mot est considéré comme le prénom)
                    $parts = explode(' ', $nomSaisi);
                    $prenom = count($parts) > 1 ? array_pop($parts) : '';
                    $nom = implode(' ', $parts) ?: $prenom;

                    // Crée l'élève s'il n'existe pas
                    $eleve = \App\Models\Eleve::firstOrCreate(
                        ['nom' => $nom, 'prenom' => $prenom]
                    );

                    \App\Models\Participation::create([
                        'media_id' => $media->id,
                        'eleve_id' => $eleve->id,
                        'role_id'  => $item['role_id'],
                    ]);
                }
            }
        }
       /* if ($request->has('eleves') && $request->has('roles')) {
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
        }*/

        return redirect()->route('medias.show', $media->id)
            ->with('success', 'Média ajouté avec succès!');
    }

    /**
     * @brief Affiche les détails d’un média.
     *
     * Récupère les informations complètes du média via le MediaService
     * (incluant éventuellement des données enrichies) et les transmet à la vue.
     *
     * @param string $id Identifiant du média
     * @return \Illuminate\View\View Vue "video" avec les informations du média
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * Si le média est introuvable
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
     * @brief Affiche le formulaire d’édition d’un média existant.
     *
     * Charge le média avec ses relations (professeur, projets, participations)
     * ainsi que les données nécessaires aux champs de sélection.
     *
     * @param string $id Identifiant du média
     * @return \Illuminate\View\View Vue du formulaire pré-rempli
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * Si le média est introuvable
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
     * @brief Met à jour les métadonnées d’un média existant.
     *
     * Cette méthode :
     * - Valide les données envoyées
     * - Nettoie les champs texte
     * - Met à jour les informations principales du média
     * - Synchronise les projets associés (relation many-to-many)
     * - Reconstruit les participations (élèves + rôles)
     * - Gère les propriétés personnalisées
     *
     * Les URI (fichiers vidéo) ne sont pas modifiées ici.
     *
     * @param Request $request Requête HTTP contenant les données mises à jour
     * @param string $id Identifiant du média
     * @return \Illuminate\Http\RedirectResponse Redirection vers la page du média
     *
     * @throws \Illuminate\Validation\ValidationException
     * Si les données sont invalides
     */
    public function update(Request $request, string $id)
    {
        $media = \App\Models\Media::findOrFail($id);

        $validated = $request->validate([
            'mtd_tech_titre' => 'required|string|max:255',
            'promotion' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'theme' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:5000',
            'professeur_id' => 'nullable|exists:professeurs,id',
            'projet_ids' => 'nullable|array',
            'projet_ids.*' => 'exists:projets,id',
            'participations' => 'nullable|array',
            'participations.*.eleve_nom' => 'required|string',
            'participations.*.role_id' => 'required|exists:roles,id',
            'properties' => 'nullable|array',
            'properties.*.key' => 'nullable|string|max:255',
            'properties.*.value' => 'nullable',
        ], [
            'mtd_tech_titre.required' => 'Le titre est obligatoire.',
            'mtd_tech_titre.max' => 'Le titre ne doit pas dépasser 255 caractères.',
            'description.max' => 'La description ne doit pas dépasser 5000 caractères.',
            'professeur_id.exists' => 'Le professeur sélectionné est invalide.',
            'projet_ids.*.exists' => 'Un des projets sélectionnés est invalide.',
            'participations.*.eleve_nom.required' => "Le nom de l'élève est obligatoire.",
            'participations.*.role_id.required' => 'Le rôle est obligatoire.',
            'participations.*.role_id.exists' => 'Le rôle sélectionné est invalide.',
        ]);

        // Sanitize single-line fields: remove newlines
        foreach (['mtd_tech_titre', 'promotion', 'type', 'theme'] as $field) {
            if (!empty($validated[$field])) {
                $validated[$field] = preg_replace('/[\r\n]+/', ' ', trim($validated[$field]));
            }
        }

        $properties = collect($request->input('properties', []))
            ->filter(fn ($item) => !empty($item['key']))
            ->mapWithKeys(fn ($item) => [
                trim((string) $item['key']) => $item['value'] ?? null
            ])
            ->toArray();

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
                'properties' => $properties,
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
            $nomSaisi = trim($participation['eleve_nom']);

            if ($nomSaisi) {
                // Découpage du nom et prénom
                $parts = explode(' ', $nomSaisi);
                $prenom = count($parts) > 1 ? array_pop($parts) : '';
                $nom = implode(' ', $parts) ?: $prenom;

                // Cherche l'élève ou le crée s'il est nouveau
                $eleve = \App\Models\Eleve::firstOrCreate(
                    ['nom' => $nom, 'prenom' => $prenom]
                );

                // Crée le lien dans la table pivot
                \App\Models\Participation::create([
                    'media_id' => $media->id,
                    'eleve_id' => $eleve->id,
                    'role_id'  => $participation['role_id'],
                ]);
            }
            }}

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
     * @brief Supprime un média ainsi que ses fichiers associés.
     *
     * Effectue :
     * - Suppression des fichiers locaux liés au média
     * - Suppression de l’entrée en base de données
     *
     * @param string $id Identifiant du média
     * @return \Illuminate\Http\RedirectResponse Redirection vers la liste des médias
     */
    public function destroy(string $id)
    {
        $success_local = $this->mediaService->clearLocalFiles($id);

        $success_db = $this->mediaService->deleteMedia($id);

        if ($success_db && $success_local) {
            return redirect()->route('medias.index')
                ->with('success', 'Media deleted successfully!');
        }

        return redirect()->route('medias.index')
            ->withErrors('Error deleting media');
    }

    /**
     * @brief Lance la synchronisation des médias depuis les différents disques.
     *
     * Déclenche des jobs asynchrones pour analyser les sources suivantes :
     * - FTP ARCH
     * - FTP PAD
     * - Stockage local externe
     *
     * Permet de mettre à jour la base de données en fonction des fichiers présents.
     *
     * @return \Illuminate\Http\RedirectResponse Retour à la page précédente avec message de statut
     */
    public function sync()
    {
        foreach ([
            'ftp_arch',
            'ftp_pad',
            'external_local',
        ] as $disk) {
            SyncMediaFromDiskJob::dispatch($disk, '/');
        }

        return back()->with('success', 'Synchronisation BD lancée !');
    }

    /**
     * @brief Synchronise un chemin local spécifique avec la base de données.
     *
     * Permet d’ajouter ou mettre à jour un média à partir d’un chemin fourni.
     *
     * @param Request $request Requête HTTP contenant le chemin à synchroniser
     * @param MediaService $mediaService Service de gestion des médias
     * @return \Illuminate\Http\JsonResponse Résultat de la synchronisation
     *
     * @throws \Illuminate\Validation\ValidationException
     * Si le chemin n’est pas fourni ou invalide
     */
    public function syncLocalPath(Request $request, MediaService $mediaService)
    {
        $request->validate([
            'path' => ['required', 'string'],
        ], [
            'path.required' => 'Le chemin est obligatoire.',
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
