<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class Autorisation extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
    \Illuminate\Support\Facades\DB::table('Autorisation')->insert([
        ['professeur' => 'amakdessi',      'modifier' => 0, 'supprimer' => 0, 'diffuser' => 0, 'administrer' => 0],
        ['professeur' => 'blagoarde',      'modifier' => 0, 'supprimer' => 0, 'diffuser' => 0, 'administrer' => 0],
        ['professeur' => 'claplace',       'modifier' => 0, 'supprimer' => 0, 'diffuser' => 0, 'administrer' => 0],
        ['professeur' => 'flafittehoussat','modifier' => 0, 'supprimer' => 0, 'diffuser' => 0, 'administrer' => 0],
        ['professeur' => 'gberthome',      'modifier' => 0, 'supprimer' => 0, 'diffuser' => 0, 'administrer' => 0],
        ['professeur' => 'jlmathieu',      'modifier' => 0, 'supprimer' => 0, 'diffuser' => 0, 'administrer' => 0],
        ['professeur' => 'jmjeault',       'modifier' => 0, 'supprimer' => 0, 'diffuser' => 0, 'administrer' => 0],
        ['professeur' => 'jmlamagnere',    'modifier' => 0, 'supprimer' => 0, 'diffuser' => 0, 'administrer' => 0],
        ['professeur' => 'jrlafourcade',   'modifier' => 0, 'supprimer' => 0, 'diffuser' => 0, 'administrer' => 0],
        ['professeur' => 'llagoardesegot', 'modifier' => 0, 'supprimer' => 0, 'diffuser' => 0, 'administrer' => 0],
        ['professeur' => 'lmariesainte',   'modifier' => 0, 'supprimer' => 0, 'diffuser' => 0, 'administrer' => 0],
        ['professeur' => 'nconguisti',     'modifier' => 1, 'supprimer' => 1, 'diffuser' => 1, 'administrer' => 1],
        ['professeur' => 'plucu',          'modifier' => 0, 'supprimer' => 0, 'diffuser' => 0, 'administrer' => 0],
        ['professeur' => 'pverdier',       'modifier' => 0, 'supprimer' => 0, 'diffuser' => 0, 'administrer' => 0],
        ['professeur' => 'schareyron',     'modifier' => 0, 'supprimer' => 0, 'diffuser' => 0, 'administrer' => 0],
        ['professeur' => 'slescoulier',    'modifier' => 0, 'supprimer' => 0, 'diffuser' => 0, 'administrer' => 0]
    ]);
    }
}
