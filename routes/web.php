<?php

use App\Http\Controllers\BackupController;
use App\Http\Controllers\ExhibitionController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\MuseumController;
use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::redirect('/', '/login');

Route::middleware(['auth', 'verified'])->prefix('admin')->group(function () {
    Route::get('/', function () {
        return Inertia::render('backend/Welcome');
    })->name('home');

    Route::get('dashboard', function () {
        return Inertia::render('backend/Dashboard');
    })->name('dashboard');

    // BACKUPS
    // Visualizzazione: richiede il permesso "manage backups"
    Route::get('settings/backups', [BackupController::class, 'index'])
        ->name('backups.index')
        ->middleware('can:manage backups');

    // Creazione: richiede il permesso "create backups"
    Route::post('settings/backups', [BackupController::class, 'store'])
        ->name('backups.store')
        ->middleware('can:create backups');

    // Aggiornamento impostazioni: richiede "manage backups"
    Route::put('settings/backups/config', [BackupController::class, 'updateSettings'])
        ->name('backups.settings.update')
        ->middleware('can:manage backups');

    // Download backup: richiede "manage backups"
    Route::get('settings/backups/download/{disk}/{file}', [BackupController::class, 'download'])
        ->name('backups.download')
        ->middleware('can:manage backups')
        ->where([
            'disk' => '[A-Za-z0-9_-]+',
            'file' => '[A-Za-z0-9._-]+',
        ]);

    // Ripristino: richiede il permesso "restore backups"
    Route::post('settings/backups/restore', [BackupController::class, 'restore'])
        ->name('backups.restore')
        ->middleware('can:restore backups');

    // LINGUE
    // Tutte le azioni sulle lingue richiedono "manage languages"
    Route::resource('languages', LanguageController::class)
        ->middleware('can:manage languages');

    // ALTRE RISORSE (non hai ancora specificato permessi per queste)
    Route::resource('museums', MuseumController::class);
    Route::resource('exhibitions', ExhibitionController::class);
    Route::resource('posts', PostController::class);
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
