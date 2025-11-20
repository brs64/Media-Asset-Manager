<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\SearchController;

// Page d'accueil
Route::get('/', [HomeController::class, 'index'])->name('home');

// Authentification
Route::get('/compte', [UserController::class, 'showLoginForm'])->name('login');
Route::post('/login', [UserController::class, 'login'])->name('login.post');
Route::post('/logout', [UserController::class, 'logout'])->name('logout');

// Routes protégées par authentification
Route::middleware(['auth'])->group(function () {
    // Profil utilisateur
    Route::get('/profile', [UserController::class, 'profile'])->name('profile');
    Route::put('/profile', [UserController::class, 'updateProfile'])->name('profile.update');

    // Gestion des médias
    Route::resource('media', MediaController::class);

    // Recherche
    Route::get('/recherche', [SearchController::class, 'index'])->name('search');
    Route::get('/search', [SearchController::class, 'search'])->name('search.results');
    Route::get('/api/autocomplete', [SearchController::class, 'autocomplete'])->name('search.autocomplete');
});

// Routes admin
Route::prefix('admin')->group(function () {
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
