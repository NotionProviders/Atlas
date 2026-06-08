<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Console\CanonicalDatabaseController;
use App\Http\Controllers\Console\DashboardController;
use App\Http\Controllers\Console\DatabaseMappingController;
use App\Http\Controllers\Console\ProjectController;
use App\Http\Controllers\Console\SnapshotController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('console.login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('console.password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('console.password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('console.password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('console.password.store');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('console.logout');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('console.password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('console.password.update');

    Route::get('/', [DashboardController::class, 'index'])->name('console.dashboard');

    Route::get('canonical-databases', [CanonicalDatabaseController::class, 'index'])
        ->name('console.canonical.index');
    Route::get('canonical-databases/c/{canonicalDatabase:slug}', [CanonicalDatabaseController::class, 'indexWithPeek'])
        ->name('console.canonical.peek-page');
    Route::get('canonical-databases/c/{canonicalDatabase:slug}/panel', [CanonicalDatabaseController::class, 'panel'])
        ->name('console.canonical.panel');
    Route::get('canonical-databases/{canonicalDatabase:slug}', [CanonicalDatabaseController::class, 'show'])
        ->name('console.canonical.show');
    Route::post('canonical-databases', [CanonicalDatabaseController::class, 'store'])
        ->name('console.canonical.store');
    Route::delete('canonical-databases/{canonicalDatabase}', [CanonicalDatabaseController::class, 'destroy'])
        ->name('console.canonical.destroy');

    Route::resource('projects', ProjectController::class)
        ->names('console.projects')
        ->parameters(['projects' => 'project:slug']);

    Route::get('projects/{project:slug}/snapshots/{type}', [SnapshotController::class, 'show'])
        ->name('console.snapshots.show')
        ->whereIn('type', ['before', 'ideal', 'after']);

    Route::get('projects/{project:slug}/snapshots/{type}/embed', [SnapshotController::class, 'embed'])
        ->name('console.snapshots.embed')
        ->whereIn('type', ['before', 'ideal', 'after']);

    Route::get('projects/{project:slug}/snapshots/{type}/nodes', [SnapshotController::class, 'nodes'])
        ->name('console.snapshots.nodes')
        ->whereIn('type', ['before', 'ideal', 'after']);

    Route::post('projects/{project:slug}/snapshots/{type}/import', [SnapshotController::class, 'import'])
        ->name('console.snapshots.import')
        ->whereIn('type', ['before', 'ideal', 'after']);

    Route::get('projects/{project:slug}/mappings', [DatabaseMappingController::class, 'index'])
        ->name('console.mappings.index');

    Route::post('projects/{project:slug}/mappings', [DatabaseMappingController::class, 'store'])
        ->name('console.mappings.store');

    Route::delete('projects/{project:slug}/mappings/{mapping}', [DatabaseMappingController::class, 'destroy'])
        ->name('console.mappings.destroy');
});
