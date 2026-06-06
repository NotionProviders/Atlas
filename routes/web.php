<?php

use App\Http\Controllers\AtlasController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AtlasController::class, 'index'])->name('atlas.index');
Route::get('/atlas-config.js', [AtlasController::class, 'configScript'])->name('atlas.config.js');
