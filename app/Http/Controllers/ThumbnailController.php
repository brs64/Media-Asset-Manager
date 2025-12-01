<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateMediaThumbnail;

class ThumbnailController extends Controller
{
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
