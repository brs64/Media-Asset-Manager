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

    protected $table = 'medias';

    protected $fillable = [
        'id',
        'promotion',
        'type',
        'theme',
        'description',
        'mtd_tech_titre',
        'URI_NAS_ARCH',
        'URI_NAS_PAD',
        'URI_NAS_MPEG',
        'professeur_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function projets(): BelongsToMany
    {
        return $this->belongsToMany(Projet::class, 'media_projet')
            ->withTimestamps();
    }

    public function professeur(): BelongsTo
    {
        return $this->belongsTo(Professeur::class);
    }

    public function eleves(): BelongsToMany
    {
        return $this->belongsToMany(Eleve::class, 'participations', 'media_id', 'eleve_id')
            ->withPivot('role_id')
            ->withTimestamps();
    }

    public function participations(): HasMany
    {
        return $this->hasMany(Participation::class);
    }
}
