<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        if ($user->isSuperadmin()) {
            return Inertia::render('Dashboard', [
                'stats' => [
                    'activeTenants' => Tenant::query()->where('is_active', true)->count(),
                    'applications' => Application::query()->count(),
                    'users' => User::query()->count(),
                    'assignedApps' => TenantApplication::query()->where('is_active', true)->count(),
                ],
            ]);
        }

        $tenant = $user->tenant;
        $tenantApplications = $tenant !== null
            ? $tenant->tenantApplications()
                ->with('application')
                ->whereHas('application', fn ($query) => $query->where('is_active', true))
                ->orderBy('is_active', 'desc')
                ->get()
            : collect();

        return Inertia::render('Tenant/Dashboard', [
            'tenant' => [
                'id' => $tenant?->id,
                'name' => $tenant?->name ?? 'Tenant',
                'domain' => $tenant?->domain,
            ],
            'applications' => $tenantApplications->map(fn (TenantApplication $app) => [
                'id' => $app->id,
                'label' => $app->label,
                'instance_url' => $app->instance_url,
                'is_active' => $app->is_active,
                'is_expired' => $app->isExpired(),
                'expired_at' => $app->expired_at?->toIso8601String(),
                'notes' => $app->notes,
                'application' => [
                    'id' => $app->application?->id,
                    'name' => $app->application?->name,
                    'slug' => $app->application?->slug,
                    'description' => $app->application?->description,
                    'icon_path' => $app->application?->icon_path,
                    'has_financial_report' => $app->application?->has_financial_report,
                ],
            ]),
        ]);
    }
}
