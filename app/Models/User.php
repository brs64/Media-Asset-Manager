<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// Spatie
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    /**
     * Champs assignables en masse
     */
    protected $fillable = [
        'name',       // login ou identifiant
        'password',
        'nom',        // pour professeur ou élève
        'prenom',     // idem
    ];

    /**
     * Champs cachés pour la sérialisation
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casts
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    // ---------------------------------------------------
    // Relation média (pour professeur)
    // ---------------------------------------------------
    public function media(): HasMany
    {
        return $this->hasMany(Media::class, 'professeur_id');
    }

    // ---------------------------------------------------
    // Helpers pour type utilisateur
    // ---------------------------------------------------

    /**
     * Vérifie si l'utilisateur est un professeur
     */
    public function isProfesseur(): bool
    {
        return $this->hasRole('professeur');
    }

    /**
     * Vérifie si l'utilisateur est un élève
     */
    public function isEleve(): bool
    {
        return $this->hasRole('eleve');
    }

    /**
     * Obtient le profil (ici c'est juste nom/prenom stocké sur User)
     */
    public function getProfile(): array
    {
        return [
            'nom' => $this->nom,
            'prenom' => $this->prenom,
        ];
    }

    // ---------------------------------------------------
    // Gestion des droits sur le front
    // ---------------------------------------------------

    /**
     * Vérifie si l'utilisateur peut modifier une vidéo
     */
    public function canModifierVideo(): bool
    {
        return $this->can('modifier video');
    }

    public function canDiffuserVideo(): bool
    {
        return $this->can('diffuser video');
    }

    public function canSupprimerVideo(): bool
    {
        return $this->can('supprimer video');
    }

    public function canAdministrerSite(): bool
    {
        return $this->can('administrer site');
    }
}
