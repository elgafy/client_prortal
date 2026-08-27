<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::resource('clients', ClientController::class)->except(['destroy']);
    Route::patch('clients/{client}/archive', [ClientController::class, 'archive'])->name('clients.archive');

    Route::resource('projects', ProjectController::class)->except(['destroy']);
});

require __DIR__.'/settings.php';
