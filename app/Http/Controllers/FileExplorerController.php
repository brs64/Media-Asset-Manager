<?php

namespace App\Http\Controllers;

use App\Services\FileExplorerService;
use Illuminate\Http\Request;

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

        // 🔐 Sécurité minimale
        abort_unless(
            in_array($disk, array_keys(config('filesystems.disks'))),
            403,
            'Disque interdit'
        );

        $items = FileExplorerService::scanDisk($disk, $path);

        // Retourne UNIQUEMENT le HTML
        return view('explorer.tree-item', compact('items'))->render();
    }
}
