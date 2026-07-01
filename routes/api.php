<?php

use App\Http\Controllers\Api\ExtensionController;
use App\Http\Controllers\Api\MigrationApiController;
use Illuminate\Support\Facades\Route;

/*
| Extension-facing API. Everything here is bearer-token authenticated (except
| pairing, which is gated by a short-lived code minted from an authed console
| session). No cookies, no CSRF — the browser extension is the only client.
*/

Route::prefix('extension')->group(function () {
    Route::post('pair', [ExtensionController::class, 'pair']);

    Route::middleware('extension.auth')->group(function () {
        Route::get('me', [ExtensionController::class, 'me']);
        Route::get('sources', [ExtensionController::class, 'sources']);
        Route::get('pending', [MigrationApiController::class, 'pending']);
    });
});

Route::prefix('migrations')->middleware('extension.auth')->group(function () {
    Route::post('/', [MigrationApiController::class, 'store']);
    Route::get('{run}', [MigrationApiController::class, 'show']);
    Route::post('{run}/claim', [MigrationApiController::class, 'claim']);
    Route::post('{run}/tree', [MigrationApiController::class, 'tree']);
    Route::post('{run}/nodes', [MigrationApiController::class, 'nodes']);
    Route::post('{run}/complete', [MigrationApiController::class, 'complete']);
});
