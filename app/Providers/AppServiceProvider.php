<?php

namespace App\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Services\FileExplorerService;
use App\http\Controllers\FileExplorerController;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        
    // View Composer: Run this logic EVERY time 'menuArbo' is loaded

        View::composer('menuArbo', function ($view) {
            

            // UNIQUEMENT local (rapide)
            try {
            $directory = config('filesystems.disks.external_local.root'); // => /mnt/archivage
            $localTree = FileExplorerService::scanDisk('external_local', $directory);

            } catch (\Throwable $e) {
                $localTree = [];
            }

            // PAS de FTP ici
            $view->with([
                'localTree' => $localTree,
                'nasPadTree' => [],
                'nasArchTree' => [],
            ]);
        });
    }
}
