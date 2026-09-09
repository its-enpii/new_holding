<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreTenantRequest;
use App\Http\Requests\Tenant\UpdateTenantRequest;
use App\Models\Tenant;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class TenantController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function index(Request $request): Response
    {
        $search = (string) $request->query('search', '');
        $sort = (string) $request->query('sort', 'name');
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';
        $perPage = max(5, min(50, (int) $request->query('per_page', 10)));

        $tenants = Tenant::query()
            ->when($search !== '', fn ($query) => $query->where(fn ($scope) => $scope
                ->where('name', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Admin/Tenants/Index', [
            'tenants' => $tenants,
            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Tenants/Create');
    }

    public function store(StoreTenantRequest $request): RedirectResponse
    {
        $tenant = Tenant::query()->create($request->validated());
        $this->activityLogger->log($request, 'create', $request->user(), Tenant::class, $tenant->id, ['name' => $tenant->name]);

        return redirect()->route('admin.tenants.index')->with('success', 'Tenant berhasil dibuat.');
    }

    public function show(Tenant $tenant): Response
    {
        return Inertia::render('Admin/Tenants/Show', [
            'tenant' => $tenant,
        ]);
    }

    public function edit(Tenant $tenant): Response
    {
        return Inertia::render('Admin/Tenants/Edit', [
            'tenant' => $tenant,
        ]);
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant): RedirectResponse
    {
        $tenant->update($request->validated());
        $this->activityLogger->log($request, 'update', $request->user(), Tenant::class, $tenant->id, ['name' => $tenant->name]);

        return redirect()->route('admin.tenants.index')->with('success', 'Tenant berhasil diperbarui.');
    }

    public function toggle(Request $request, Tenant $tenant): RedirectResponse
    {
        $tenant->update(['is_active' => ! $tenant->is_active]);
        $this->activityLogger->log($request, $tenant->is_active ? 'activate' : 'deactivate', $request->user(), Tenant::class, $tenant->id);

        return redirect()->back()->with('success', 'Status tenant berhasil diperbarui.');
    }

    public function destroy(Request $request, Tenant $tenant): RedirectResponse
    {
        $metadata = ['name' => $tenant->name];
        $tenant->delete();
        $this->activityLogger->log($request, 'delete', $request->user(), Tenant::class, $tenant->id, $metadata);

        return redirect()->route('admin.tenants.index')->with('success', 'Tenant berhasil dihapus.');
    }
}
