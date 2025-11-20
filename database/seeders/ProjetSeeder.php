<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProjetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $projets = [
            ['libelle' => 'Projet Film Court-Métrage'],
            ['libelle' => 'Projet Documentaire'],
            ['libelle' => 'Projet Animation 2D'],
            ['libelle' => 'Projet Clip Musical'],
            ['libelle' => 'Projet Web Série'],
        ];

        foreach ($projets as $projet) {
            \App\Models\Projet::create($projet);
        }
    }
}
