<?php

use App\Http\Controllers\AtlasController;
use App\Http\Controllers\WorkspacesController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AtlasController::class, 'index'])->name('atlas.index');
Route::get('/atlas-config.js', [AtlasController::class, 'configScript'])->name('atlas.config.js');

Route::get('/workspaces', [WorkspacesController::class, 'index'])->name('workspaces.index');
Route::post('/workspaces', [WorkspacesController::class, 'store'])->name('workspaces.store');
Route::delete('/workspaces/{slug}', [WorkspacesController::class, 'destroy'])->name('workspaces.destroy');
