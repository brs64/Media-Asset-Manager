<?php
/*
  --------------------------------------------------------------------------
   Ancienne implémentation directe avec FFProbe & FFMpeg.
   Fonctionne, mais utilisation d'une équivalente avec un workflow FFASTrans sous demande du commanditaire pour unification sous un seul logiciel.
  --------------------------------------------------------------------------

namespace App\Jobs;

use App\Models\Media;
use App\Services\MediaThumbnailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateMediaThumbnail implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $mediaId
    ) {}

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
*/
