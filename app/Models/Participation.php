<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Participation extends Model
{
    use HasFactory;

    protected $fillable = [
        'eleve_id',
        'media_id',
        'role_id',
    ];

    /**
     * L'élève qui participe
     */
    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class);
    }

    /**
     * Le média concerné
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    /**
     * Le rôle de l'élève dans ce média
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
