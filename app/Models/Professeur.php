<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Professeur extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'prenom',
        'identifiant',
        'mot_de_passe',
    ];

    protected $hidden = [
        'mot_de_passe',
    ];

    /**
     * Un professeur est référent de plusieurs médias
     */
    public function media(): HasMany
    {
        return $this->hasMany(Media::class, 'professeur_id');
    }
}
