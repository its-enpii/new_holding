<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Services\ActivityLogger;
use App\Services\ReportBundleService;
use App\Services\SubsidiaryReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

final class ReportController extends Controller
{
    public function __construct(
        private readonly SubsidiaryReportService $reportService,
        private readonly ReportBundleService $bundleService,
        private readonly ActivityLogger $activityLogger,
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
        $validated = $this->validateFilters($request);
        $applications = $this->eligibleApplications($validated['tenant_id'], $validated['apps']);

        return inertia('Admin/Reports/Index', [
            'tenants' => $this->tenants(),
            'tenantApplications' => $this->applicationsForFilter($request)->values(),
            'reportTypes' => $this->reportTypes(),
            'years' => $this->years(),
            'filters' => $validated,
            'report' => $this->reportService->comparative($applications, $validated['type'], $validated['month'], $validated['year'], $validated['force']),
        ]);
    }

    public function downloadBundle(Request $request): Response
    {
        $validated = $this->validateFilters($request, bundle: true);
        $applications = $this->eligibleApplications($validated['tenant_id'], $validated['apps']);

        abort_if($applications->isEmpty(), 422, 'Tidak ada aplikasi aktif yang dapat dibundel.');

        $this->activityLogger->log($request, 'export_bundle_report', $request->user(), Tenant::class, $validated['tenant_id'], [
            'format' => 'zip',
            'mode' => $validated['mode'],
            'reports' => ReportBundleService::REPORT_TYPES,
            'year' => $validated['year'],
            'month' => $validated['month'],
            'apps' => $applications->pluck('id')->all(),
        ]);

        return $this->bundleService->download($applications, $validated['year'], $validated['month'], $validated['mode'], $validated['force']);
    }

    /**
     * @param  list<int>  $appIds
     * @return Collection<int, TenantApplication>
     */
    private function eligibleApplications(int $tenantId, array $appIds): Collection
    {
        return TenantApplication::query()
            ->whereIn('id', $appIds)
            ->where('tenant_id', $tenantId)
            ->with(['application', 'tenant'])
            ->get()
            ->filter(fn (TenantApplication $application): bool => $application->is_active
                && ! $application->isExpired()
                && ($application->application?->has_financial_report ?? false))
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function validateFilters(Request $request, bool $bundle = false): array
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'apps' => ['required', 'array', 'min:1'],
            'apps.*' => ['integer'],
            'type' => $bundle
                ? ['nullable', 'string', 'in:'.implode(',', ReportBundleService::REPORT_TYPES)]
                : ['required', 'string', 'in:'.implode(',', ReportBundleService::REPORT_TYPES)],
            'mode' => $bundle
                ? ['required', 'string', 'in:'.implode(',', ReportBundleService::ACCEPTED_MODES)]
                : ['nullable', 'string'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'force' => ['nullable'],
        ]);

        return [
            'tenant_id' => (int) $validated['tenant_id'],
            'apps' => array_map(intval(...), $validated['apps']),
            'type' => $validated['type'] ?? 'balance_sheet',
            'mode' => $validated['mode'] ?? ReportBundleService::MODE_GABUNGAN,
            'year' => (int) $validated['year'],
            'month' => isset($validated['month']) && $validated['month'] !== '' ? (int) $validated['month'] : null,
            'force' => $request->boolean('force'),
        ];
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
            'mode' => ReportBundleService::MODE_GABUNGAN,
            'year' => (int) now()->year,
            'month' => null,
            'force' => false,
        ];
    }
}
