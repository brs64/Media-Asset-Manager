<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateMediaThumbnail;

class ThumbnailController extends Controller
{
    /**
     * @brief Affiche la miniature d'un média ou génère-la si elle n'existe pas.
     *
     * Fonctionnement :
     * 1. Si la miniature existe déjà → la retourner immédiatement
     * 2. Si la miniature n'existe pas → lancer un job de génération asynchrone
     * 3. Pendant la génération → retourner une image placeholder
     * 4. Si aucun placeholder n'existe → retourner une réponse vide (204)
     *
     * Cette approche permet un chargement rapide des pages tout en générant
     * les miniatures de manière asynchrone en arrière-plan.
     *
     * @param int $mediaId Identifiant du média dont on veut la miniature
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\Response
     * Image de la miniature, placeholder ou réponse vide
     */
    public function show(int $mediaId)
    {
        $thumbnailPath = storage_path("app/public/thumbnails/{$mediaId}_miniature.jpg");

        if (file_exists($thumbnailPath)) {
            return response()->file($thumbnailPath);
        }

        GenerateMediaThumbnail::dispatch($mediaId);

        $placeholderPath = public_path('images/placeholder-video.png');

        if (file_exists($placeholderPath)) {
            return response()->file($placeholderPath);
        }

        return response()->noContent();
    }
}
