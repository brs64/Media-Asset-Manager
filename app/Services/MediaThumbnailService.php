<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MediaThumbnailService
{
    protected string $ffmpegPath;
    protected string $thumbnailsPath;
    protected string $cachePath;
    protected int $defaultWidth = 320;
    protected string $thumbnailSuffix = '_miniature.jpg';

    public function __construct()
    {
        $this->ffmpegPath = config('media.ffmpeg_path', '/usr/bin/ffmpeg');
        $this->thumbnailsPath = storage_path('app/public/thumbnails');
        $this->cachePath = storage_path('app/media_cache');

        if (!is_dir($this->thumbnailsPath)) {
            mkdir($this->thumbnailsPath, 0755, true);
        }

        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }

    public function generateThumbnail(Media $media, ?string $videoPath = null, bool $force = false): ?string
    {
        $remoteVideoPath = $videoPath ?? $media->URI_NAS_MPEG ?? $media->URI_NAS_PAD ?? $media->URI_NAS_ARCH;

        if (!$remoteVideoPath) {
            Log::warning("No video path for media #{$media->id}");
            return null;
        }

        $thumbnailFilename = $media->id . $this->thumbnailSuffix;
        $thumbnailPath = $this->thumbnailsPath . '/' . $thumbnailFilename;

        if (!$force && file_exists($thumbnailPath)) {
            return $thumbnailPath;
        }

        $localVideoPath = $this->getOrDownloadVideo($media, $remoteVideoPath);
        if (!$localVideoPath) {
            return null;
        }

        // Get actual video duration
        $duration = $this->getVideoDuration($localVideoPath);
        $timecode = $duration ? $this->calculateTimecode($duration) : 5;

        $success = $this->executeFfmpeg($localVideoPath, $thumbnailPath, $timecode);

        if ($success) {
            Log::info("Thumbnail generated for media #{$media->id}");
            return $thumbnailPath;
        }

        Log::error("Failed to generate thumbnail for media #{$media->id}");
        return null;
    }

    protected function getOrDownloadVideo(Media $media, string $remoteVideoPath): ?string
    {
        $extension = pathinfo($remoteVideoPath, PATHINFO_EXTENSION);
        $cachedPath = $this->cachePath . '/' . $media->id . '.' . $extension;

        if (file_exists($cachedPath)) {
            Log::info("Using cached video for media #{$media->id}");
            return $cachedPath;
        }

        $ftpDisk = $this->determineFtpDisk($media);
        if (!$ftpDisk) {
            Log::warning("Unable to determine FTP disk for media #{$media->id}");
            return null;
        }

        return $this->downloadFromFtp($ftpDisk, $remoteVideoPath, $media->id, $cachedPath);
    }

    protected function determineFtpDisk(Media $media): ?string
    {
        if ($media->URI_NAS_MPEG) return 'ftp_mpeg';
        if ($media->URI_NAS_PAD) return 'ftp_pad';
        return null;
    }

    protected function downloadFromFtp(string $disk, string $remotePath, int $mediaId, string $cachedPath): ?string
    {
        try {
            if (!Storage::disk($disk)->exists($remotePath)) {
                Log::warning("File not found on FTP {$disk}: {$remotePath} for media #{$mediaId}");
                return null;
            }

            Log::info("Downloading from FTP {$disk} for media #{$mediaId}");

            $content = Storage::disk($disk)->get($remotePath);
            file_put_contents($cachedPath, $content);

            Log::info("Video cached for media #{$mediaId}");
            return $cachedPath;
        } catch (\Exception $e) {
            Log::error("FTP download error for media #{$mediaId}: " . $e->getMessage());
            return null;
        }
    }

    public function clearCache(?int $mediaId = null): void
    {
        if ($mediaId) {
            $pattern = $this->cachePath . '/' . $mediaId . '.*';
            foreach (glob($pattern) as $file) {
                unlink($file);
            }
            Log::info("Video cache cleared for media #{$mediaId}");
        } else {
            $files = glob($this->cachePath . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            Log::info("Entire video cache cleared");
        }
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

        $medias = Media::whereNotNull('URI_NAS_MPEG')
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

    protected function executeFfmpeg(string $videoPath, string $outputPath, int $timecode): bool
    {
        $command = sprintf(
            '%s -y -i %s -ss %d -vframes 1 -vf "scale=%d:-1" -q:v 3 -strict unofficial -update 1 %s 2>&1',
            escapeshellcmd($this->ffmpegPath),
            escapeshellarg($videoPath),
            $timecode,
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

    protected function getVideoDuration(string $videoPath): ?string
    {
        $command = sprintf(
            '%s -i %s 2>&1',
            escapeshellcmd($this->ffmpegPath),
            escapeshellarg($videoPath)
        );

        exec($command, $output);
        $outputStr = implode("\n", $output);

        if (preg_match('/Duration: (\d{2}:\d{2}:\d{2}\.\d{2})/', $outputStr, $matches)) {
            return $matches[1];
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
