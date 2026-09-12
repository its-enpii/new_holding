<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\TenantApplication;
use App\Services\ActivityLogger;
use App\Services\ReportBundleService;
use App\Services\SubsidiaryReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

final class ReportController extends Controller
{
    private const REPORT_TYPES = [
        'balance_sheet' => 'Neraca',
        'income_statement' => 'Laba Rugi',
        'cash_flow' => 'Arus Kas',
        'equity_changes' => 'Perubahan Ekuitas',
        'calk' => 'CALK',
    ];

    public function __construct(
        private readonly SubsidiaryReportService $reportService,
        private readonly ActivityLogger $activityLogger,
        private readonly ReportBundleService $bundleService,
    ) {}

    public function index(Request $request): InertiaResponse
    {
        return inertia('Tenant/Reports/Index', [
            'tenantApplications' => $this->eligibleApplications($request)->values(),
            'reportTypes' => self::REPORT_TYPES,
            'years' => $this->years(),
            'filters' => $this->defaultFilters(),
        ]);
    }

    public function show(Request $request): InertiaResponse
    {
        $validated = $this->validateReportQuery($request);
        $applications = $this->selectedApplications($request, $validated['apps']);
        $comparative = $this->reportService->comparative($applications, $validated['type'], $validated['month'], $validated['year'], $validated['force']);

        $this->activityLogger->log($request, 'view_report', $request->user(), TenantApplication::class, $applications->first()?->id, $this->metadata($validated, $applications));

        return inertia('Tenant/Reports/Index', [
            ...$this->indexProps($request),
            'filters' => $validated,
            'report' => $comparative,
        ]);
    }

    public function exportCsv(Request $request): Response
    {
        $validated = $this->validateReportQuery($request);
        $applications = $this->selectedApplications($request, $validated['apps']);
        $comparative = $this->reportService->comparative($applications, $validated['type'], $validated['month'], $validated['year'], $validated['force']);

        $this->activityLogger->log($request, 'export_report', $request->user(), TenantApplication::class, $applications->first()?->id, ['format' => 'csv', ...$this->metadata($validated, $applications)]);

        return response()->streamDownload(function () use ($comparative, $applications): void {
            $stream = fopen('php://output', 'wb');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['Kode', 'Nama', ...$applications->pluck('id')->all()], ';');

            foreach ($comparative['rows'] as $row) {
                fputcsv($stream, [$row['code'], $row['name'], ...array_map($this->formatIndonesian(...), $row['values'])], ';');
            }

            foreach ($comparative['totals'] as $applicationId => $totals) {
                if ($totals === null) {
                    continue;
                }

                fputcsv($stream, ['TOTAL', $applicationId, ...array_map($this->formatIndonesian(...), array_values($totals))], ';');
            }
        }, $this->filename($validated, 'csv'), ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportPdf(Request $request): Response
    {
        $validated = $this->validateReportQuery($request);
        $applications = $this->selectedApplications($request, $validated['apps']);
        $comparative = $this->reportService->comparative($applications, $validated['type'], $validated['month'], $validated['year'], $validated['force']);

        $this->activityLogger->log($request, 'export_report', $request->user(), TenantApplication::class, $applications->first()?->id, ['format' => 'pdf', ...$this->metadata($validated, $applications)]);

        $pdf = Pdf::loadView('reports.comparative', [
            'title' => self::REPORT_TYPES[$validated['type']],
            'period' => $this->periodLabel($validated),
            'applications' => $applications,
            'report' => $comparative,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream($this->filename($validated, 'pdf'));
    }

    public function downloadBundle(Request $request): Response
    {
        $validated = $this->validateBundleQuery($request);
        $applications = $this->selectedApplications($request, $validated['apps']);

        abort_if($applications->isEmpty(), 422, 'Tidak ada aplikasi aktif yang dapat dibundel.');

        $this->activityLogger->log($request, 'export_bundle_report', $request->user(), TenantApplication::class, $applications->first()?->id, [
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
     * @return array<string, mixed>
     */
    private function indexProps(Request $request): array
    {
        return [
            'tenantApplications' => $this->eligibleApplications($request)->values(),
            'reportTypes' => self::REPORT_TYPES,
            'years' => $this->years(),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function eligibleApplications(Request $request): Collection
    {
        return TenantApplication::query()
            ->where('tenant_id', $request->user()?->tenant_id)
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
     * @return Collection<int, TenantApplication>
     */
    private function selectedApplications(Request $request, array $appIds): Collection
    {
        $ownedApplications = TenantApplication::query()
            ->whereIn('id', $appIds)
            ->where('tenant_id', $request->user()?->tenant_id)
            ->with('application')
            ->get();

        if ($ownedApplications->count() !== count(array_unique($appIds))) {
            abort(403, 'Aplikasi tidak valid untuk laporan ini.');
        }

        return $ownedApplications
            ->filter(fn (TenantApplication $application): bool => $application->is_active
                && ! $application->isExpired()
                && ($application->application?->has_financial_report ?? false))
            ->values();
    }

    /**
     * @return array{apps: list<int>, type: string, year: int, month: int|null, force: bool}
     */
    private function validateReportQuery(Request $request): array
    {
        $validated = $request->validate([
            'apps' => ['required', 'array', 'min:1'],
            'apps.*' => ['integer'],
            'type' => ['required', 'string', 'in:balance_sheet,income_statement,cash_flow,equity_changes,calk'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'force' => ['nullable'],
        ]);

        return [
            'apps' => array_map(intval(...), $validated['apps']),
            'type' => $validated['type'],
            'year' => (int) $validated['year'],
            'month' => isset($validated['month']) && $validated['month'] !== '' ? (int) $validated['month'] : null,
            'force' => $request->boolean('force'),
        ];
    }

    /**
     * @return array{apps: list<int>, year: int, month: int|null, mode: string, force: bool}
     */
    private function validateBundleQuery(Request $request): array
    {
        $validated = $request->validate([
            'apps' => ['required', 'array', 'min:1'],
            'apps.*' => ['integer'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'mode' => ['required', 'string', 'in:'.implode(',', ReportBundleService::ACCEPTED_MODES)],
            'force' => ['nullable'],
        ]);

        return [
            'apps' => array_map(intval(...), $validated['apps']),
            'year' => (int) $validated['year'],
            'month' => isset($validated['month']) && $validated['month'] !== '' ? (int) $validated['month'] : null,
            'mode' => $validated['mode'],
            'force' => $request->boolean('force'),
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
     * @return array{apps: list<int>, type: string, year: int, month: int|null, force: bool}
     */
    private function defaultFilters(): array
    {
        return [
            'apps' => [],
            'type' => 'balance_sheet',
            'year' => (int) now()->year,
            'month' => null,
            'force' => false,
        ];
    }

    /**
     * @param  array{apps: list<int>, type: string, year: int, month: int|null, force: bool}  $validated
     * @param  Collection<int, TenantApplication>  $applications
     * @return array<string, mixed>
     */
    private function metadata(array $validated, Collection $applications): array
    {
        return [
            'type' => $validated['type'],
            'year' => $validated['year'],
            'month' => $validated['month'],
            'apps' => $applications->pluck('id')->all(),
        ];
    }

    private function periodLabel(array $validated): string
    {
        return $validated['month'] === null
            ? 'Tahunan '.$validated['year']
            : Carbon::create($validated['year'], $validated['month'])->translatedFormat('F Y');
    }

    private function filename(array $validated, string $extension): string
    {
        $period = $validated['month'] === null
            ? $validated['year'].'-ALL'
            : sprintf('%d-%02d', $validated['year'], $validated['month']);

        return 'laporan-'.$validated['type'].'-'.$period.'.'.$extension;
    }

    private function formatIndonesian(int|float|null $value): string
    {
        return $value === null ? '-' : number_format($value, 2, ',', '.');
    }
}
