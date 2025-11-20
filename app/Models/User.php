<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Relation vers le profil professeur (si l'utilisateur est un professeur)
     */
    public function professeur()
    {
        return $this->hasOne(Professeur::class);
    }

    /**
     * Relation vers le profil élève (si l'utilisateur est un élève)
     */
    public function eleve()
    {
        return $this->hasOne(Eleve::class);
    }

    /**
     * Vérifie si l'utilisateur est un professeur
     */
    public function isProfesseur(): bool
    {
        return $this->professeur()->exists();
    }

    /**
     * Vérifie si l'utilisateur est un élève
     */
    public function isEleve(): bool
    {
        return $this->eleve()->exists();
    }

    /**
     * Obtient le profil (professeur ou élève) de l'utilisateur
     */
    public function getProfile()
    {
        return $this->professeur ?? $this->eleve;
    }
}
