<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EleveSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $eleves = [
            ['nom' => 'Leroy', 'prenom' => 'Thomas'],
            ['nom' => 'Moreau', 'prenom' => 'Julie'],
            ['nom' => 'Simon', 'prenom' => 'Lucas'],
            ['nom' => 'Laurent', 'prenom' => 'Emma'],
            ['nom' => 'Lefebvre', 'prenom' => 'Hugo'],
            ['nom' => 'Michel', 'prenom' => 'Léa'],
            ['nom' => 'Garcia', 'prenom' => 'Nathan'],
            ['nom' => 'David', 'prenom' => 'Chloé'],
        ];

        foreach ($eleves as $eleveData) {
            // Créer d'abord le compte utilisateur (parent)
            $email = strtolower($eleveData['prenom'] . '.' . $eleveData['nom']) . '@mediamanager.fr';
            $user = \App\Models\User::create([
                'name' => $eleveData['prenom'] . ' ' . $eleveData['nom'],
                'email' => $email,
                'password' => bcrypt('password'),
            ]);

            // Créer le profil élève (enfant) lié à l'utilisateur
            \App\Models\Eleve::create([
                'user_id' => $user->id,
                'nom' => $eleveData['nom'],
                'prenom' => $eleveData['prenom'],
            ]);
        }
    }
}
