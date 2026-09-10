<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TenantApplication\StoreTenantApplicationRequest;
use App\Http\Requests\TenantApplication\UpdateTenantApplicationRequest;
use App\Models\Application;
use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Services\ActivityLogger;
use App\Services\AppConnectionCheckService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

final class TenantApplicationController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function index(Request $request, Tenant $tenant): Response
    {
        $search = (string) $request->query('search', '');
        $sort = (string) $request->query('sort', 'created_at');
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';
        $perPage = max(5, min(50, (int) $request->query('per_page', 10)));

        $applications = TenantApplication::query()
            ->with('application')
            ->where('tenant_id', $tenant->id)
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($q) use ($search): void {
                    $q->where('label', 'like', "%{$search}%")
                        ->orWhere('instance_url', 'like', "%{$search}%")
                        ->orWhereHas('application', fn ($appQuery) => $appQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when(in_array($sort, ['created_at', 'label', 'instance_url', 'is_active', 'expired_at'], true), function ($query) use ($sort, $direction): void {
                $query->orderBy($sort, $direction);
            }, function ($query): void {
                $query->latest();
            })
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Admin/TenantApplications/Index', [
            'tenant' => $tenant,
            'tenantApplications' => $applications,
            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $perPage,
            ],
            'flash' => [
                'new_api_secret' => $request->session()->get('new_api_secret'),
            ],
        ]);
    }

    public function create(Tenant $tenant): Response
    {
        $existingAppIds = $tenant->tenantApplications()->pluck('application_id')->all();
        $applications = Application::query()
            ->where('is_active', true)
            ->whereNotIn('id', $existingAppIds)
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'base_url', 'icon_path']);

        return Inertia::render('Admin/TenantApplications/Create', [
            'tenant' => $tenant,
            'applications' => $applications,
        ]);
    }

    public function store(StoreTenantApplicationRequest $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validated();
        $validated['tenant_id'] = $tenant->id;
        $validated['api_secret'] = Str::random(40);
        $validated['is_active'] = $validated['is_active'] ?? true;
        if ($validated['is_active']) {
            $validated['activated_at'] = now();
        }

        $tenantApplication = TenantApplication::query()->create($validated);
        $tenantApplication->load('application');

        $this->activityLogger->log(
            $request,
            'assign_app',
            $request->user(),
            TenantApplication::class,
            $tenantApplication->id,
            [
                'tenant_name' => $tenant->name,
                'application_name' => $tenantApplication->application?->name,
                'instance_url' => $tenantApplication->instance_url,
            ]
        );

        return redirect()
            ->route('admin.tenants.applications.show', [$tenant->id, $tenantApplication->id])
            ->with('success', 'Aplikasi berhasil di-assign ke tenant.')
            ->with('new_api_secret', $tenantApplication->api_secret);
    }

    public function show(Tenant $tenant, TenantApplication $application): Response
    {
        abort_unless($application->tenant_id === $tenant->id, 404);
        $application->load('application');

        return Inertia::render('Admin/TenantApplications/Show', [
            'tenant' => $tenant,
            'tenantApplication' => $application,
            'newApiSecret' => session('new_api_secret'),
        ]);
    }

    public function edit(Tenant $tenant, TenantApplication $application): Response
    {
        abort_unless($application->tenant_id === $tenant->id, 404);
        $application->load('application');

        return Inertia::render('Admin/TenantApplications/Edit', [
            'tenant' => $tenant,
            'tenantApplication' => $application,
        ]);
    }

    public function update(UpdateTenantApplicationRequest $request, Tenant $tenant, TenantApplication $application): RedirectResponse
    {
        abort_unless($application->tenant_id === $tenant->id, 404);

        $validated = $request->validated();
        $application->update($validated);
        $application->load('application');

        $this->activityLogger->log(
            $request,
            'update_app_license',
            $request->user(),
            TenantApplication::class,
            $application->id,
            [
                'tenant_name' => $tenant->name,
                'application_name' => $application->application?->name,
                'is_active' => $application->is_active,
                'expired_at' => $application->expired_at?->toIso8601String(),
            ]
        );

        return redirect()
            ->route('admin.tenants.applications.index', $tenant->id)
            ->with('success', 'Lisensi aplikasi berhasil diperbarui.');
    }

    public function destroy(Request $request, Tenant $tenant, TenantApplication $application): RedirectResponse
    {
        abort_unless($application->tenant_id === $tenant->id, 404);
        $application->load('application');

        $metadata = [
            'tenant_name' => $tenant->name,
            'application_name' => $application->application?->name,
        ];

        $application->delete();

        $this->activityLogger->log(
            $request,
            'revoke_app',
            $request->user(),
            TenantApplication::class,
            $application->id,
            $metadata
        );

        return redirect()
            ->route('admin.tenants.applications.index', $tenant->id)
            ->with('success', 'Lisensi aplikasi berhasil dicabut (unassign).');
    }

    public function regenerateSecret(Request $request, Tenant $tenant, TenantApplication $application): RedirectResponse
    {
        abort_unless($application->tenant_id === $tenant->id, 404);

        $newSecret = Str::random(40);
        $application->update(['api_secret' => $newSecret]);
        $application->load('application');

        $this->activityLogger->log(
            $request,
            'regenerate_api_secret',
            $request->user(),
            TenantApplication::class,
            $application->id,
            [
                'tenant_name' => $tenant->name,
                'application_name' => $application->application?->name,
            ]
        );

        return redirect()
            ->back()
            ->with('success', 'API Secret berhasil di-regenerate. Secret lama sudah tidak berlaku.')
            ->with('new_api_secret', $newSecret);
    }

    public function testConnection(Request $request, AppConnectionCheckService $connectionCheckService, Tenant $tenant, TenantApplication $application): JsonResponse
    {
        abort_unless($application->tenant_id === $tenant->id, 404);

        $result = $connectionCheckService->check($application);
        $application->update([
            'connection_status' => $result['status'],
            'connection_latency_ms' => $result['latency_ms'],
            'connection_checked_at' => now(),
        ]);

        $this->activityLogger->log(
            $request,
            'test_connection',
            $request->user(),
            TenantApplication::class,
            $application->id,
            [
                'tenant_name' => $tenant->name,
                'application_name' => $application->application?->name,
                'connection_status' => $result['status'],
                'connection_latency_ms' => $result['latency_ms'],
            ]
        );

        return response()->json($result);
    }
}
