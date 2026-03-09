<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Participation;
use App\Models\Eleve;
use App\Models\Media;
use App\Models\Role;

class ParticipationFactory extends Factory
{
    protected $model = Participation::class;

    public function definition(): array
    {
        return [
            'eleve_id' => Eleve::factory(),
            'media_id' => Media::factory(),
            'role_id' => Role::factory(),
        ];
    }
}