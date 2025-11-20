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
            [
                'nom' => 'Dupont',
                'prenom' => 'Jean',
                'identifiant' => 'j.dupont',
            ],
            [
                'nom' => 'Martin',
                'prenom' => 'Sophie',
                'identifiant' => 's.martin',
            ],
            [
                'nom' => 'Bernard',
                'prenom' => 'Pierre',
                'identifiant' => 'p.bernard',
            ],
        ];

        foreach ($professeurs as $profData) {
            // Créer d'abord le compte utilisateur (parent)
            $user = \App\Models\User::create([
                'name' => $profData['prenom'] . ' ' . $profData['nom'],
                'email' => $profData['identifiant'] . '@mediamanager.fr',
                'password' => bcrypt('password'),
            ]);

            // Créer le profil professeur (enfant) lié à l'utilisateur
            \App\Models\Professeur::create([
                'user_id' => $user->id,
                'nom' => $profData['nom'],
                'prenom' => $profData['prenom'],
                'identifiant' => $profData['identifiant'],
            ]);
        }
    }
}
