<?php

use App\Http\Controllers\AtlasController;
use App\Http\Controllers\ConsoleController;
use App\Http\Controllers\IntakeController;
use Illuminate\Support\Facades\Route;

// Public — the conceptual Workspace Atlas.
Route::get('/', [AtlasController::class, 'index'])->name('atlas.index');
Route::get('/atlas-config.js', [AtlasController::class, 'configScript'])->name('atlas.config.js');

// Private backend — the Atlas Console (mapping tool).
Route::prefix('console')->group(function () {
    Route::get('login', [ConsoleController::class, 'showLogin'])->name('console.login');
    Route::post('login', [ConsoleController::class, 'login'])->name('console.login.attempt');
    Route::post('logout', [ConsoleController::class, 'logout'])->name('console.logout');

    Route::middleware('console.auth')->group(function () {
        Route::get('/', [ConsoleController::class, 'index'])->name('console.index');
        Route::get('guide', [IntakeController::class, 'guide'])->name('console.guide');
        Route::get('map/{slug}', [ConsoleController::class, 'viewMap'])->name('console.map');

        // Workspaces + intake.
        Route::post('workspaces', [IntakeController::class, 'createWorkspace'])->name('workspaces.store');
        Route::delete('workspaces/{slug}', [IntakeController::class, 'destroyWorkspace'])->name('workspaces.destroy');

        Route::get('workspaces/{slug}/intake', [IntakeController::class, 'show'])->name('intake.show');
        Route::post('workspaces/{slug}/intake/upload', [IntakeController::class, 'upload'])->name('intake.upload');
        Route::post('workspaces/{slug}/intake/scan', [IntakeController::class, 'scan'])->name('intake.scan');
        Route::delete('workspaces/{slug}/intake/{source}', [IntakeController::class, 'removeSource'])->name('intake.remove');
    });
});
