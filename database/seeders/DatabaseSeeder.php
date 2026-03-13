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
        $this->call([
            SecurityRoleSeeder::class, // D'abord les rôles et permissions Spatie
            ProjetSeeder::class,
            ProfesseurSeeder::class,
            EleveSeeder::class,
            RoleSeeder::class,
            MediaSeeder::class,
        ]);
    }
}
