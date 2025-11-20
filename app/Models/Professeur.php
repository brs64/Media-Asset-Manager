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
        'identifiant',
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
}
