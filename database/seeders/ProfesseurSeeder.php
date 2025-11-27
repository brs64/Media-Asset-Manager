<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProfesseurSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $professeurs = [
            ['nom' => 'Dupont', 'prenom' => 'Jean'],
            ['nom' => 'Martin', 'prenom' => 'Sophie'],
            ['nom' => 'Bernard', 'prenom' => 'Pierre'],
        ];

        foreach ($professeurs as $profData) {
            $email = strtolower(substr($profData['prenom'], 0, 1) . '.' . $profData['nom']) . '@mediamanager.fr';

            // Créer d'abord le compte utilisateur (parent)
            $user = \App\Models\User::create([
                'name' => $profData['prenom'] . ' ' . $profData['nom'],
                'email' => $email,
                'password' => bcrypt('password'),
            ]);

            // Créer le profil professeur (enfant) lié à l'utilisateur
            \App\Models\Professeur::create([
                'user_id' => $user->id,
                'nom' => $profData['nom'],
                'prenom' => $profData['prenom'],
            ]);
        }
    }
}
