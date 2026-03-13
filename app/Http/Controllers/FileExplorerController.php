<?php

namespace App\Http\Controllers;

use App\Services\FileExplorerService;
use App\Services\FfastransService;
use App\Models\Media;
use Illuminate\Http\Request;
use App\Jobs\ScanDiskJob;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class FileExplorerController extends Controller
{
    /**
     * Affiche la racine de l’explorateur
     */
    public function index()
    {
        $disk = 'external_local'; // ou depuis config / auth / param
        $path = '/';

        $items = FileExplorerService::scanDisk($disk, $path);

        return view('explorer.index', compact('items'));
    }

    /**
     * Scan un dossier au clic (AJAX)
     */
    public function scan(Request $request)
    {
        $disk = $request->query('disk');
        $path = $request->query('path', '/');

        abort_unless(
            in_array($disk, array_keys(config('filesystems.disks'))),
            403,
            'Disque interdit'
        );

        // scan filesystem
        $items = FileExplorerService::scanDisk($disk, $path);

        // chemins vidéos
        $videoPaths = collect($items)
            ->where('type', 'video')
            ->pluck('path')
            ->values();

        // mapping disk → colonne BDD
        $column = match ($disk) {
            'external_local' => 'chemin_local',
            'ftp_pad'        => 'URI_NAS_PAD',
            'ftp_arch'       => 'URI_NAS_ARCH',
            default          => null,
        };

        $medias = collect();

        if ($column && $videoPaths->isNotEmpty()) {
            $medias = Media::whereIn($column, $videoPaths)
                ->get()
                ->keyBy($column);
        }

        // enrichissement
        $items = collect($items)->map(function ($item) use ($medias, $column) {
            if ($item['type'] === 'video' && $column) {
                $item['media'] = $medias->get($item['path']);
            }
            return $item;
        });

        return view('explorer.tree-item', [
            'items' => $items,
        ])->render();
    }

    public function startScan(Request $request)
    {
        $disk = $request->query('disk', "ftp_pad");

        $envPath = env('URI_RACINE_NAS_PAD');
        $path = $request->input('path') ?: $envPath ?: '/';
        
        $forceRefresh = $request->boolean('force');
        $cacheKey = "scan_lock:" . md5($disk . $path);

        if (!$forceRefresh) {
            $existingScanId = Cache::get($cacheKey);
            if ($existingScanId && Cache::get("scan:{$existingScanId}:status") === 'done') {
                return response()->json([
                    'scan_id' => $existingScanId,
                    'status'  => 'started',
                    'cached'  => true
                ]);
            }
        }

        $scanId = (string) Str::uuid();

        Cache::put($cacheKey, $scanId, now()->addMinutes(5));

        Cache::put(
            "scan:{$scanId}:status",
            'running',
            now()->addHours(2)
        );

        ScanDiskJob::dispatch($disk, $path, $scanId);

        return response()->json([
            'scan_id' => $scanId,
            'status' => 'started',
        ]);
    }

    public function scanStatus(string $scanId)
    {
        return response()->json([
            'status' => Cache::get("scan:{$scanId}:status", 'unknown'),
            'count'  => Cache::get("scan:{$scanId}:count", 0),
        ]);
    }

    public function scanResults(string $scanId, FfastransService $ffastrans)
    {
        // Check Status
        $status = Cache::get("scan:{$scanId}:status", 'unknown');

        if ($status !== 'done') {
            return response()->json([
                'status' => $status,
                'count'  => Cache::get("scan:{$scanId}:count", 0),
                'results'=> []
            ]);
        }

        $rawFiles = Cache::get("scan:{$scanId}:results", []);
        
        $allScannedPaths = array_column($rawFiles, 'path');
        
        $archivedPaths = Media::whereIn('chemin_local', $allScannedPaths)
                            ->pluck('chemin_local')
                            ->flip()
                            ->toArray();

        $activeMap = [];
        try {
            $activeJobs = $ffastrans->getFullStatusList();
            foreach ($activeJobs as $job) {
                $activeMap[$job['filename']] = $job;
            }
        } catch (\Exception $e) {
            $activeMap = [];
        }

        $unifiedList = [];

        foreach ($rawFiles as $file) {
            if (($file['type'] ?? '') !== 'video') continue;

            $filename = $file['name'];
            $path = $file['path'];

            if (isset($archivedPaths[$path])) {
                continue; // Skip archived files
            }

            if (isset($activeMap[$filename])) {
                $job = $activeMap[$filename];
                $unifiedList[] = [
                    'filename' => $filename,
                    'path'     => $path,
                    'disk'     => 'ftp_pad',
                    'job_id'   => $job['id'],
                    'status'   => $job['status'],
                    'progress' => $job['progress'],
                    'finished' => $job['is_finished']
                ];
            } else {
                $unifiedList[] = [
                    'filename' => $filename,
                    'path'     => $path,
                    'disk'     => 'ftp_pad',
                    'job_id'   => null,
                    'status'   => 'En attente',
                    'progress' => 0,
                    'finished' => false
                ];
            }
        }

        return response()->json([
            'status' => 'done',
            'count'  => count($unifiedList),
            'results'=> $unifiedList, 
        ]);
    }
}
