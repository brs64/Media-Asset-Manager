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
                'mot_de_passe' => bcrypt('password'),
            ],
            [
                'nom' => 'Martin',
                'prenom' => 'Sophie',
                'identifiant' => 's.martin',
                'mot_de_passe' => bcrypt('password'),
            ],
            [
                'nom' => 'Bernard',
                'prenom' => 'Pierre',
                'identifiant' => 'p.bernard',
                'mot_de_passe' => bcrypt('password'),
            ],
        ];

        foreach ($professeurs as $professeur) {
            \App\Models\Professeur::create($professeur);
        }
    }
}
