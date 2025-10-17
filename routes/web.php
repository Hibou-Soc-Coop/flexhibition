<?php

use App\Http\Controllers\MuseumController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::redirect('/', '/backend');

Route::prefix('backend')->group(function () {
    Route::get('/', function () {
        return Inertia::render('backend/Welcome');
    })->name('home');

    Route::get('dashboard', function () {
        return Inertia::render('backend/Dashboard');
    })->middleware(['auth', 'verified'])->name('dashboard');

    Route::resource('museums', MuseumController::class)->middleware(['auth', 'verified']);
});



require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
