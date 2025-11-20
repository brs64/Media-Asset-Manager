<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['libelle' => 'Réalisateur'],
            ['libelle' => 'Scénariste'],
            ['libelle' => 'Chef opérateur'],
            ['libelle' => 'Monteur'],
            ['libelle' => 'Acteur'],
            ['libelle' => 'Sound Designer'],
            ['libelle' => 'Producteur'],
            ['libelle' => 'Assistant réalisateur'],
        ];

        foreach ($roles as $role) {
            \App\Models\Role::create($role);
        }
    }
}
