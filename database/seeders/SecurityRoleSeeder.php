<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SecurityRole;
use App\Models\SecurityPermission;

class SecurityRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Réinitialiser le cache des permissions
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        // Créer les permissions
        $permissions = [
            'modifier video',
            'diffuser video',
            'supprimer video',
            'administrer site',
        ];

        foreach ($permissions as $permission) {
            SecurityPermission::firstOrCreate(['name' => $permission]);
        }

        // Créer les rôles et assigner les permissions

        // Rôle Admin : toutes les permissions
        $adminRole = SecurityRole::firstOrCreate(['name' => 'admin']);
        $adminRole->syncPermissions(SecurityPermission::all());

        // Rôle Professeur : toutes les permissions sauf supprimer
        $professeurRole = SecurityRole::firstOrCreate(['name' => 'professeur']);
        $professeurRole->syncPermissions(['modifier video', 'diffuser video', 'administrer site']);

        // Rôle Élève : diffuser vidéo uniquement
        $eleveRole = SecurityRole::firstOrCreate(['name' => 'eleve']);
        $eleveRole->syncPermissions(['diffuser video']);
    }
}
