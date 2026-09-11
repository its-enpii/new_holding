<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Services\SubsidiaryReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Response as InertiaResponse;

final class ReportController extends Controller
{
    public function __construct(
        private readonly SubsidiaryReportService $reportService,
    ) {}

    public function index(Request $request): InertiaResponse
    {
        return inertia('Admin/Reports/Index', [
            'tenants' => $this->tenants(),
            'tenantApplications' => $this->applicationsForFilter($request)->values(),
            'reportTypes' => $this->reportTypes(),
            'years' => $this->years(),
            'filters' => $this->defaultFilters(),
        ]);
    }

    public function show(Request $request): InertiaResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'apps' => ['required', 'array', 'min:1'],
            'apps.*' => ['integer'],
            'type' => ['required', 'string', 'in:balance_sheet,income_statement,cash_flow,equity_changes,calk'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'force' => ['nullable'],
        ]);

        $filters = [
            'tenant_id' => (int) $validated['tenant_id'],
            'apps' => array_map(intval(...), $validated['apps']),
            'type' => $validated['type'],
            'year' => (int) $validated['year'],
            'month' => isset($validated['month']) && $validated['month'] !== '' ? (int) $validated['month'] : null,
            'force' => $request->boolean('force'),
        ];

        $applications = TenantApplication::query()
            ->whereIn('id', $filters['apps'])
            ->where('tenant_id', $filters['tenant_id'])
            ->with('application')
            ->get()
            ->filter(fn (TenantApplication $application): bool => $application->is_active
                && ! $application->isExpired()
                && ($application->application?->has_financial_report ?? false))
            ->values();

        return inertia('Admin/Reports/Index', [
            'tenants' => $this->tenants(),
            'tenantApplications' => $this->applicationsForFilter($request)->values(),
            'reportTypes' => $this->reportTypes(),
            'years' => $this->years(),
            'filters' => $filters,
            'report' => $this->reportService->comparative($applications, $filters['type'], $filters['month'], $filters['year'], $filters['force']),
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function tenants(): Collection
    {
        return Tenant::query()->orderBy('name')->get(['id', 'name'])
            ->map(fn (Tenant $tenant): array => ['id' => $tenant->id, 'name' => $tenant->name]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function applicationsForFilter(Request $request): Collection
    {
        return TenantApplication::query()
            ->when($request->filled('tenant_id'), fn ($query) => $query->where('tenant_id', $request->integer('tenant_id')))
            ->with('application:id,name,has_financial_report')
            ->orderBy('label')
            ->get()
            ->map(fn (TenantApplication $application): array => [
                'id' => $application->id,
                'label' => $application->label ?? $application->application?->name,
                'application_name' => $application->application?->name,
                'has_financial_report' => $application->application?->has_financial_report ?? false,
                'is_active' => $application->is_active,
                'is_expired' => $application->isExpired(),
            ]);
    }

    /**
     * @return array<string, string>
     */
    private function reportTypes(): array
    {
        return [
            'balance_sheet' => 'Neraca',
            'income_statement' => 'Laba Rugi',
            'cash_flow' => 'Arus Kas',
            'equity_changes' => 'Perubahan Ekuitas',
            'calk' => 'CALK',
        ];
    }

    /**
     * @return list<int>
     */
    private function years(): array
    {
        return range((int) now()->year - 4, (int) now()->year + 1);
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultFilters(): array
    {
        return [
            'tenant_id' => null,
            'apps' => [],
            'type' => 'balance_sheet',
            'year' => (int) now()->year,
            'month' => null,
            'force' => false,
        ];
    }
}
