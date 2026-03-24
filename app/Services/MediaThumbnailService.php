<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Support\Facades\Log;

/**
 * @brief Service de génération de miniatures vidéo via FFmpeg.
 *
 * Ce service permet de créer automatiquement des images miniatures (thumbnails)
 * à partir de fichiers vidéo, que ce soit depuis le stockage local ou distant (FTP).
 *
 * Fonctionnalités principales :
 * - Génération de miniatures depuis fichiers locaux ou FTP
 * - Extraction automatique de frame à mi-durée de la vidéo
 * - Détection de la durée vidéo via FFprobe
 * - Régénération en masse des miniatures manquantes
 * - Suppression de miniatures
 *
 * Outils externes requis :
 * - FFmpeg : extraction de frames vidéo
 * - FFprobe : analyse des métadonnées vidéo
 *
 * Configuration :
 * - Chemin FFmpeg/FFprobe dans config/media.php
 * - Stockage : storage/app/public/thumbnails/
 * - Largeur par défaut : 320px (hauteur auto)
 * - Format : JPEG avec qualité 3 (balance qualité/poids)
 */
class MediaThumbnailService
{
    /** @brief Chemin vers l'exécutable FFmpeg */
    protected string $ffmpegPath;

    /** @brief Chemin vers l'exécutable FFprobe */
    protected string $ffprobePath;

    /** @brief Dossier de stockage des miniatures */
    protected string $thumbnailsPath;

    /** @brief Largeur par défaut des miniatures en pixels */
    protected int $defaultWidth = 320;

    /** @brief Suffixe ajouté aux noms de fichiers miniatures */
    protected string $thumbnailSuffix = '_miniature.jpg';

    /**
     * @brief Initialise le service avec les chemins FFmpeg et le dossier de miniatures.
     *
     * Crée automatiquement le dossier de miniatures s'il n'existe pas.
     */
    public function __construct()
    {
        $this->ffmpegPath = config('media.ffmpeg_path', '/usr/bin/ffmpeg');
        $this->ffprobePath = config('media.ffprobe_path', '/usr/bin/ffprobe');
        $this->thumbnailsPath = storage_path('app/public/thumbnails');

        if (!is_dir($this->thumbnailsPath)) {
            mkdir($this->thumbnailsPath, 0755, true);
        }
    }

    /**
     * @brief Génère une miniature pour un média donné.
     *
     * Cette méthode tente de générer une miniature selon l'ordre de priorité suivant :
     * 1. Fichier local (chemin_local) si disponible
     * 2. Fichier sur NAS ARCH (URI_NAS_ARCH)
     * 3. Fichier sur NAS PAD (URI_NAS_PAD)
     *
     * Processus :
     * - Vérifie si la miniature existe déjà (sauf si force=true)
     * - Détermine la durée de la vidéo via FFprobe
     * - Extrait une frame à mi-durée (ou à 5 secondes par défaut)
     * - Sauvegarde la miniature au format JPEG
     *
     * Optimisation FTP :
     * - Utilise l'option -ss avant -i pour extraction rapide (single frame)
     * - Évite de télécharger toute la vidéo
     *
     * @param Media $media Modèle du média pour lequel générer la miniature
     * @param string|null $videoPath Chemin vidéo personnalisé (optionnel, override auto-détection)
     * @param bool $force Force la régénération même si la miniature existe (défaut : false)
     * @return string|null Chemin complet vers la miniature générée, ou null en cas d'échec
     */
    public function generateThumbnail(Media $media, ?string $videoPath = null, bool $force = false): ?string
    {
        $thumbnailFilename = $media->id . $this->thumbnailSuffix;
        $thumbnailPath = $this->thumbnailsPath . '/' . $thumbnailFilename;

        if (!$force && file_exists($thumbnailPath)) {
            return $thumbnailPath;
        }

        // Priorité: chemin_local > ARCH > PAD
        // Si chemin_local existe, générer depuis le fichier local
        if ($media->chemin_local) {
            $localPath = storage_path('app/' . ltrim($media->chemin_local, '/'));
            if (file_exists($localPath)) {
                Log::info("Generating thumbnail from local file: {$localPath}");
                $duration = $this->getVideoDuration($localPath);
                $timecode = $duration ? $this->calculateTimecode($duration) : 5;
                $success = $this->executeFfmpeg($localPath, $thumbnailPath, $timecode);
                if ($success) {
                    Log::info("Thumbnail generated for media #{$media->id}");
                    return $thumbnailPath;
                }
            }
        }

        // Fallback FTP (ARCH > PAD)
        $remoteVideoPath = $videoPath ?? $media->URI_NAS_ARCH ?? $media->URI_NAS_PAD;

        if (!$remoteVideoPath) {
            Log::warning("No video path for media #{$media->id}");
            return null;
        }

        // Build FTP URL
        $ftpUrl = $this->buildFtpUrl($media, $remoteVideoPath);
        if (!$ftpUrl) {
            Log::warning("Unable to build FTP URL for media #{$media->id}");
            return null;
        }

        // Get video duration directly from FTP
        $duration = $this->getVideoDuration($ftpUrl);
        $timecode = $duration ? $this->calculateTimecode($duration) : 5;

        // Generate thumbnail directly from FTP
        $success = $this->executeFfmpeg($ftpUrl, $thumbnailPath, $timecode);

        if ($success) {
            Log::info("Thumbnail generated for media #{$media->id}");
            return $thumbnailPath;
        }

        Log::error("Failed to generate thumbnail for media #{$media->id}");
        return null;
    }

    /**
     * @brief Construit une URL FTP complète pour accéder à une vidéo distante.
     *
     * Utilise les informations de connexion FTP configurées dans filesystems.disks
     * pour construire une URL au format : ftp://user:pass@host/path
     *
     * @param Media $media Modèle du média (pour déterminer le disque ARCH ou PAD)
     * @param string $remoteVideoPath Chemin du fichier vidéo sur le serveur FTP
     * @return string|null URL FTP complète, ou null si le disque n'est pas déterminable
     */
    protected function buildFtpUrl(Media $media, string $remoteVideoPath): ?string
    {
        // Determine FTP disk (ARCH > PAD)
        $ftpDisk = null;
        if ($media->URI_NAS_ARCH) {
            $ftpDisk = 'ftp_arch';
        } elseif ($media->URI_NAS_PAD) {
            $ftpDisk = 'ftp_pad';
        }

        if (!$ftpDisk) {
            return null;
        }

        // Build FTP URL
        $config = config("filesystems.disks.{$ftpDisk}");
        if (!$config) {
            return null;
        }
        return sprintf(
            'ftp://%s:%s@%s/%s',
            $config['username'],
            $config['password'],
            $config['host'],
            ltrim($remoteVideoPath, '/')
        );
    }

    /**
     * @brief Supprime la miniature associée à un média.
     *
     * @param Media $media Modèle du média dont supprimer la miniature
     * @return bool true si la suppression a réussi ou si le fichier n'existait pas, false sinon
     */
    public function deleteThumbnail(Media $media): bool
    {
        $thumbnailFilename = $media->id . $this->thumbnailSuffix;
        $thumbnailPath = $this->thumbnailsPath . '/' . $thumbnailFilename;

        if (file_exists($thumbnailPath)) {
            return unlink($thumbnailPath);
        }

        return true;
    }

    /**
     * @brief Régénère toutes les miniatures manquantes pour tous les médias.
     *
     * Parcourt tous les médias ayant au moins un chemin vidéo disponible
     * et génère les miniatures manquantes.
     *
     * Cas d'usage :
     * - Après migration de base de données
     * - Après ajout de nouveaux médias en masse
     * - Après nettoyage du dossier thumbnails
     *
     * @return array Statistiques de traitement avec 3 clés :
     *               - 'success' : nombre de miniatures générées avec succès
     *               - 'failed' : nombre d'échecs
     *               - 'skipped' : nombre de miniatures déjà existantes
     */
    public function regenerateMissingThumbnails(): array
    {
        $stats = ['success' => 0, 'failed' => 0, 'skipped' => 0];

        $medias = Media::whereNotNull('chemin_local')
            ->orWhereNotNull('URI_NAS_PAD')
            ->orWhereNotNull('URI_NAS_ARCH')
            ->get();

        foreach ($medias as $media) {
            $thumbnailFilename = $media->id . $this->thumbnailSuffix;
            $thumbnailPath = $this->thumbnailsPath . '/' . $thumbnailFilename;

            if (file_exists($thumbnailPath)) {
                $stats['skipped']++;
                continue;
            }

            $result = $this->generateThumbnail($media);
            if ($result) {
                $stats['success']++;
            } else {
                $stats['failed']++;
            }
        }

        return $stats;
    }

    /**
     * @brief Exécute FFmpeg pour extraire une frame vidéo.
     *
     * Utilise l'option -ss avant -i pour un seek rapide (surtout important pour FTP).
     * Extrait une seule frame (-vframes 1) et la redimensionne à la largeur spécifiée.
     *
     * Options FFmpeg utilisées :
     * - -y : écrase le fichier existant
     * - -ss : position temporelle (avant -i pour fast seek)
     * - -vframes 1 : extrait une seule frame
     * - -vf "scale=320:-1" : redimensionne (hauteur auto)
     * - -q:v 3 : qualité JPEG (1=meilleure, 31=pire)
     *
     * @param string $videoUrl Chemin ou URL de la vidéo (local ou ftp://)
     * @param string $outputPath Chemin de sortie pour la miniature
     * @param int $timecode Position temporelle en secondes où extraire la frame
     * @return bool true si l'extraction a réussi, false sinon
     */
    protected function executeFfmpeg(string $videoUrl, string $outputPath, int $timecode): bool
    {
        // Put -ss before -i for fast seeking - only downloads single frame from FTP
        $command = sprintf(
            '%s -y -ss %d -i %s -vframes 1 -vf "scale=%d:-1" -q:v 3 %s 2>&1',
            escapeshellcmd($this->ffmpegPath),
            $timecode,
            escapeshellarg($videoUrl),
            $this->defaultWidth,
            escapeshellarg($outputPath)
        );

        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            Log::error("FFmpeg error: " . implode("\n", $output));
            return false;
        }

        return file_exists($outputPath);
    }

    /**
     * @brief Récupère la durée d'une vidéo via FFprobe.
     *
     * Interroge les métadonnées vidéo pour obtenir la durée totale.
     * Fonctionne sur fichiers locaux et URLs FTP sans téléchargement complet.
     *
     * @param string $videoUrl Chemin ou URL de la vidéo
     * @return string|null Durée au format "HH:MM:SS.CC" (centièmes inclus), ou null en cas d'échec
     */
    protected function getVideoDuration(string $videoUrl): ?string
    {
        // Use ffprobe to get duration directly from FTP without downloading
        $command = sprintf(
            '%s -v quiet -print_format json -show_format %s 2>&1',
            escapeshellcmd($this->ffprobePath),
            escapeshellarg($videoUrl)
        );

        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            Log::warning("FFprobe failed to get duration: " . implode("\n", $output));
            return null;
        }

        $json = implode("\n", $output);
        $data = json_decode($json, true);

        if (isset($data['format']['duration'])) {
            $seconds = (float)$data['format']['duration'];
            $hours = (int)floor($seconds / 3600);
            $minutes = (int)floor(($seconds % 3600) / 60);
            $secs = (int)floor($seconds % 60);
            $centisecs = (int)floor(($seconds - floor($seconds)) * 100);

            return sprintf('%02d:%02d:%02d.%02d', $hours, $minutes, $secs, $centisecs);
        }

        return null;
    }

    /**
     * @brief Calcule le timecode optimal pour extraire une frame représentative.
     *
     * Extrait une frame à mi-durée de la vidéo pour avoir une image
     * représentative du contenu (évite les écrans noirs de début/fin).
     *
     * @param string $duration Durée au format "HH:MM:SS.CC"
     * @return int Position temporelle en secondes (durée totale / 2)
     */
    protected function calculateTimecode(string $duration): int
    {
        $hours = (int)substr($duration, 0, 2);
        $minutes = (int)substr($duration, 3, 2);
        $seconds = (int)substr($duration, 6, 2);
        $centiseconds = (int)substr($duration, 9, 2);

        $total = $hours * 3600 + $minutes * 60 + $seconds + ($centiseconds / 100);

        return (int)floor($total / 2);
    }

    /**
     * @brief Définit la largeur des miniatures générées.
     *
     * @param int $width Largeur en pixels (la hauteur sera calculée automatiquement)
     * @return self Instance actuelle pour chaînage de méthodes
     */
    public function setWidth(int $width): self
    {
        $this->defaultWidth = $width;
        return $this;
    }

    /**
     * @brief Définit le chemin personnalisé vers l'exécutable FFmpeg.
     *
     * Utile pour les environnements où FFmpeg n'est pas dans le PATH
     * ou pour utiliser une version spécifique.
     *
     * @param string $path Chemin absolu vers l'exécutable FFmpeg
     * @return self Instance actuelle pour chaînage de méthodes
     */
    public function setFfmpegPath(string $path): self
    {
        $this->ffmpegPath = $path;
        return $this;
    }
}
