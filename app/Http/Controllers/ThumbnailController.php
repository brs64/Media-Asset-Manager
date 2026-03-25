<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ThumbnailController extends Controller
{
    protected string $localThumbnailsPath;
    protected string $archivageMountPath;

    public function __construct()
    {
        $this->localThumbnailsPath = storage_path('app/public/thumbnails');
        $this->archivageMountPath = env('FILESYSTEM_LOCAL_PATH', '/mnt/archivage');

        if (!is_dir($this->localThumbnailsPath)) {
            mkdir($this->localThumbnailsPath, 0755, true);
        }
    }

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
        $thumbnailFilename = "{$mediaId}_miniature.jpg";
        $localPath = "{$this->localThumbnailsPath}/{$thumbnailFilename}";

        // 1. Check local storage first (cached copy)
        if (file_exists($localPath)) {
            return response()->file($localPath);
        }

        // 2. Try to find FFAStrans-generated thumbnail
        $media = Media::find($mediaId);
        if ($media && $media->chemin_local) {
            $ffastransThumbnail = $this->buildThumbnailPath($media->chemin_local);

            Log::info("Path of Thumbnail for media #{$mediaId} should be {$ffastransThumbnail}");

            if ($ffastransThumbnail && file_exists($ffastransThumbnail)) {
                // Copy to local storage for future requests
                if (copy($ffastransThumbnail, $localPath)) {
                    Log::info("Copied FFAStrans thumbnail for media #{$mediaId}");
                    return response()->file($localPath);
                }
                // If copy fails, serve directly from source
                return response()->file($ffastransThumbnail);
            }
        }

        // 3. No thumbnail available
        abort(404);
    }

    /**
     * Build thumbnail path from video path.
     * Thumbnails are in /mnt/Thumbnails/<year>/<project>/<filename>.jpg
     */
    protected function buildThumbnailPath(string $videoPath): ?string
    {
        // Split path into directory and filename
        $dir = dirname($videoPath);
        $filename = basename($videoPath);

        // Replace H264 with Thumbnails only in directory part (not filename)
        $thumbnailDir = str_replace('H264', 'Thumbnails', $dir);

        // Replace video extension with .jpg
        $thumbnailFilename = preg_replace('/\.(mp4|mov|mxf)$/i', '.jpg', $filename);

        // Build full path
        return "/mnt/miniatures/{$thumbnailDir}/{$thumbnailFilename}";
    }
}
