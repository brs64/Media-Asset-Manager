<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

// Page d'accueil publique
Route::get('/', [HomeController::class, 'index'])->name('home');

// Gestion des médias
Route::resource('medias', MediaController::class);

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

    // Recherche
    Route::get('/recherche', [SearchController::class, 'index'])->name('search');
    Route::get('/search', [SearchController::class, 'search'])->name('search.results');
    Route::get('/api/autocomplete', [SearchController::class, 'autocomplete'])->name('search.autocomplete');
});

// Routes admin (uniquement pour les professeurs)
Route::middleware(['auth', 'verified'])->prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('admin.dashboard');

    // Gestion des professeurs
    Route::get('/professeurs', [AdminController::class, 'professeurs'])->name('admin.professeurs');
    Route::post('/professeurs', [AdminController::class, 'createProfesseur'])->name('admin.professeurs.create');
    Route::delete('/professeurs/{id}', [AdminController::class, 'deleteProfesseur'])->name('admin.professeurs.delete');

    // Gestion des élèves
    Route::get('/eleves', [AdminController::class, 'eleves'])->name('admin.eleves');
    Route::post('/eleves', [AdminController::class, 'createEleve'])->name('admin.eleves.create');
    Route::delete('/eleves/{id}', [AdminController::class, 'deleteEleve'])->name('admin.eleves.delete');

    // Gestion des projets
    Route::get('/projets', [AdminController::class, 'projets'])->name('admin.projets');
    Route::post('/projets', [AdminController::class, 'createProjet'])->name('admin.projets.create');
    Route::delete('/projets/{id}', [AdminController::class, 'deleteProjet'])->name('admin.projets.delete');
});

// Routes d'authentification Breeze
require __DIR__.'/auth.php';
