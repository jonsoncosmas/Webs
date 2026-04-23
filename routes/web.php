<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordChangeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Orion\OrionController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

// Guest auth routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// Authenticated + active + (force password change handled inside)
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/password/change', [PasswordChangeController::class, 'show'])->name('password.change');
    Route::post('/password/change', [PasswordChangeController::class, 'update'])->name('password.update');

    Route::middleware('password.changed')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // ORION AI (all users interact with same brand; routing is server-side).
        Route::post('/orion/ask', [OrionController::class, 'ask'])->name('orion.ask');
    });
});
