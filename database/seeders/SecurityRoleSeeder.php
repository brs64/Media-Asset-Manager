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
            SecurityPermission::create(['name' => $permission]);
        }

        // Créer les rôles et assigner les permissions

        // Rôle Admin : toutes les permissions
        $adminRole = SecurityRole::create(['name' => 'admin']);
        $adminRole->givePermissionTo(SecurityPermission::all());

        // Rôle Professeur : toutes les permissions sauf supprimer
        $professeurRole = SecurityRole::create(['name' => 'professeur']);
        $professeurRole->givePermissionTo(['modifier video', 'diffuser video', 'administrer site']);

        // Rôle Élève : diffuser vidéo uniquement
        $eleveRole = SecurityRole::create(['name' => 'eleve']);
        $eleveRole->givePermissionTo(['diffuser video']);
    }
}
