<?php

namespace App\Helpers;

/**
 * Helper contenant des fonctions utilitaires pour la gestion des vidéos
 */
class VideoHelper
{
    /**
     * Vérifie si un fichier est une vidéo basé sur son extension
     */
    public static function isVideo(string $filename): bool
    {
        $videoExtensions = ['mp4', 'mxf', 'avi', 'mov', 'wmv', 'flv', 'mkv'];
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return in_array($extension, $videoExtensions);
    }

    /**
     * Récupère le nom du fichier sans son extension
     */
    public static function getFilenameWithoutExtension(string $filename): string
    {
        return pathinfo($filename, PATHINFO_FILENAME);
    }

    /**
     * Force l'extension d'un fichier en .mp4
     */
    public static function forceExtensionMp4(string $filename): string
    {
        $filenameWithoutExt = self::getFilenameWithoutExtension($filename);
        return $filenameWithoutExt . '.mp4';
    }

    /**
     * Force l'extension d'un fichier en .mxf
     */
    public static function forceExtensionMxf(string $filename): string
    {
        $filenameWithoutExt = self::getFilenameWithoutExtension($filename);
        return $filenameWithoutExt . '.mxf';
    }

    /**
     * Récupère l'extension d'un fichier
     */
    public static function getFileExtension(string $filename): string
    {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }

    /**
     * Extrait le titre de la vidéo depuis le nom de fichier
     * Format attendu: ANNEE_PROJET_TITRE.extension
     */
    public static function extractVideoTitle(string $filename): string
    {
        $matches = [];
        if (preg_match("/^[^_]*_[^_]*_(.*)(?=\.)/", $filename, $matches)) {
            if (isset($matches[1]) && !empty($matches[1])) {
                return $matches[1];
            }
        }

        // Si le format n'est pas reconnu, retourner le nom sans extension
        return self::getFilenameWithoutExtension($filename);
    }

    /**
     * Génère le nom de la miniature d'une vidéo
     */
    public static function getThumbnailName(string $videoFilename): string
    {
        $filenameWithoutExt = self::getFilenameWithoutExtension($videoFilename);
        return $filenameWithoutExt . '_thumbnail.jpg';
    }

    /**
     * Vérifie si un nom de fichier ne contient pas de caractères spéciaux
     */
    public static function hasNoSpecialCharacters(string $filename): bool
    {
        return !preg_match('/[%\s()\'"]/', $filename);
    }

    /**
     * Formate la taille d'un fichier en unités lisibles
     */
    public static function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Extrait l'année depuis le nom de fichier
     * Format attendu: ANNEE_PROJET_TITRE.extension
     */
    public static function extractYearFromFilename(string $filename): ?string
    {
        $matches = [];
        if (preg_match("/^(\d{4})_/", $filename, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Extrait le projet depuis le nom de fichier
     * Format attendu: ANNEE_PROJET_TITRE.extension
     */
    public static function extractProjectFromFilename(string $filename): ?string
    {
        $matches = [];
        if (preg_match("/^\d{4}_([^_]+)_/", $filename, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Vérifie la correspondance entre deux noms de vidéos (sans tenir compte de l'extension)
     */
    public static function areVideosMatching(string $filename1, string $filename2): bool
    {
        $path1 = pathinfo($filename1, PATHINFO_DIRNAME);
        $path2 = pathinfo($filename2, PATHINFO_DIRNAME);

        $name1 = self::getFilenameWithoutExtension($filename1);
        $name2 = self::getFilenameWithoutExtension($filename2);

        return ($path1 === $path2 && $name1 === $name2);
    }

    /**
     * Génère un chemin de stockage local pour une vidéo
     */
    public static function generateLocalStoragePath(string $filename): string
    {
        $filenameWithoutExt = self::getFilenameWithoutExtension($filename);
        $year = self::extractYearFromFilename($filename);
        $project = self::extractProjectFromFilename($filename);

        if ($year && $project) {
            return $year . '/' . $project . '/' . $filenameWithoutExt . '/';
        }

        return 'autres/' . $filenameWithoutExt . '/';
    }

    /**
     * Parse une liste de personnes séparées par des virgules
     */
    public static function parsePersonList(string $list): array
    {
        if (empty(trim($list))) {
            return [];
        }

        // Normaliser et séparer
        $list = trim(preg_replace('/\s*,\s*/', ', ', $list));
        return array_filter(array_map('trim', explode(', ', $list)));
    }

    /**
     * Vérifie si une vidéo est au format HD
     */
    public static function isHD(string $resolution): bool
    {
        return preg_match('/1920x1080|1280x720/', $resolution) === 1;
    }

    /**
     * Vérifie si une vidéo est au format 4K
     */
    public static function is4K(string $resolution): bool
    {
        return preg_match('/3840x2160|4096x2160/', $resolution) === 1;
    }
}
