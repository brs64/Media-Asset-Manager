<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\TransfertController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ThumbnailController;
use Illuminate\Support\Facades\Route;

// Page d'accueil publique
Route::get('/', [HomeController::class, 'index'])->name('home');

// Miniatures
Route::get('/thumbnails/{mediaId}', [ThumbnailController::class, 'show'])->name('thumbnails.show');

// Gestion des médias
Route::resource('medias', MediaController::class);

// Recherche
Route::get('/recherche', [SearchController::class, 'index'])->name('search');
Route::get('/search', [SearchController::class, 'search'])->name('search.results');

// Routes protégées par authentification (Breeze)
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard (redirige vers home)
    Route::get('/dashboard', function () {
        return redirect()->route('home');
    })->name('dashboard');

    // Profil utilisateur (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/api/autocomplete', [SearchController::class, 'autocomplete'])->name('search.autocomplete');
});

// Routes admin (uniquement pour les professeurs)

Route::middleware(['auth', 'verified'])->prefix('admin')->group(function () {
    
    // --- 1. The Main Dashboard (Loads ALL Tabs) ---
    Route::get('/', [AdminController::class, 'index'])->name('admin.dashboard');
    // --- 2. Settings Update 
    Route::post('/settings', [AdminController::class, 'updateSettings'])->name('admin.settings.update');
    // --- 3. Actions (Create/Delete) ---
    // Professeurs Actions
    Route::post('/professeurs', [AdminController::class, 'createProfesseur'])->name('admin.professeurs.create');
    Route::delete('/professeurs/{id}', [AdminController::class, 'deleteProfesseur'])->name('admin.professeurs.delete');
    // Admins Actions
    Route::post('/backup/run', [AdminController::class, 'runBackup'])->name('admin.backup.run');
    Route::post('/backup/save', [AdminController::class, 'saveBackupSettings'])->name('admin.backup.save');
    Route::post('/reconciliation', [AdminController::class, 'runReconciliation'])->name('admin.reconciliation.run');
    Route::get('/logs/download', [AdminController::class, 'downloadLogs'])->name('admin.logs.download');
    // Eleves Actions
    Route::post('/eleves', [AdminController::class, 'createEleve'])->name('admin.eleves.create');
    Route::delete('/eleves/{id}', [AdminController::class, 'deleteEleve'])->name('admin.eleves.delete');
    // Projets Actions
    Route::post('/projets', [AdminController::class, 'createProjet'])->name('admin.projets.create');
    Route::delete('/projets/{id}', [AdminController::class, 'deleteProjet'])->name('admin.projets.delete');

    // PAGE: Dashboard Transferts
    Route::get('/transferts', [TransfertController::class, 'index'])->name('admin.transfers.index');

    // AJAX: Status Polling
    Route::get('/transferts/status/{jobId}', [TransfertController::class, 'checkStatus'])->name('admin.transfers.status');

    // ACTION: Cancel Job
    Route::post('/transferts/cancel/{jobId}', [TransfertController::class, 'cancel'])->name('admin.transfers.cancel');
});

/*use App\Services\FfastransService;
Route::get('/test-connection', function (FfastransService $service) {
    try {
        // This is the simplest "Ping" command in the API
        $workflows = $service->getWorkflows();

        return response()->json([
            'status' => 'success',
            'message' => 'Connection Established!',
            'data_received' => $workflows,
            'note' => empty($workflows) ? 'Connected, but no workflows found (this is normal if list is empty).' : 'Workflows found.'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Could not connect to FFAStrans.',
            'error_details' => $e->getMessage(),
            'hint' => 'Check if the IP in .env is correct and reachable from inside the Docker container.'
        ], 500);
    }
});*/

// Routes d'authentification Breeze
require __DIR__.'/auth.php';
