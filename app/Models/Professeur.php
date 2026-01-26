<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Professeur extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nom',
        'prenom',
        'is_admin',
        'can_edit_video',
        'can_broadcast_video',
        'can_delete_video',
        'can_administer',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_admin' => 'boolean',
        'can_edit_video' => 'boolean',
        'can_broadcast_video' => 'boolean',
        'can_delete_video' => 'boolean',
        'can_administer' => 'boolean',
    ];

    /**
     * Un professeur est référent de plusieurs médias
     */
    public function media(): HasMany
    {
        return $this->hasMany(Media::class, 'professeur_id');
    }

    /**
     * Relation vers le compte utilisateur du professeur (parent)
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Vérifie si le professeur est administrateur
     */
    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    /**
     * Vérifie si le professeur peut modifier des vidéos
     */
    public function canEditVideo(): bool
    {
        return $this->is_admin || $this->can_edit_video;
    }

    /**
     * Vérifie si le professeur peut diffuser des vidéos
     */
    public function canBroadcastVideo(): bool
    {
        return $this->is_admin || $this->can_broadcast_video;
    }

    /**
     * Vérifie si le professeur peut supprimer des vidéos
     */
    public function canDeleteVideo(): bool
    {
        return $this->is_admin || $this->can_delete_video;
    }

    /**
     * Vérifie si le professeur peut administrer le site
     */
    public function canAdminister(): bool
    {
        return $this->is_admin || $this->can_administer;
    }

    /**
     * Obtient l'attribut role pour la compatibilité avec le code existant
     */
    public function getRoleAttribute(): string
    {
        return $this->is_admin ? 'admin' : 'professeur';
    }
}
