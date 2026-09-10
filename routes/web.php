<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\ApplicationController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\TenantApplicationController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Tenant\AppAccessController;
use App\Http\Controllers\Tenant\ReportController;
use App\Http\Controllers\Tenant\StaffController;
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

    // Tenant App Quick Access (owner and staff)
    Route::middleware('role:tenant_owner,tenant_staff')->group(function (): void {
        Route::get('/tenant/reports', [ReportController::class, 'index'])->name('tenant.reports.index');
        Route::get('/tenant/reports/view', [ReportController::class, 'show'])->name('tenant.reports.show');
        Route::get('/tenant/reports/export/csv', [ReportController::class, 'exportCsv'])->name('tenant.reports.export.csv');
        Route::get('/tenant/reports/export/pdf', [ReportController::class, 'exportPdf'])->name('tenant.reports.export.pdf');
        Route::post('/app/{tenantApplication}/access', [AppAccessController::class, 'access'])->name('app.access');
    });

    // Tenant Staff Management (owner only)
    Route::middleware('role:tenant_owner')->prefix('tenant')->name('tenant.')->group(function (): void {
        Route::resource('staff', StaffController::class)->except(['show']);
        Route::patch('/staff/{staff}/toggle', [StaffController::class, 'toggle'])->name('staff.toggle');
        Route::post('/staff/{staff}/reset-password', [StaffController::class, 'resetPassword'])->name('staff.reset-password');
    });

    // Superadmin Vendor Panel
    Route::middleware('role:superadmin')->prefix('admin')->name('admin.')->group(function (): void {
        Route::resource('tenants', TenantController::class)->except(['destroy']);
        Route::delete('/tenants/{tenant}', [TenantController::class, 'destroy'])->name('tenants.destroy');
        Route::patch('/tenants/{tenant}/toggle', [TenantController::class, 'toggle'])->name('tenants.toggle');

        Route::prefix('tenants/{tenant}')->name('tenants.')->group(function (): void {
            Route::resource('applications', TenantApplicationController::class);
            Route::post('applications/{application}/regenerate-secret', [TenantApplicationController::class, 'regenerateSecret'])->name('applications.regenerate-secret');
        });

        Route::resource('applications', ApplicationController::class)->except(['destroy']);
        Route::delete('/applications/{application}', [ApplicationController::class, 'destroy'])->name('applications.destroy');
        Route::patch('/applications/{application}/toggle', [ApplicationController::class, 'toggle'])->name('applications.toggle');

        Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
        Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/view', [AdminReportController::class, 'show'])->name('reports.show');
    });
});
