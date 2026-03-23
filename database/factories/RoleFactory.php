<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'libelle' => $this->faker->randomElement(['Réalisateur', 'Acteur', 'Monteur', 'Caméraman', 'Scénariste']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
