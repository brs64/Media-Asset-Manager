<?php

namespace App\Jobs;

use App\Models\Media;
use App\Services\MediaThumbnailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * @brief Job asynchrone de génération de miniature vidéo.
 *
 * Ce job permet de créer une image miniature (thumbnail) à partir d'une vidéo
 * en mode asynchrone, évitant ainsi de bloquer l'interface utilisateur.
 *
 * Fonctionnement :
 * - Déclenché automatiquement lors de l'accès à une miniature inexistante
 * - Extrait une frame de la vidéo via FFmpeg
 * - Sauvegarde la miniature dans storage/app/public/thumbnails/
 * - Format : {media_id}_miniature.jpg
 *
 * Avantages :
 * - Chargement instantané des pages (affichage placeholder pendant génération)
 * - Traitement en arrière-plan sans impact sur les performances
 * - Gestion automatique des erreurs et logs
 */
class GenerateMediaThumbnail implements ShouldQueue
{
    use Queueable;

    /**
     * @brief Initialise le job avec l'identifiant du média à traiter.
     *
     * @param int $mediaId Identifiant du média pour lequel générer une miniature
     */
    public function __construct(
        public int $mediaId
    ) {}

    /**
     * @brief Exécute la génération de la miniature.
     *
     * Récupère le média depuis la base de données et délègue la génération
     * de la miniature au service spécialisé MediaThumbnailService.
     *
     * Si le média n'existe pas ou plus, un warning est logué et le job se termine
     * silencieusement sans erreur.
     *
     * @param MediaThumbnailService $service Service de génération de miniatures (injection automatique)
     * @return void
     */
    public function handle(MediaThumbnailService $service): void
    {
        $media = Media::find($this->mediaId);

        if (!$media) {
            Log::warning("GenerateMediaThumbnail: Media #{$this->mediaId} not found");
            return;
        }

        $service->generateThumbnail($media);
    }
}
