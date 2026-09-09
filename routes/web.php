<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ApplicationController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/', fn () => redirect()->route('login'))->name('home');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login.attempt');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('role:superadmin')->prefix('admin')->name('admin.')->group(function (): void {
        Route::resource('tenants', TenantController::class)->except(['destroy']);
        Route::delete('/tenants/{tenant}', [TenantController::class, 'destroy'])->name('tenants.destroy');
        Route::patch('/tenants/{tenant}/toggle', [TenantController::class, 'toggle'])->name('tenants.toggle');

        Route::resource('applications', ApplicationController::class)->except(['destroy']);
        Route::delete('/applications/{application}', [ApplicationController::class, 'destroy'])->name('applications.destroy');
        Route::patch('/applications/{application}/toggle', [ApplicationController::class, 'toggle'])->name('applications.toggle');
    });
});
