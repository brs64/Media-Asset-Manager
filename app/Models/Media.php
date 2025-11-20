<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Media extends Model
{
    use HasFactory;

    protected $fillable = [
        'promotion',
        'type',
        'theme',
        'description',
        'mtd_tech_titre',
        'mtd_tech_fps',
        'mtd_tech_resolution',
        'mtd_tech_duree',
        'mtd_tech_format',
        'URI_NAS_ARCH',
        'URI_NAS_PAD',
        'URI_NAS_MPEG',
        'projet_id',
        'professeur_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Un média appartient à un projet (nullable - peut être orphelin)
     */
    public function projet(): BelongsTo
    {
        return $this->belongsTo(Projet::class);
    }

    /**
     * Un média a un professeur référent (nullable)
     */
    public function professeur(): BelongsTo
    {
        return $this->belongsTo(Professeur::class);
    }

    /**
     * Un média a plusieurs élèves participants
     * Relation many-to-many via la table participations
     */
    public function eleves(): BelongsToMany
    {
        return $this->belongsToMany(Eleve::class, 'participations', 'media_id', 'eleve_id')
            ->withPivot('role_id')
            ->withTimestamps();
    }

    /**
     * Les participations de ce média
     */
    public function participations(): HasMany
    {
        return $this->hasMany(Participation::class);
    }
}
