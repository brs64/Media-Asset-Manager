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
            // Direction
            ['libelle' => 'Réalisateur'],
            ['libelle' => 'Assistant réalisateur'],
            ['libelle' => 'Scénariste'],
            ['libelle' => 'Producteur'],

            // Image
            ['libelle' => 'Directeur de la photographie'],
            ['libelle' => 'Chef opérateur'],
            ['libelle' => 'Cadreur'],
            ['libelle' => 'Assistant caméra'],
            ['libelle' => 'Étalonneur'],

            // Son
            ['libelle' => 'Ingénieur du son'],
            ['libelle' => 'Perchman'],
            ['libelle' => 'Sound Designer'],
            ['libelle' => 'Mixeur son'],

            // Montage
            ['libelle' => 'Monteur'],
            ['libelle' => 'Assistant monteur'],
        ];

        foreach ($roles as $role) {
            \App\Models\Role::create($role);
        }
    }
}
