<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'libelle',
    ];

    /**
     * Un rôle peut être utilisé dans plusieurs participations
     */
    public function participations(): HasMany
    {
        return $this->hasMany(Participation::class);
    }
}
