<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    /**
     * Seed de production : compte admin par défaut + rôles Spatie.
     */
    public function run(): void
    {
        $this->call(SecurityRoleSeeder::class);

        $admin = User::firstOrCreate(
            ['name' => 'admin'],
            [
                'nom' => 'Administrateur',
                'prenom' => 'Compte',
                'password' => bcrypt(env('ADMIN_PASSWORD', 'admin')),
            ]
        );

        $admin->assignRole('admin');
    }
}
