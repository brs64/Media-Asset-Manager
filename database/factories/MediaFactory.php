<?php

namespace Database\Factories;

use App\Models\Media;
use App\Models\Professeur;
use Illuminate\Database\Eloquent\Factories\Factory;

class MediaFactory extends Factory
{
    protected $model = Media::class;

    public function definition(): array
    {
        return [
            'mtd_tech_titre' => $this->faker->sentence(4),
            'promotion'     => $this->faker->optional()->year(),
            'type'          => $this->faker->optional()->randomElement([
                'video',
                'audio',
                'document',
            ]),
            'theme'         => $this->faker->optional()->word(),
            'description'   => $this->faker->optional()->paragraph(),
            'URI_NAS_ARCH'  => null,
            'URI_NAS_PAD'   => null,
            'chemin_local'  => $this->faker->optional()->filePath(),
            'properties'    => [
                'resolution' => '1920x1080',
                'format'     => 'mp4',
            ],
            'professeur_id' => Professeur::factory(),
        ];
    }

    /**
     * Média sans professeur
     */
    public function withoutProfesseur(): static
    {
        return $this->state(fn () => [
            'professeur_id' => null,
        ]);
    }

    /**
     * Média avec properties personnalisées
     */
    public function withProperties(array $properties): static
    {
        return $this->state(fn () => [
            'properties' => $properties,
        ]);
    }
}
