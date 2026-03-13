<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class ProfesseurFactory extends Factory
{
  // un faux prof 
public function definition(): array
{
    return [
        'user_id' => \App\Models\User::factory(), // Crée un compte User automatiquement
        'nom' => strtoupper($this->faker->lastName()),
        'prenom' => $this->faker->firstName(),
    ];
}}