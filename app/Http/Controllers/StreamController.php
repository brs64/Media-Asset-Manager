<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StreamController extends Controller
{
    /**
     * Stream video from FTP or local storage
     */
    public function stream(Request $request, $mediaId)
    {
        try {
            $media = Media::findOrFail($mediaId);

            // En mode développement local, utiliser les vidéos locales
            if (env('APP_ENV') === 'local' && env('LOCAL_VIDEO_PATH')) {
                return $this->streamLocalMediaVideo($media, $request);
            }

            // Priorité: chemin_local (transcodé) > ARCH > PAD
            // Si chemin_local existe, streamer en local
            if ($media->chemin_local) {
                return $this->streamLocalVideo($media->chemin_local, $request);
            }

            // Sinon utiliser les chemins NAS
            $videoPath = $media->URI_NAS_ARCH ?? $media->URI_NAS_PAD;

            if (!$videoPath) {
                abort(404, 'Vidéo non trouvée');
            }

            // Si pas de connexion FTP configurée, essayer en local
            if (!env('FTP_PAD_HOST') && !env('FTP_ARCH_HOST')) {
                return $this->streamLocalMediaVideo($media, $request);
            }

        } catch (\Exception $e) {
            // Fallback si la DB n'est pas accessible
            Log::warning("Database/Media error: " . $e->getMessage());

            // En mode dev, essayer de mapper l'ID à une vidéo locale
            if (env('APP_ENV') === 'local' && env('LOCAL_VIDEO_PATH')) {
                return $this->streamLocalVideoByMapping($mediaId, $request);
            }

            abort(404, 'Vidéo non trouvée');
        }

        // Déterminer le disque FTP à utiliser
        $ftpDisk = null;
        if ($media->URI_NAS_ARCH) {
            $ftpDisk = 'ftp_arch';
        } elseif ($media->URI_NAS_PAD) {
            $ftpDisk = 'ftp_pad';
        }

        if (!$ftpDisk) {
            abort(404, 'Configuration FTP non trouvée');
        }

        // Si c'est un fichier .m3u8 (HLS), on le sert directement
        if (pathinfo($videoPath, PATHINFO_EXTENSION) === 'm3u8') {
            return $this->streamHLS($ftpDisk, $videoPath);
        }

        // Pour les fichiers MP4, on fait du streaming avec range support
        return $this->streamMP4($ftpDisk, $videoPath, $request);
    }

    /**
     * Stream MP4 files with range support
     */
    private function streamMP4($ftpDisk, $videoPath, Request $request)
    {
        try {
            $disk = Storage::disk($ftpDisk);

            if (!$disk->exists($videoPath)) {
                Log::error("Video file not found on FTP: $videoPath");
                abort(404, 'Fichier vidéo non trouvé');
            }

            $size = $disk->size($videoPath);
            $mimeType = $disk->mimeType($videoPath) ?? 'video/mp4';

            // Support pour les requêtes Range (nécessaire pour la navigation dans la vidéo)
            $start = 0;
            $end = $size - 1;

            if ($request->hasHeader('Range')) {
                $range = $request->header('Range');
                if (preg_match('/bytes=(\d+)-(\d*)/', $range, $matches)) {
                    $start = intval($matches[1]);
                    if (!empty($matches[2])) {
                        $end = intval($matches[2]);
                    }
                }
            }

            $length = $end - $start + 1;

            // Headers pour le streaming
            $headers = [
                'Content-Type' => $mimeType,
                'Content-Length' => $length,
                'Accept-Ranges' => 'bytes',
                'Content-Range' => "bytes $start-$end/$size",
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ];

            $status = ($start > 0 || $end < ($size - 1)) ? 206 : 200;

            // Stream le fichier
            return response()->stream(
                function () use ($disk, $videoPath, $start, $length) {
                    $stream = $disk->readStream($videoPath);

                    if ($start > 0) {
                        fseek($stream, $start);
                    }

                    $bufferSize = 1024 * 1024; // 1MB buffer
                    $bytesRead = 0;

                    while (!feof($stream) && $bytesRead < $length) {
                        $toRead = min($bufferSize, $length - $bytesRead);
                        echo fread($stream, $toRead);
                        $bytesRead += $toRead;
                        flush();
                    }

                    fclose($stream);
                },
                $status,
                $headers
            );

        } catch (\Exception $e) {
            Log::error('Error streaming video: ' . $e->getMessage());
            abort(500, 'Erreur lors du streaming de la vidéo');
        }
    }

    /**
     * Stream HLS files
     */
    private function streamHLS($ftpDisk, $m3u8Path)
    {
        try {
            $disk = Storage::disk($ftpDisk);

            if (!$disk->exists($m3u8Path)) {
                abort(404, 'Playlist HLS non trouvée');
            }

            $content = $disk->get($m3u8Path);

            return response($content, 200, [
                'Content-Type' => 'application/vnd.apple.mpegurl',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ]);

        } catch (\Exception $e) {
            Log::error('Error streaming HLS: ' . $e->getMessage());
            abort(500, 'Erreur lors du streaming HLS');
        }
    }

    /**
     * Stream local video files
     */
    private function streamLocalVideo($videoPath, Request $request)
    {
        // Utiliser le disque app qui pointe vers storage/app/
        $disk = Storage::disk('external_local');

        if (!$disk->exists($videoPath)) {
            Log::error("Local video file not found: $videoPath");
            abort(404, 'Fichier vidéo local non trouvé');
        }

        $fullPath = $disk->path($videoPath);
        $size = $disk->size($videoPath);
        $mimeType = $disk->mimeType($videoPath) ?? 'video/mp4';
    
        // Support pour les requêtes Range
        $start = 0;
        $end = $size - 1;

        if ($request->hasHeader('Range')) {
            $range = $request->header('Range');
            if (preg_match('/bytes=(\d+)-(\d*)/', $range, $matches)) {
                $start = intval($matches[1]);
                if (!empty($matches[2])) {
                    $end = intval($matches[2]);
                }
            }
        }

        $length = $end - $start + 1;

        // Headers pour le streaming
        $headers = [
            'Content-Type' => $mimeType,
            'Content-Length' => $length,
            'Accept-Ranges' => 'bytes',
            'Content-Range' => "bytes $start-$end/$size",
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];

        $status = ($start > 0 || $end < ($size - 1)) ? 206 : 200;

        // Stream le fichier local
        return response()->stream(
            function () use ($fullPath, $start, $length) {
                $stream = fopen($fullPath, 'rb');

                if ($start > 0) {
                    fseek($stream, $start);
                }

                $bufferSize = 1024 * 1024; // 1MB buffer
                $bytesRead = 0;

                while (!feof($stream) && $bytesRead < $length) {
                    $toRead = min($bufferSize, $length - $bytesRead);
                    echo fread($stream, $toRead);
                    $bytesRead += $toRead;
                    flush();
                }

                fclose($stream);
            },
            $status,
            $headers
        );
    }

    /**
     * Stream local video based on media model
     */
    private function streamLocalMediaVideo(Media $media, Request $request)
    {
        $localPath = env('LOCAL_VIDEO_PATH', 'storage/app/TestFolder');
        $cleanPath = str_replace('storage/app/', '', $localPath);

        // Essayer plusieurs formats de nom de fichier possibles
        $possibleFilenames = [
            $media->mtd_tech_titre, // Titre technique exact
            pathinfo($media->mtd_tech_titre, PATHINFO_FILENAME) . '.mp4', // Sans extension + .mp4
            str_replace(' ', '_', $media->mtd_tech_titre), // Espaces remplacés par underscore
        ];

        // Si le chemin URI contient un nom de fichier, l'utiliser
        if ($media->chemin_local || $media->URI_NAS_ARCH || $media->URI_NAS_PAD) {
            $uriPath = $media->chemin_local ?? $media->URI_NAS_ARCH ?? $media->URI_NAS_PAD;
            $possibleFilenames[] = basename($uriPath);
        }

        // Chercher le fichier
        foreach ($possibleFilenames as $filename) {
            if (empty($filename)) continue;

            $filePath = $cleanPath . '/' . $filename;
            if (Storage::disk('app')->exists($filePath)) {
                Log::info("Streaming local video: $filePath");
                return $this->streamLocalVideo('storage/app/' . $filePath, $request);
            }
        }

        // Si aucun fichier trouvé, chercher n'importe quel .mp4 dans le dossier
        $files = Storage::disk('app')->files($cleanPath);
        $mp4Files = array_filter($files, function($file) {
            return pathinfo($file, PATHINFO_EXTENSION) === 'mp4';
        });

        if (!empty($mp4Files)) {
            // Prendre le premier fichier MP4 trouvé
            $firstMp4 = reset($mp4Files);
            Log::info("Using first MP4 found: $firstMp4");
            return $this->streamLocalVideo('storage/app/' . $firstMp4, $request);
        }

        Log::error("No local video found for media #{$media->id}");
        abort(404, 'Vidéo locale non trouvée');
    }

    /**
     * Stream local video by mapping ID to test videos
     */
    private function streamLocalVideoByMapping($mediaId, Request $request)
    {
        $localPath = env('LOCAL_VIDEO_PATH', 'storage/app/TestFolder');
        $cleanPath = str_replace('storage/app/', '', $localPath);

        // Mapping simple des IDs aux vidéos de test
        $videoMapping = [
            '1' => 'video_test_rouge.mp4',
            '2' => 'video_test_vert.mp4',
            '3' => 'video_test_bleu.mp4',
            '4' => 'video_test_bleu.mp4',
            '5' => 'video_test_rouge.mp4',
        ];

        $filename = $videoMapping[$mediaId] ?? 'video_test_bleu.mp4';
        $filePath = $cleanPath . '/' . $filename;

        if (Storage::disk('app')->exists($filePath)) {
            Log::info("Streaming mapped video for ID $mediaId: $filePath");
            return $this->streamLocalVideo('storage/app/' . $filePath, $request);
        }

        // Fallback : prendre n'importe quel MP4
        $files = Storage::disk('app')->files($cleanPath);
        $mp4Files = array_filter($files, function($file) {
            return pathinfo($file, PATHINFO_EXTENSION) === 'mp4';
        });

        if (!empty($mp4Files)) {
            $firstMp4 = reset($mp4Files);
            return $this->streamLocalVideo('storage/app/' . $firstMp4, $request);
        }

        abort(404, 'Vidéo locale non trouvée');
    }

    /**
     * Stream HLS segments (.ts files)
     */
    public function segment(Request $request, $mediaId, $segment)
    {
        $media = Media::findOrFail($mediaId);

        // Déterminer le chemin de base
        $basePath = $media->URI_NAS_ARCH ?? $media->URI_NAS_PAD;

        if (!$basePath) {
            abort(404, 'Vidéo non trouvée');
        }

        // Construire le chemin du segment
        $segmentPath = dirname($basePath) . '/' . $segment;

        // Déterminer le disque FTP
        $ftpDisk = null;
        if ($media->URI_NAS_ARCH) {
            $ftpDisk = 'ftp_arch';
        } elseif ($media->URI_NAS_PAD) {
            $ftpDisk = 'ftp_pad';
        }

        if (!$ftpDisk) {
            abort(404, 'Configuration FTP non trouvée');
        }

        try {
            $disk = Storage::disk($ftpDisk);

            if (!$disk->exists($segmentPath)) {
                abort(404, 'Segment non trouvé');
            }

            $content = $disk->get($segmentPath);

            return response($content, 200, [
                'Content-Type' => 'video/mp2t',
                'Cache-Control' => 'max-age=3600',
            ]);

        } catch (\Exception $e) {
            Log::error('Error streaming segment: ' . $e->getMessage());
            abort(500, 'Erreur lors du streaming du segment');
        }
    }
}