<?php

use App\Http\Controllers\AtlasController;
use App\Http\Controllers\ConsoleController;
use App\Http\Controllers\WorkspacesController;
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
        Route::get('map/{slug}', [ConsoleController::class, 'viewMap'])->name('console.map');

        Route::post('workspaces', [WorkspacesController::class, 'store'])->name('workspaces.store');
        Route::post('workspaces/upload', [WorkspacesController::class, 'upload'])->name('workspaces.upload');
        Route::delete('workspaces/{slug}', [WorkspacesController::class, 'destroy'])->name('workspaces.destroy');
    });
});
