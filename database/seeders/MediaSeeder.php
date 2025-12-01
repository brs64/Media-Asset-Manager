<?php

namespace Database\Seeders;

use App\Models\Media;
use App\Models\Professeur;
use App\Models\Projet;
use Illuminate\Database\Seeder;

class MediaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $professeurs = Professeur::all();
        $projets = Projet::all();

        $medias = [
            [
                'promotion' => '2024',
                'type' => 'Court-metrage',
                'theme' => 'Drame',
                'description' => 'Un court-metrage sur la solitude urbaine',
                'mtd_tech_titre' => 'Exemple 1',
                'URI_NAS_PAD' => 'example1.mxf',
                'URI_NAS_MPEG' => 'example1.mp4',
            ],
            [
                'promotion' => '2024',
                'type' => 'Documentaire',
                'theme' => 'Nature',
                'description' => 'Documentaire sur la faune locale',
                'mtd_tech_titre' => 'Exemple 2',
                'URI_NAS_PAD' => 'example2.mxf',
                'URI_NAS_MPEG' => 'example2.mp4',
            ],
            [
                'promotion' => '2023',
                'type' => 'Clip',
                'theme' => 'Musique',
                'description' => 'Clip musical pour un groupe local',
                'mtd_tech_titre' => 'Exemple 3',
                'URI_NAS_PAD' => 'example3.mxf',
                'URI_NAS_MPEG' => 'example3.mp4',
            ],
        ];

        foreach ($medias as $index => $mediaData) {
            // Assigner un professeur aleatoirement si disponible
            if ($professeurs->isNotEmpty()) {
                $mediaData['professeur_id'] = $professeurs->random()->id;
            }

            $media = Media::create($mediaData);

            // Attacher a un ou plusieurs projets aleatoirement si disponibles
            if ($projets->isNotEmpty()) {
                $randomProjets = $projets->random(rand(1, min(2, $projets->count())));
                $media->projets()->attach($randomProjets->pluck('id'));
            }
        }
    }
}
