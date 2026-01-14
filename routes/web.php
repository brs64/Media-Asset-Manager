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

Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    
    // --- TAB 1: BASE DE DONNEES ---
    Route::get('/', [AdminController::class, 'database'])->name('database');
    Route::get('/database', [AdminController::class, 'database'])->name('database.index');

    // --- TAB 2: TRANSFERTS ---
    Route::get('/transferts', [TransfertController::class, 'index'])->name('transferts');
    Route::get('/transferts/list', [TransfertController::class, 'list'])->name('transfers.list');
    Route::get('/transferts/status/{jobId}', [TransfertController::class, 'checkStatus'])->name('transfers.status');
    Route::post('/transferts/cancel/{jobId}', [TransfertController::class, 'cancel'])->name('transfers.cancel');

    // --- TAB 3: RECONCILIATION ---
    Route::get('/reconciliation', [AdminController::class, 'reconciliation'])->name('reconciliation');
    Route::post('/reconciliation', [AdminController::class, 'runReconciliation'])->name('reconciliation.run');

    // --- TAB 4: PARAMETRAGE (Settings) ---
    Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
    Route::post('/settings', [AdminController::class, 'updateSettings'])->name('settings.update');

    // --- TAB 5: LOGS ---
    Route::get('/logs', [AdminController::class, 'logs'])->name('logs');
    Route::get('/logs/download', [AdminController::class, 'downloadLogs'])->name('logs.download');

    // --- TAB 6: UTILISATEURS ---
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    // User Actions
    Route::post('/professeurs', [AdminController::class, 'createProfesseur'])->name('professeurs.create');
    Route::delete('/professeurs/{id}', [AdminController::class, 'deleteProfesseur'])->name('professeurs.delete');
    Route::post('/eleves', [AdminController::class, 'createEleve'])->name('eleves.create');
    Route::delete('/eleves/{id}', [AdminController::class, 'deleteEleve'])->name('eleves.delete');

    // --- BACKUP ACTIONS ---
    Route::post('/backup/run', [AdminController::class, 'runBackup'])->name('backup.run');
    Route::post('/backup/save', [AdminController::class, 'saveBackupSettings'])->name('backup.save');
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
