<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Services\FileExplorerService;

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
            
            // 1. Scan Local Storage
            try {
                // Ensure 'external_local' is defined in config/filesystems.php
                $localTree = FileExplorerService::scanDisk('external_local', config('file_explorer.external_local.root'));
            } catch (\Exception $e) {
                $localTree = [];
            }

            // 2. Scan FTP PAD
            try {
                $nasPadTree = FileExplorerService::scanDisk('ftp_pad');
            } catch (\Exception $e) {
                $nasPadTree = [];
            }

            // 3. Scan FTP MPEG (formerly ARCH)
            try {
                $nasArchTree = FileExplorerService::scanDisk('ftp_mpeg');
            } catch (\Exception $e) {
                $nasArchTree = [];
            }

            // Inject variables into the view
            $view->with('localTree', $localTree);
            $view->with('nasPadTree', $nasPadTree);
            $view->with('nasArchTree', $nasArchTree);
        });
    }
}
