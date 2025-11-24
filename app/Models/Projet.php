<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Projet extends Model
{
    use HasFactory;

    protected $fillable = [
        'libelle',
    ];

    /**
     * Un projet peut avoir plusieurs medias
     */
    public function medias(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'media_projet')
            ->withTimestamps();
    }
}
