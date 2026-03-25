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
     * @brief Point d'entrée principal pour le streaming d’un média.
     *
     * Cette méthode détermine automatiquement la source la plus appropriée
     * pour servir la vidéo en fonction du contexte et des données disponibles.
     *
     * Ordre de priorité :
     * 1. Environnement local → fichiers locaux (dev)
     * 2. Fichier transcodé local (chemin_local)
     * 3. NAS via FTP (ARCH ou PAD)
     *
     * Supporte :
     * - Streaming MP4 avec gestion des requêtes HTTP Range (seek vidéo)
     * - Streaming HLS (.m3u8)
     *
     * Inclut un fallback en environnement de développement si la base de données
     * est inaccessible ou si le média n’est pas trouvé.
     *
     * @param Request $request Requête HTTP entrante
     * @param int|string $mediaId Identifiant du média à streamer
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * Si le média ou la source vidéo est introuvable
     */
    public function stream(Request $request, $mediaId)
    {
        try {
            $media = Media::findOrFail($mediaId);

            // En mode développement local, utiliser les vidéos locales
            if (config('app.env') === 'local' && config('app.local_video_path')) {
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
            if (!config('filesystems.disks.ftp_pad.host') && !config('filesystems.disks.ftp_arch.host')) {
                return $this->streamLocalMediaVideo($media, $request);
            }

        } catch (\Exception $e) {
            // Fallback si la DB n'est pas accessible
            Log::warning("Database/Media error: " . $e->getMessage());

            // En mode dev, essayer de mapper l'ID à une vidéo locale
            if (config('app.env') === 'local' && config('app.local_video_path')) {
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
     * @brief Stream un fichier vidéo MP4 depuis un stockage distant avec support HTTP Range.
     *
     * Permet une lecture fluide et optimisée en supportant :
     * - les requêtes partielles (seek vidéo)
     * - le streaming progressif
     *
     * Le fichier est lu en flux (stream) depuis un disque Laravel (FTP),
     * avec gestion manuelle des offsets et des buffers.
     *
     * @param string $ftpDisk Nom du disque Laravel configuré (ex: ftp_arch, ftp_pad)
     * @param string $videoPath Chemin du fichier vidéo sur le stockage distant
     * @param Request $request Requête HTTP (utilisée pour détecter les headers Range)
     * @return StreamedResponse
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * Si le fichier est introuvable ou en cas d'erreur de streaming
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

        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error streaming video: ' . $e->getMessage());
            abort(500, 'Erreur lors du streaming de la vidéo');
        }
    }

    /**
     * @brief Stream une playlist HLS (.m3u8) depuis un stockage distant.
     *
     * Retourne le contenu brut de la playlist avec le Content-Type approprié
     * pour permettre au client (lecteur vidéo) de charger les segments associés.
     *
     * @param string $ftpDisk Nom du disque Laravel configuré
     * @param string $m3u8Path Chemin vers le fichier playlist (.m3u8)
     * @return \Illuminate\Http\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * Si la playlist est introuvable ou inaccessible
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

        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error streaming HLS: ' . $e->getMessage());
            abort(500, 'Erreur lors du streaming HLS');
        }
    }

    /**
     * @brief Stream un fichier vidéo local avec support des requêtes HTTP Range.
     *
     * Utilise le disque "external_local" pour accéder aux fichiers stockés localement.
     * Implémente un streaming optimisé avec gestion des offsets pour permettre
     * la navigation dans la vidéo (seek).
     *
     * @param string $videoPath Chemin relatif du fichier vidéo
     * @param Request $request Requête HTTP (gestion des headers Range)
     * @return StreamedResponse
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * Si le fichier local est introuvable
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
     * @brief Tente de résoudre et streamer une vidéo locale à partir d’un modèle Media.
     *
     * Cette méthode applique plusieurs stratégies pour retrouver un fichier local :
     * - Nom technique exact du média
     * - Nom sans extension avec ajout ".mp4"
     * - Nom avec espaces remplacés par des underscores
     * - Nom extrait depuis les URI disponibles
     *
     * En cas d’échec, un fallback est appliqué en sélectionnant le premier
     * fichier MP4 disponible dans le dossier cible.
     *
     * @param Media $media Instance du modèle Media
     * @param Request $request Requête HTTP
     * @return StreamedResponse
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * Si aucun fichier vidéo local ne peut être trouvé
     */
    private function streamLocalMediaVideo(Media $media, Request $request)
    {
        $localPath = config('app.local_video_path', 'storage/app/TestFolder');
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
     * @brief Stream une vidéo locale de test en fonction d’un mapping d’ID (dev uniquement).
     *
     * Utilisé comme fallback lorsque la base de données est inaccessible.
     * Associe un identifiant de média à un fichier vidéo local prédéfini.
     *
     * Si aucun mapping n’est trouvé, un fallback est appliqué en sélectionnant
     * le premier fichier MP4 disponible.
     *
     * @param int|string $mediaId Identifiant du média
     * @param Request $request Requête HTTP
     * @return StreamedResponse
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * Si aucune vidéo locale n’est disponible
     */
    private function streamLocalVideoByMapping($mediaId, Request $request)
    {
        $localPath = config('app.local_video_path', 'storage/app/TestFolder');
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
     * @brief Stream un segment vidéo HLS (.ts).
     *
     * Construit dynamiquement le chemin du segment à partir des informations
     * du média, puis récupère le fichier depuis le stockage distant (FTP).
     *
     * Cette méthode est utilisée par les lecteurs HLS pour charger
     * les segments vidéo référencés dans la playlist (.m3u8).
     *
     * @param Request $request Requête HTTP
     * @param int|string $mediaId Identifiant du média
     * @param string $segment Nom du fichier segment (.ts)
     * @return \Illuminate\Http\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * Si le segment ou le média est introuvable
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

        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error streaming segment: ' . $e->getMessage());
            abort(500, 'Erreur lors du streaming du segment');
        }
    }
}