<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Eleve extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'prenom',
    ];

    /**
     * Un élève participe à plusieurs médias
     * Relation many-to-many via la table participations
     */
    public function media(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'participations', 'eleve_id', 'media_id')
            ->withPivot('role_id')
            ->withTimestamps();
    }

    /**
     * Participations de l'élève
     */
    public function participations()
    {
        return $this->hasMany(Participation::class);
    }
}
