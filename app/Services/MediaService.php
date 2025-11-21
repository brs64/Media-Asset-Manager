<?php

namespace App\Services;

use App\Models\Media;
use App\Models\Projet;
use App\Models\Professeur;
use App\Models\Eleve;
use App\Models\Role;
use App\Models\Participation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service de gestion des médias
 * Contient toute la logique métier pour les opérations sur les médias
 */
class MediaService
{
    /**
     * Récupère les informations complètes d'un média
     */
    public function getMediaInfos(int $idMedia): ?array
    {
        $media = Media::with([
            'projets',
            'professeur',
            'participations.eleve',
            'participations.role'
        ])->find($idMedia);

        if (!$media) {
            return null;
        }

        return [
            'media' => $media,
            'titre' => $this->extraireTitreVideo($media->mtd_tech_titre),
            'participations' => $this->formaterParticipations($media->participations),
        ];
    }

    /**
     * Met à jour les métadonnées d'un média
     */
    public function mettreAJourMetadonnees(
        int $idMedia,
        string $profReferent,
        ?string $promotion,
        ?string $projet,
        ?string $description,
        array $roles
    ): bool {
        try {
            DB::beginTransaction();

            $media = Media::findOrFail($idMedia);

            // Mise à jour du professeur référent
            if ($profReferent) {
                $professeur = $this->trouverOuCreerProfesseur($profReferent);
                $media->professeur_id = $professeur->id;
            }

            // Mise à jour des autres champs
            $media->promotion = $promotion;
            $media->description = $description;
            $media->save();

            // Mise à jour du projet (many-to-many via pivot)
            if ($projet) {
                $projetModel = $this->trouverOuCreerProjet($projet);
                $media->projets()->sync([$projetModel->id]);
            }

            // Mise à jour des participations (rôles)
            $this->mettreAJourParticipations($idMedia, $roles);

            DB::commit();
            Log::info("Métadonnées mises à jour pour le média #$idMedia");

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erreur lors de la mise à jour des métadonnées : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Assigne des participants avec leurs rôles à un média
     */
    public function assignerRoles(int $idMedia, string $nomRole, array $personnes): void
    {
        $role = Role::firstOrCreate(['libelle' => $nomRole]);

        foreach ($personnes as $personne) {
            $eleve = Eleve::firstOrCreate([
                'nom' => $this->extraireNom($personne),
                'prenom' => $this->extrairePrenom($personne),
            ]);

            Participation::updateOrCreate([
                'media_id' => $idMedia,
                'eleve_id' => $eleve->id,
                'role_id' => $role->id,
            ]);
        }
    }

    /**
     * Supprime un média et ses participations
     */
    public function supprimerMedia(int $idMedia): bool
    {
        try {
            DB::beginTransaction();

            $media = Media::findOrFail($idMedia);

            // Supprimer les participations
            Participation::where('media_id', $idMedia)->delete();

            // Supprimer le média
            $media->delete();

            DB::commit();
            Log::info("Média #$idMedia supprimé avec succès");

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erreur lors de la suppression du média : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère les derniers médias modifiés
     */
    public function getDerniersMedias(int $limite = 20): array
    {
        return Media::with(['projets', 'professeur'])
            ->orderBy('updated_at', 'desc')
            ->limit($limite)
            ->get()
            ->toArray();
    }

    /**
     * Recherche de médias avec filtres
     */
    public function rechercherMedias(array $filtres): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = Media::query()->with(['projets', 'professeur', 'participations.eleve', 'participations.role']);

        if (!empty($filtres['titre'])) {
            $query->where('mtd_tech_titre', 'like', '%' . $filtres['titre'] . '%');
        }

        if (!empty($filtres['projet_id'])) {
            $query->whereHas('projets', function ($q) use ($filtres) {
                $q->where('projets.id', $filtres['projet_id']);
            });
        }

        if (!empty($filtres['professeur_id'])) {
            $query->where('professeur_id', $filtres['professeur_id']);
        }

        if (!empty($filtres['promotion'])) {
            $query->where('promotion', 'like', '%' . $filtres['promotion'] . '%');
        }

        if (!empty($filtres['description'])) {
            $query->where('description', 'like', '%' . $filtres['description'] . '%');
        }

        if (!empty($filtres['type'])) {
            $query->where('type', 'like', '%' . $filtres['type'] . '%');
        }

        return $query->paginate(20);
    }

    // --- Méthodes privées utilitaires ---

    /**
     * Trouve ou crée un professeur à partir de son nom complet
     */
    private function trouverOuCreerProfesseur(string $nomComplet): Professeur
    {
        $parties = explode(' ', trim($nomComplet));
        $prenom = array_pop($parties);
        $nom = implode(' ', $parties) ?: $prenom;

        // Chercher un professeur existant
        $professeur = Professeur::where('nom', $nom)->where('prenom', $prenom)->first();

        if ($professeur) {
            return $professeur;
        }

        // Creer le User associe
        $email = strtolower(substr($prenom, 0, 1) . '.' . $nom) . '@mediamanager.fr';
        $user = \App\Models\User::firstOrCreate(
            ['email' => $email],
            ['name' => $prenom . ' ' . $nom, 'password' => bcrypt('password')]
        );

        // Creer le Professeur
        return Professeur::create([
            'user_id' => $user->id,
            'nom' => $nom,
            'prenom' => $prenom,
        ]);
    }

    /**
     * Trouve ou crée un projet
     */
    private function trouverOuCreerProjet(string $libelle): Projet
    {
        return Projet::firstOrCreate(['libelle' => $libelle]);
    }

    /**
     * Met à jour toutes les participations d'un média
     */
    private function mettreAJourParticipations(int $idMedia, array $roles): void
    {
        // Supprimer les anciennes participations
        Participation::where('media_id', $idMedia)->delete();

        // Ajouter les nouvelles participations
        foreach ($roles as $nomRole => $listePersonnesCsv) {
            if (empty(trim($listePersonnesCsv))) {
                continue;
            }

            $personnes = array_filter(array_map('trim', explode(',', $listePersonnesCsv)));
            $this->assignerRoles($idMedia, $nomRole, $personnes);
        }
    }

    /**
     * Formate les participations pour l'affichage
     */
    private function formaterParticipations($participations): array
    {
        $result = [];

        foreach ($participations as $participation) {
            $role = $participation->role->libelle;
            $nom = $participation->eleve->nom . ' ' . $participation->eleve->prenom;

            if (!isset($result[$role])) {
                $result[$role] = [];
            }

            $result[$role][] = $nom;
        }

        // Convertir en chaînes séparées par des virgules
        foreach ($result as $role => &$names) {
            $names = implode(', ', $names);
        }

        return $result;
    }

    /**
     * Extrait le titre d'une vidéo depuis son nom de fichier
     * Format: ANNEE_PROJET_TITRE.ext
     */
    private function extraireTitreVideo(string $nomFichier): string
    {
        if (preg_match("/^[^_]*_[^_]*_(.*)(?=\.)/", $nomFichier, $matches)) {
            return $matches[1] ?? pathinfo($nomFichier, PATHINFO_FILENAME);
        }

        return pathinfo($nomFichier, PATHINFO_FILENAME);
    }

    /**
     * Extrait le nom d'une personne (avant le dernier espace)
     */
    private function extraireNom(string $nomComplet): string
    {
        $parties = explode(' ', trim($nomComplet));
        if (count($parties) > 1) {
            array_pop($parties);
            return implode(' ', $parties);
        }
        return $nomComplet;
    }

    /**
     * Extrait le prénom d'une personne (après le dernier espace)
     */
    private function extrairePrenom(string $nomComplet): string
    {
        $parties = explode(' ', trim($nomComplet));
        return array_pop($parties);
    }
}
