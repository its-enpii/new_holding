<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Application\StoreApplicationRequest;
use App\Http\Requests\Application\UpdateApplicationRequest;
use App\Models\Application;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ApplicationController extends Controller
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

        $applications = Application::query()
            ->when($search !== '', fn ($query) => $query->where(fn ($scope) => $scope
                ->where('name', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%")
                ->orWhere('base_url', 'like', "%{$search}%")))
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Admin/Applications/Index', [
            'applications' => $applications,
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
        return Inertia::render('Admin/Applications/Create');
    }

    public function store(StoreApplicationRequest $request): RedirectResponse
    {
        $application = Application::query()->create($request->validated());
        $this->activityLogger->log($request, 'create', $request->user(), Application::class, $application->id, ['name' => $application->name]);

        return redirect()->route('admin.applications.index')->with('success', 'Aplikasi berhasil dibuat.');
    }

    public function show(Application $application): Response
    {
        return Inertia::render('Admin/Applications/Show', [
            'application' => $application,
        ]);
    }

    public function edit(Application $application): Response
    {
        return Inertia::render('Admin/Applications/Edit', [
            'application' => $application,
        ]);
    }

    public function update(UpdateApplicationRequest $request, Application $application): RedirectResponse
    {
        $application->update($request->validated());
        $this->activityLogger->log($request, 'update', $request->user(), Application::class, $application->id, ['name' => $application->name]);

        return redirect()->route('admin.applications.index')->with('success', 'Aplikasi berhasil diperbarui.');
    }

    public function toggle(Request $request, Application $application): RedirectResponse
    {
        $application->update(['is_active' => ! $application->is_active]);
        $this->activityLogger->log($request, $application->is_active ? 'activate' : 'deactivate', $request->user(), Application::class, $application->id);

        return redirect()->back()->with('success', 'Status aplikasi berhasil diperbarui.');
    }

    public function destroy(Request $request, Application $application): RedirectResponse
    {
        $metadata = ['name' => $application->name];
        $application->delete();
        $this->activityLogger->log($request, 'delete', $request->user(), Application::class, $application->id, $metadata);

        return redirect()->route('admin.applications.index')->with('success', 'Aplikasi berhasil dihapus.');
    }
}
