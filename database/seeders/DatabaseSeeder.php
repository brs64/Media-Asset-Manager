<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Toujours exécuté : admin par défaut + rôles Spatie
        $this->call(ProductionSeeder::class);

        // Données de test uniquement en local/testing
        if (app()->environment('local', 'testing')) {
            $this->call([
                ProjetSeeder::class,
                ProfesseurSeeder::class,
                EleveSeeder::class,
                RoleSeeder::class,
                MediaSeeder::class,
            ]);
        }
    }
}
