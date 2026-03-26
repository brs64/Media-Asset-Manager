<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * @brief Service d'exploration de fichiers sur différents types de stockage.
 *
 * Ce service permet de naviguer et d'explorer les fichiers présents sur
 * différents disques (locaux, FTP, NAS) de manière uniforme et sécurisée.
 *
 * Fonctionnalités :
 * - Scan d'un niveau de dossier (non récursif)
 * - Scan récursif complet d'une arborescence
 * - Détection automatique des fichiers vidéo
 * - Support de callbacks pour traitement en temps réel
 * - Compatible avec tous les disques Laravel (local, FTP, S3, etc.)
 *
 * Types de disques supportés :
 * - external_local : Disque local avec accès système direct
 * - ftp_pad, ftp_arch : Serveurs FTP/NAS via Laravel Storage
 * - Tout autre disque configuré dans filesystems.disks
 */
class FileExplorerService
{
    /**
     * @brief Scanne un dossier sur un niveau (non récursif).
     *
     * Retourne uniquement les dossiers et les fichiers vidéo détectés.
     * Les autres types de fichiers sont filtrés et ignorés.
     *
     * Comportement spécial pour 'external_local' :
     * - Accès direct au système de fichiers (scandir)
     * - Chemins absolus renvoyés
     *
     * Comportement pour les autres disques :
     * - Utilise Laravel Storage
     * - Chemins relatifs renvoyés
     *
     * @param string $diskName Nom du disque Laravel à scanner
     * @param string $directory Chemin du dossier à scanner
     * @return array Liste triée alphabétiquement contenant :
     *               - 'type' : 'folder' ou 'video'
     *               - 'name' : nom du fichier/dossier
     *               - 'path' : chemin complet
     *               - 'disk' : nom du disque
     *               - 'id' : null (réservé pour enrichissement ultérieur)
     */
    public function scanDisk(string $diskName, string $directory): array
    {

        $directory = rtrim($directory, '/\\');

        // Cas normal : NAS / FTP / disque Laravel
        $disk = Storage::disk($diskName);
        $results = [];

        try {
            $directories = $disk->directories($directory);
            sort($directories);
            foreach ($directories as $dirPath) {
                $results[] = [
                    'type' => 'folder',
                    'name' => basename($dirPath),
                    'path' => $dirPath,
                    'disk' => $diskName,
                ];
                if ($diskName === 'external_local') {
                    Log::info("En local : {$dirPath} trouvé");
                }
            }

            $files = $disk->files($directory);
            sort($files);
            foreach ($files as $filePath) {
                if ($diskName === 'external_local') {
                    Log::info("En local : {$filePath} trouvé");
                }
                $fileName = basename($filePath);
                if ($fileName === '.gitkeep' || str_starts_with($fileName, '.')) continue;
                if ($this->isVideo($fileName)) {
                    $results[] = [
                        'type' => 'video',
                        'name' => $fileName,
                        'path' => $filePath,
                        'disk' => $diskName,
                        'id'   => null,
                    ];
                }
            }
        } catch (\Throwable $e) {
            return [];
        }

        return $results;
    }

    /**
     * @brief Scanne récursivement un disque et tous ses sous-dossiers.
     *
     * Cette méthode parcourt l'intégralité d'une arborescence de fichiers
     * et appelle optionnellement un callback pour chaque élément trouvé.
     *
     * Fonctionnalités :
     * - Exploration récursive complète
     * - Callback en temps réel pour traitement à la volée
     * - Augmentation des limites PHP pour gros volumes (512 Mo RAM, 600 sec)
     *
     * Cas d'usage typiques :
     * - Synchronisation de base de données
     * - Comptage de fichiers en temps réel
     * - Indexation de médias
     *
     * @param string $diskName Nom du disque Laravel à scanner
     * @param string $path Chemin de départ (défaut : racine '/')
     * @param callable|null $onItemFound Fonction appelée pour chaque élément trouvé.
     *                                   Signature : function(array $item): void
     *                                   où $item contient : type, name, path, disk, id
     * @return array Tableau vide (legacy - utiliser le callback pour traiter les résultats)
     */
    public function scanDiskRecursive(string $diskName, string $path = '/', callable $onItemFound = null)
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 600);

        if ($diskName === "external_local") {
            Log::info("Scan du chemin local {$path}");
        }

        $allFiles = [];
        
        $items = $this->scanDisk($diskName, $path);

        foreach ($items as $item) {
            if ($onItemFound) $onItemFound($item);

            if ($item['type'] === 'folder') {
                $this->scanDiskRecursive($diskName, $item['path'], $onItemFound);
            }
        }

        return $allFiles;
    }

    /**
     * @brief Détermine si un fichier est une vidéo en se basant sur son extension.
     *
     * Extensions vidéo supportées :
     * - mp4, mov, avi, mkv, webm, m4v, mxf
     *
     * La vérification est insensible à la casse.
     *
     * @param string $filename Nom du fichier (avec ou sans chemin)
     * @return bool true si l'extension correspond à une vidéo, false sinon
     */
    public function isVideo(string $filename): bool
    {
        return preg_match('/\.(mp4|mov|avi|mkv|webm|m4v|mxf)$/i', $filename);
    }
}