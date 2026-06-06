<?php

use App\Http\Controllers\AtlasController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AtlasController::class, 'index'])->name('atlas.index');
