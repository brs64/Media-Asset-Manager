import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/compte.css',
                'resources/css/formulaire.css',
                'resources/css/header.css',
                'resources/css/home.css',
                'resources/css/main.css',
                'resources/css/menuArbo.css',
                'resources/css/pageAdministration.css',
                'resources/css/popup.css',
                'resources/css/recherche.css',
                'resources/css/reconciliation.css',
                'resources/css/sauvegarde.css',
                'resources/css/transfert.css',
                'resources/css/video.css',
                'resources/js/app.js',
                'resources/js/video.js'
            ],
            refresh: true,
        }),
    ],
});
