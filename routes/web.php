<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\ApplicationController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\TenantApplicationController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\Website\WebsiteMessageController;
use App\Http\Controllers\Admin\Website\WebsitePageController;
use App\Http\Controllers\Admin\Website\WebsitePostController;
use App\Http\Controllers\Admin\Website\WebsiteSettingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PublicSite\PublicSiteController;
use App\Http\Controllers\Tenant\AppAccessController;
use App\Http\Controllers\Tenant\ReportController;
use App\Http\Controllers\Tenant\StaffController;
use Illuminate\Support\Facades\Route;

// Public Site & Blog Routes
Route::get('/', [PublicSiteController::class, 'home'])->name('home');
Route::get('/berita', [PublicSiteController::class, 'posts'])->name('public.posts');
Route::get('/berita/{slug}', [PublicSiteController::class, 'post'])->name('public.post');
Route::get('/p/{slug}', [PublicSiteController::class, 'page'])->name('public.page');
Route::get('/kontak', [PublicSiteController::class, 'contact'])->name('public.contact');
Route::post('/kontak', [PublicSiteController::class, 'storeMessage'])
    ->middleware('throttle:10,1')
    ->name('public.contact.store');
Route::get('/sitemap.xml', [PublicSiteController::class, 'sitemap'])->name('public.sitemap');
Route::get('/robots.txt', [PublicSiteController::class, 'robots'])->name('public.robots');

// Guest Authentication Routes
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login.attempt');
});

Route::middleware('auth')->post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'throttle:web-app'])->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Tenant App Quick Access (owner and staff)
    Route::middleware('role:tenant_owner,tenant_staff')->group(function (): void {
        Route::get('/tenant/reports', [ReportController::class, 'index'])->name('tenant.reports.index');
        Route::get('/tenant/reports/view', [ReportController::class, 'show'])->name('tenant.reports.show');
        Route::get('/tenant/reports/export/csv', [ReportController::class, 'exportCsv'])->name('tenant.reports.export.csv');
        Route::get('/tenant/reports/export/pdf', [ReportController::class, 'exportPdf'])->name('tenant.reports.export.pdf');
        Route::post('/app/{tenantApplication}/access', [AppAccessController::class, 'access'])
            ->middleware('throttle:app.access')
            ->name('app.access');
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
            Route::post('applications/{application}/test-connection', [TenantApplicationController::class, 'testConnection'])
                ->middleware('throttle:6,1')
                ->name('applications.test-connection');
            Route::post('applications/{application}/regenerate-secret', [TenantApplicationController::class, 'regenerateSecret'])->name('applications.regenerate-secret');
        });

        Route::resource('applications', ApplicationController::class)->except(['destroy']);
        Route::delete('/applications/{application}', [ApplicationController::class, 'destroy'])->name('applications.destroy');
        Route::patch('/applications/{application}/toggle', [ApplicationController::class, 'toggle'])->name('applications.toggle');

        Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
        Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/view', [AdminReportController::class, 'show'])->name('reports.show');
    });

    // Superadmin Website Management
    Route::middleware('role:superadmin')->prefix('website')->name('website.')->group(function (): void {
        Route::resource('posts', WebsitePostController::class)->except(['show']);
        Route::post('posts/{post}/restore', [WebsitePostController::class, 'restore'])->name('posts.restore');
        Route::delete('posts/{post}/cover', [WebsitePostController::class, 'removeCover'])->name('posts.remove-cover');

        Route::resource('pages', WebsitePageController::class)->except(['show']);
        Route::post('pages/{page}/restore', [WebsitePageController::class, 'restore'])->name('pages.restore');

        Route::get('settings', [WebsiteSettingController::class, 'edit'])->name('settings.edit');
        Route::match(['put', 'patch'], 'settings', [WebsiteSettingController::class, 'update'])->name('settings.update');

        Route::get('messages', [WebsiteMessageController::class, 'index'])->name('messages.index');
        Route::post('messages/{message}/mark-read', [WebsiteMessageController::class, 'markRead'])->name('messages.mark-read');
        Route::delete('messages/{message}', [WebsiteMessageController::class, 'destroy'])->name('messages.destroy');
    });
});
