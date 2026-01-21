<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Support\Facades\Log;

class MediaThumbnailService
{
    protected string $ffmpegPath;
    protected string $ffprobePath;
    protected string $thumbnailsPath;
    protected int $defaultWidth = 320;
    protected string $thumbnailSuffix = '_miniature.jpg';

    public function __construct()
    {
        $this->ffmpegPath = config('media.ffmpeg_path', '/usr/bin/ffmpeg');
        $this->ffprobePath = config('media.ffprobe_path', '/usr/bin/ffprobe');
        $this->thumbnailsPath = storage_path('app/public/thumbnails');

        if (!is_dir($this->thumbnailsPath)) {
            mkdir($this->thumbnailsPath, 0755, true);
        }
    }

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
        return sprintf(
            'ftp://%s:%s@%s/%s',
            $config['username'],
            $config['password'],
            $config['host'],
            ltrim($remoteVideoPath, '/')
        );
    }

    public function deleteThumbnail(Media $media): bool
    {
        $thumbnailFilename = $media->id . $this->thumbnailSuffix;
        $thumbnailPath = $this->thumbnailsPath . '/' . $thumbnailFilename;

        if (file_exists($thumbnailPath)) {
            return unlink($thumbnailPath);
        }

        return true;
    }

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

    protected function calculateTimecode(string $duration): int
    {
        $hours = (int)substr($duration, 0, 2);
        $minutes = (int)substr($duration, 3, 2);
        $seconds = (int)substr($duration, 6, 2);
        $centiseconds = (int)substr($duration, 9, 2);

        $total = $hours * 3600 + $minutes * 60 + $seconds + ($centiseconds / 100);

        return (int)floor($total / 2);
    }

    public function setWidth(int $width): self
    {
        $this->defaultWidth = $width;
        return $this;
    }

    public function setFfmpegPath(string $path): self
    {
        $this->ffmpegPath = $path;
        return $this;
    }
}
