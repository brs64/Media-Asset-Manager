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

        foreach ($eleves as $eleve) {
            \App\Models\Eleve::create($eleve);
        }
    }
}
