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
                'mtd_tech_titre' => 'Solitude',
                'mtd_tech_fps' => '24',
                'mtd_tech_resolution' => '1920x1080',
                'mtd_tech_duree' => '00:12:30',
                'mtd_tech_format' => 'MP4',
            ],
            [
                'promotion' => '2024',
                'type' => 'Documentaire',
                'theme' => 'Nature',
                'description' => 'Documentaire sur la faune locale',
                'mtd_tech_titre' => 'Vie Sauvage',
                'mtd_tech_fps' => '30',
                'mtd_tech_resolution' => '3840x2160',
                'mtd_tech_duree' => '00:25:00',
                'mtd_tech_format' => 'MOV',
            ],
            [
                'promotion' => '2023',
                'type' => 'Clip',
                'theme' => 'Musique',
                'description' => 'Clip musical pour un groupe local',
                'mtd_tech_titre' => 'Rythmes Urbains',
                'mtd_tech_fps' => '60',
                'mtd_tech_resolution' => '1920x1080',
                'mtd_tech_duree' => '00:04:15',
                'mtd_tech_format' => 'MP4',
            ],
            [
                'promotion' => '2024',
                'type' => 'Court-metrage',
                'theme' => 'Comedie',
                'description' => 'Une comedie sur la vie etudiante',
                'mtd_tech_titre' => 'Partiels',
                'mtd_tech_fps' => '24',
                'mtd_tech_resolution' => '1920x1080',
                'mtd_tech_duree' => '00:08:45',
                'mtd_tech_format' => 'MP4',
            ],
            [
                'promotion' => '2023',
                'type' => 'Reportage',
                'theme' => 'Social',
                'description' => 'Reportage sur les associations etudiantes',
                'mtd_tech_titre' => 'Engagement',
                'mtd_tech_fps' => '25',
                'mtd_tech_resolution' => '1920x1080',
                'mtd_tech_duree' => '00:15:00',
                'mtd_tech_format' => 'MP4',
            ],
            [
                'promotion' => '2024',
                'type' => 'Fiction',
                'theme' => 'Thriller',
                'description' => 'Court thriller psychologique',
                'mtd_tech_titre' => 'Ombres',
                'mtd_tech_fps' => '24',
                'mtd_tech_resolution' => '2560x1440',
                'mtd_tech_duree' => '00:18:20',
                'mtd_tech_format' => 'MOV',
            ],
            [
                'promotion' => '2023',
                'type' => 'Animation',
                'theme' => 'Fantaisie',
                'description' => 'Court-metrage anime en 2D',
                'mtd_tech_titre' => 'Le Voyage',
                'mtd_tech_fps' => '24',
                'mtd_tech_resolution' => '1920x1080',
                'mtd_tech_duree' => '00:06:00',
                'mtd_tech_format' => 'MP4',
            ],
            [
                'promotion' => '2024',
                'type' => 'Publicite',
                'theme' => 'Commercial',
                'description' => 'Spot publicitaire pour une entreprise locale',
                'mtd_tech_titre' => 'Saveurs du Terroir',
                'mtd_tech_fps' => '30',
                'mtd_tech_resolution' => '1920x1080',
                'mtd_tech_duree' => '00:00:45',
                'mtd_tech_format' => 'MP4',
            ],
            [
                'promotion' => '2023',
                'type' => 'Court-metrage',
                'theme' => 'Science-fiction',
                'description' => 'Vision dystopique du futur',
                'mtd_tech_titre' => '2084',
                'mtd_tech_fps' => '24',
                'mtd_tech_resolution' => '3840x2160',
                'mtd_tech_duree' => '00:14:30',
                'mtd_tech_format' => 'MOV',
            ],
            [
                'promotion' => '2024',
                'type' => 'Documentaire',
                'theme' => 'Portrait',
                'description' => 'Portrait d\'un artisan local',
                'mtd_tech_titre' => 'Mains de Maitre',
                'mtd_tech_fps' => '25',
                'mtd_tech_resolution' => '1920x1080',
                'mtd_tech_duree' => '00:20:00',
                'mtd_tech_format' => 'MP4',
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
