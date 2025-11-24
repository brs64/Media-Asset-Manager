<?php

/**
 * Fichier de fonctions helper globales
 * Ces fonctions sont chargées automatiquement et disponibles partout
 */

use App\Helpers\VideoHelper;

if (!function_exists('is_video')) {
    function is_video(string $filename): bool {
        return VideoHelper::isVideo($filename);
    }
}

if (!function_exists('format_file_size')) {
    function format_file_size(int $bytes): string {
        return VideoHelper::formatFileSize($bytes);
    }
}

if (!function_exists('extract_video_title')) {
    function extract_video_title(string $filename): string {
        return VideoHelper::extractVideoTitle($filename);
    }
}

if (!function_exists('get_thumbnail_name')) {
    function get_thumbnail_name(string $videoFilename): string {
        return VideoHelper::getThumbnailName($videoFilename);
    }
}
