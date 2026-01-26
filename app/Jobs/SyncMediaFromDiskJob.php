<?php

namespace App\Jobs;

use App\Models\Media;
use App\Services\FileExplorerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncMediaFromDiskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $disk;
    public string $path;

    public $timeout = 0;
    public $tries = 1;

    protected string $uriField;

    public function __construct(string $disk, string $path = '/')
    {
        $this->disk = $disk;
        $this->path = $path;

        // Mapping NAS → champ BD
        $this->uriField = match ($disk) {
            'ftp_arch'       => 'URI_NAS_ARCH',
            'ftp_pad'        => 'URI_NAS_PAD',
            'external_local' => 'chemin_local',
            default => throw new \InvalidArgumentException("Disk non supporté: $disk"),
        };
    }

    public function handle()
    {
        Log::info('Sync Media démarrée', ['disk' => $this->disk]);

        FileExplorerService::scanDiskRecursive(
            $this->disk,
            $this->path,
            function ($item) {
                $this->handleItem($item);
            }
        );

        Log::info('Sync Media terminée', ['disk' => $this->disk]);
    }

    protected function handleItem(array $item): void
    {
        if (($item['type'] ?? '') !== 'video') {
            return;
        }

        // Normalisation pour la casse
        $title = pathinfo($item['name'], PATHINFO_FILENAME);
        $normalizedTitle = mb_strtolower(trim($title));

        // Cherche la vidéo existante (insensible à la casse)
        $media = Media::whereRaw('LOWER(mtd_tech_titre) = ?', [$normalizedTitle])->first();

        if (!$media) {
            $media = new Media();
            $media->mtd_tech_titre = $normalizedTitle;
            $media->type = 'video';
            $media->promotion = null;
            $media->theme = null;
            $media->description = null;
            $media->professeur_id = null;
        }



        // Mets à jour uniquement le NAS courant
        $media->{$this->uriField} = $item['path'];

        $media->save();
    }

}
