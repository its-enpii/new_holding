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
            $now = now();
            $expiringLicenses = TenantApplication::query()
                ->where('is_active', true)
                ->whereBetween('expired_at', [$now, $now->copy()->addDays(7)])
                ->with(['application', 'tenant'])
                ->orderBy('expired_at')
                ->get();
            $expiredLicenses = TenantApplication::query()
                ->where('is_active', true)
                ->where('expired_at', '<', $now)
                ->with(['application', 'tenant'])
                ->orderByDesc('expired_at')
                ->get();
            $alerts = collect([
                ...$expiringLicenses->map(fn (TenantApplication $license) => $this->licenseAlert($license, 'expiring'))->all(),
                ...$expiredLicenses->map(fn (TenantApplication $license) => $this->licenseAlert($license, 'expired'))->all(),
            ])
                ->take(5)
                ->values();
            $expiringAt = $now->copy()->addDays(7);
            $tenants = Tenant::query()
                ->orderBy('is_active', 'desc')
                ->orderBy('name')
                ->withCount([
                    'tenantApplications as applications_count',
                    'tenantApplications as expiring_count' => fn ($query) => $query
                        ->where('is_active', true)
                        ->whereBetween('expired_at', [$now, $expiringAt]),
                    'tenantApplications as expired_count' => fn ($query) => $query
                        ->where('is_active', true)
                        ->where('expired_at', '<', $now),
                ])
                ->get();

            return Inertia::render('Dashboard', [
                'stats' => [
                    'activeTenants' => Tenant::query()->where('is_active', true)->count(),
                    'applications' => Application::query()->count(),
                    'users' => User::query()->count(),
                    'assignedApps' => TenantApplication::query()->where('is_active', true)->count(),
                ],
                'tenantList' => $tenants->map(fn (Tenant $tenant) => $this->tenantListEntry($tenant))->all(),
                'applicationList' => $this->applicationList(),
                'licenseAlerts' => [
                    'items' => $alerts,
                    'total' => $expiringLicenses->count() + $expiredLicenses->count(),
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
                'sub_tenant_code' => $app->sub_tenant_code,
                'is_active' => $app->is_active,
                'is_expired' => $app->isExpired(),
                'is_expiring_soon' => $app->expired_at !== null && ! $app->isExpired() && $app->expired_at->lessThanOrEqualTo(now()->addDays(7)),
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

    /**
     * @return array<string, mixed>
     */
    private function tenantListEntry(Tenant $tenant): array
    {
        return [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'is_active' => $tenant->is_active,
            'applications_count' => $tenant->applications_count,
            'expiring_count' => $tenant->expiring_count,
            'expired_count' => $tenant->expired_count,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function applicationList(): array
    {
        return Application::query()
            ->orderBy('name')
            ->withCount([
                'tenantApplications as tenants_using_count',
                'tenantApplications as active_licenses_count' => fn ($query) => $query->where('is_active', true),
                'tenantApplications as connected_count' => fn ($query) => $query->where('connection_status', 'connected'),
                'tenantApplications as connection_issues_count' => fn ($query) => $query->whereIn('connection_status', ['auth_error', 'offline']),
            ])
            ->get()
            ->map(fn (Application $application) => [
                'id' => $application->id,
                'name' => $application->name,
                'slug' => $application->slug,
                'description' => $application->description,
                'icon_path' => $application->icon_path,
                'is_active' => $application->is_active,
                'tenants_using_count' => $application->tenants_using_count,
                'active_licenses_count' => $application->active_licenses_count,
                'connected_count' => $application->connected_count,
                'connection_issues_count' => $application->connection_issues_count,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function licenseAlert(TenantApplication $license, string $status): array
    {
        return [
            'id' => $license->id,
            'status' => $status,
            'label' => $license->label,
            'application_name' => $license->application?->name,
            'tenant_name' => $license->tenant?->name,
            'expired_at' => $license->expired_at?->toIso8601String(),
        ];
    }
}
