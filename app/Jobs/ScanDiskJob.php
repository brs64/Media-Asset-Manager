<?php

namespace App\Jobs;

use App\Services\FileExplorerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * @brief Job asynchrone de scan de disque avec feedback en temps réel.
 *
 * Ce job permet de scanner récursivement un disque (FTP, NAS, local) pour
 * détecter tous les fichiers vidéo, avec mise à jour progressive de l'interface
 * utilisateur pendant le scan.
 *
 * Fonctionnalités principales :
 * - Scan récursif complet d'une arborescence de fichiers
 * - Filtrage automatique sur les fichiers de type vidéo
 * - Feedback en temps réel : mise à jour du compteur tous les 10 fichiers détectés
 * - Stockage des résultats en cache pour récupération ultérieure
 * - Gestion des erreurs avec statut d'échec
 *
 * Workflow :
 * 1. L'utilisateur lance un scan via l'interface
 * 2. Le job démarre en arrière-plan et stocke son statut dans le cache
 * 3. Tous les 10 fichiers trouvés, le cache est mis à jour (compteur + liste)
 * 4. L'interface peut interroger l'avancement via l'API de status
 * 5. À la fin, le statut passe à 'done' et les résultats complets sont disponibles
 *
 * Identifiant de scan : UUID unique permettant de suivre l'avancement du job
 */
class ScanDiskJob implements ShouldQueue
{

    private FileExplorerService $fileExplorerService;

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $disk;
    public string $path;
    public string $scanId;

    /** @brief Timeout du job en secondes (0 = illimité) */
    public $timeout = 0;

    /** @brief Nombre de tentatives en cas d'échec */
    public $tries = 1;

    /**
     * @brief Initialise le job de scan avec ses paramètres.
     *
     * @param string $disk Nom du disque Laravel à scanner (ex: ftp_pad, nas_arch)
     * @param string $path Chemin de départ du scan
     * @param string $scanId Identifiant unique (UUID) pour suivre l'avancement du scan
     */
    public function __construct(string $disk, string $path, string $scanId)
    {
        $this->disk = $disk;
        $this->path = $path;
        $this->scanId = $scanId;
        $this->fileExplorerService = new FileExplorerService();
    }

    /* public function handle()
    {
        try {
            Log::info("Scan FTP démarré", [
                'disk' => $this->disk,
                'path' => $this->path,
                'scan_id' => $this->scanId,
            ]);

            // Lancement du scan récursif
            $results = FileExplorerService::scanDiskRecursive($this->disk, $this->path);

            // Compter les fichiers de type 'video' par exemple
            $fileCount = collect($results)
                ->where('type', 'video')
                ->count();

            // Stocker les résultats complets
            Cache::put(
                "scan:{$this->scanId}:results",
                $results,
                now()->addHours(2)
            );

            // Stocker le nombre de fichiers OK
            Cache::put(
                "scan:{$this->scanId}:count",
                $fileCount,
                now()->addHours(2)
            );

            // Statut terminé
            Cache::put(
                "scan:{$this->scanId}:status",
                'done',
                now()->addHours(2)
            );

            Log::info("Scan FTP terminé", [
                'scan_id' => $this->scanId,
                'count' => $fileCount,
            ]);

        } catch (\Throwable $e) {
            Log::error("Erreur lors du scan FTP", [
                'scan_id' => $this->scanId,
                'disk' => $this->disk,
                'path' => $this->path,
                'message' => $e->getMessage(),
            ]);

            Cache::put(
                "scan:{$this->scanId}:status",
                'failed',
                now()->addHours(2)
            );

            // Stocker 0 fichiers si échec
            Cache::put(
                "scan:{$this->scanId}:count",
                0,
                now()->addHours(2)
            );
        }
    } */

    /**
     * @brief Exécute le scan du disque avec feedback progressif.
     *
     * Processus :
     * 1. Initialise un buffer temporaire pour accumuler les fichiers
     * 2. Scan récursif via FileExplorerService avec callback sur chaque fichier
     * 3. Tous les 10 fichiers vidéo détectés → flush du buffer vers le cache
     * 4. Flush final du buffer pour sauvegarder les fichiers restants
     * 5. Marquage du statut 'done' en cache
     *
     * En cas d'erreur, le statut passe à 'failed' et l'exception est loguée.
     *
     * Les données sont stockées dans le cache avec les clés :
     * - scan:{scanId}:status : 'running', 'done' ou 'failed'
     * - scan:{scanId}:results : tableau des fichiers détectés
     * - scan:{scanId}:count : nombre de fichiers vidéo
     *
     * Durée de vie en cache : 2 heures
     *
     * @return void
     */
    public function handle()
    {
        Log::info("Scan FTP démarré avec Live Feedback", ['scan_id' => $this->scanId]);

        // We will hold files here temporarily until we have 10 of them
        $buffer = []; 

        try {
            $this->fileExplorerService->scanDiskRecursive(
                $this->disk, 
                $this->path, 
                function ($item) use (&$buffer) {
                    
                    // We only care about VIDEO files for the live counter
                    if (($item['type'] ?? '') === 'video') {
                        $buffer[] = $item;

                        // EVERY 10 VIDEOS -> Update the Cache
                        if (count($buffer) >= 10) {
                            $this->flushBufferToCache($buffer);
                            $buffer = []; // Empty the buffer
                        }
                    }
                }
            );

            // Save any remaining files in the buffer (e.g. the last 4 files)
            if (count($buffer) > 0) {
                $this->flushBufferToCache($buffer);
            }

            // Mark as DONE
            Cache::put("scan:{$this->scanId}:status", 'done', now()->addHours(2));
            Log::info("Scan FTP terminé avec succès");

        } catch (\Throwable $e) {
            Log::error("Erreur Scan: " . $e->getMessage());
            Cache::put("scan:{$this->scanId}:status", 'failed', now()->addHours(2));
        }
    }

    /**
     * @brief Vide le buffer de fichiers dans le cache pour mise à jour progressive.
     *
     * Cette méthode permet d'afficher le compteur de fichiers en temps réel
     * dans l'interface utilisateur sans attendre la fin complète du scan.
     *
     * Fonctionnement :
     * 1. Récupère la liste actuelle depuis le cache
     * 2. Fusionne avec les nouveaux fichiers du buffer
     * 3. Sauvegarde la liste mise à jour en cache
     * 4. Met à jour le compteur visible (pour l'affichage frontend)
     *
     * @param array $newFiles Tableau de nouveaux fichiers à ajouter au cache
     * @return void
     */
    protected function flushBufferToCache(array $newFiles)
    {
        $cacheKey = "scan:{$this->scanId}:results";
        
        // 1. Get current list
        $currentList = Cache::get($cacheKey, []);
        
        // 2. Merge new files
        $updatedList = array_merge($currentList, $newFiles);
        
        // 3. Save back to cache
        Cache::put($cacheKey, $updatedList, now()->addHours(2));

        // 4. Update the visible counter
        Cache::put("scan:{$this->scanId}:count", count($updatedList), now()->addHours(2));
    }
}
