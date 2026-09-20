<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\Tenant\ReportController;
use App\Models\ActivityLog;
use App\Models\Application;
use App\Models\ReportCache;
use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Models\User;
use App\Services\ReportBundleService;
use App\Services\SubsidiaryReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class SubsidiaryReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_second_fetch_uses_cache_without_second_http_call(): void
    {
        $tenantApplication = TenantApplication::factory()->create(['instance_url' => 'https://sidbm.test/app']);
        $service = app(SubsidiaryReportService::class);

        Http::fake([
            'https://sidbm.test/app/api/v1/holding/reports/balance-sheet?*' => Http::response($this->balanceSheetPayload(), 200),
        ]);

        $first = $service->fetch($tenantApplication, 'balance_sheet', 6, 2026);
        $second = $service->fetch($tenantApplication, 'balance_sheet', 6, 2026);

        Http::assertSentCount(1);
        $this->assertSame(1, ReportCache::query()->count());
        $this->assertSame($first['data']['sections'][0]['name'], $second['data']['sections'][0]['name']);
    }

    public function test_force_bypasses_valid_cache(): void
    {
        $tenantApplication = TenantApplication::factory()->create(['instance_url' => 'https://sidbm.test/app']);
        $service = app(SubsidiaryReportService::class);

        Http::fake([
            'https://sidbm.test/app/api/v1/holding/reports/balance-sheet?*' => Http::response($this->balanceSheetPayload(), 200),
        ]);

        $service->fetch($tenantApplication, 'balance_sheet', null, 2026);
        $service->fetch($tenantApplication, 'balance_sheet', null, 2026, true);

        Http::assertSentCount(2);
    }

    public function test_expired_cache_is_refetched(): void
    {
        $tenantApplication = TenantApplication::factory()->create(['instance_url' => 'https://sidbm.test/app']);

        ReportCache::factory()->expired()->create([
            'tenant_application_id' => $tenantApplication->id,
            'report_type' => 'cash_flow',
            'period' => '2026-ALL',
        ]);

        $service = app(SubsidiaryReportService::class);
        Http::fake([
            'https://sidbm.test/app/api/v1/holding/reports/cash-flow?*' => Http::response(['status' => 'success', 'data' => []], 200),
        ]);

        $service->fetch($tenantApplication, 'cash_flow', null, 2026);

        Http::assertSentCount(1);
    }

    public function test_comparative_matches_only_identical_code_and_name(): void
    {
        $tenant = Tenant::factory()->create();
        $first = TenantApplication::factory()->create(['tenant_id' => $tenant->id, 'instance_url' => 'https://one.test']);
        $second = TenantApplication::factory()->create(['tenant_id' => $tenant->id, 'instance_url' => 'https://two.test']);
        $service = app(SubsidiaryReportService::class);

        Http::fake([
            'https://one.test/api/v1/holding/reports/balance-sheet?*' => Http::response($this->balanceSheetPayload(), 200),
            'https://two.test/api/v1/holding/reports/balance-sheet?*' => Http::response($this->alternateBalanceSheetPayload(), 200),
        ]);

        $report = $service->comparative(collect([$first, $second]), 'balance_sheet', 6, 2026);

        $this->assertCount(4, $report['rows']);
        $this->assertSame(500, $report['rows'][0]['values'][$first->id]);
        $this->assertSame(250, $report['rows'][0]['values'][$second->id]);
        $this->assertNull($report['rows'][2]['values'][$second->id]);
        $this->assertSame(500, $report['totals'][$first->id]['assets']);
    }

    public function test_server_error_maps_to_offline_state(): void
    {
        $tenantApplication = TenantApplication::factory()->create(['instance_url' => 'https://offline.test']);
        $service = app(SubsidiaryReportService::class);

        Http::fake(['https://offline.test/*' => Http::response('error', 500)]);
        $report = $service->comparative(collect([$tenantApplication]), 'balance_sheet', null, 2026);

        $this->assertSame('offline', $report['appStates'][$tenantApplication->id]);
        Http::assertSentCount(2);
    }

    public function test_connection_failure_maps_to_offline_state(): void
    {
        $tenantApplication = TenantApplication::factory()->create(['instance_url' => 'https://aplikasi.example.test']);
        $service = app(SubsidiaryReportService::class);
        $attempts = 0;

        Http::fake(function () use (&$attempts): never {
            $attempts++;

            throw new ConnectionException('cURL error 6: Could not resolve host');
        });

        $report = $service->comparative(collect([$tenantApplication]), 'balance_sheet', null, 2026);

        $this->assertSame('offline', $report['appStates'][$tenantApplication->id]);
        $this->assertNull($report['totals'][$tenantApplication->id]);
        $this->assertSame(2, $attempts);
    }

    public function test_unauthorized_maps_to_auth_error_state(): void
    {
        $tenantApplication = TenantApplication::factory()->create(['instance_url' => 'https://auth.test']);
        $service = app(SubsidiaryReportService::class);

        Http::fake(['https://auth.test/*' => Http::response(['message' => 'invalid token'], 401)]);
        $report = $service->comparative(collect([$tenantApplication]), 'income_statement', 3, 2026);

        $this->assertSame('auth_error', $report['appStates'][$tenantApplication->id]);
        Http::assertSentCount(1);
    }

    public function test_tenant_cannot_view_apps_from_another_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantOwner()->for($tenant)->create();
        $foreignApp = TenantApplication::factory()->create(['instance_url' => 'https://foreign.test']);

        $this->actingAs($user)
            ->get(route('tenant.reports.show', ['apps' => [$foreignApp->id], 'type' => 'balance_sheet', 'year' => 2026]))
            ->assertForbidden();

        $this->assertSame(0, ActivityLog::query()->where('action', 'view_report')->count());
    }

    public function test_inactive_expired_and_non_financial_apps_are_filtered(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantOwner()->for($tenant)->create();
        $application = Application::factory()->withoutFinancialReport()->create();
        $nonFinancial = TenantApplication::factory()->create(['tenant_id' => $tenant->id, 'application_id' => $application->id]);
        $inactive = TenantApplication::factory()->inactive()->create(['tenant_id' => $tenant->id]);
        $expired = TenantApplication::factory()->expired()->create(['tenant_id' => $tenant->id]);
        $eligible = TenantApplication::factory()->create(['tenant_id' => $tenant->id]);

        Http::fake(['*/api/v1/holding/reports/*' => Http::response($this->balanceSheetPayload(), 200)]);

        $this->actingAs($user)
            ->get(route('tenant.reports.show', ['apps' => [$nonFinancial->id, $inactive->id, $expired->id, $eligible->id], 'type' => 'balance_sheet', 'year' => 2026]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('report.appStates', fn ($states) => count($states->toArray()) === 1));
    }

    public function test_tenant_can_export_report_as_csv(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantOwner()->for($tenant)->create();
        $application = TenantApplication::factory()->create(['tenant_id' => $tenant->id]);

        Http::fake(['*/api/v1/holding/reports/*' => Http::response($this->balanceSheetPayload(), 200)]);

        $response = $this->actingAs($user)->get(route('tenant.reports.export.csv', [
            'apps' => [$application->id], 'type' => 'balance_sheet', 'year' => 2026, 'month' => 6,
        ]));

        $response->assertOk();
        $content = $response->streamedContent();

        $this->assertSame("\xEF\xBB\xBF", substr($content, 0, 3));
        $this->assertStringContainsString('Kode;Nama;'.$application->id, $content);
        $this->assertStringContainsString('1.000,00', $content);
    }

    public function test_tenant_can_export_report_as_pdf_stream(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantOwner()->for($tenant)->create();
        $application = TenantApplication::factory()->create(['tenant_id' => $tenant->id]);

        Http::fake(['*/api/v1/holding/reports/*' => Http::response($this->balanceSheetPayload(), 200)]);

        $this->actingAs($user)
            ->get(route('tenant.reports.export.pdf', ['apps' => [$application->id], 'type' => 'balance_sheet', 'year' => 2026]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_csv_period_columns_detected_from_report_shape(): void
    {
        $controller = app(ReportController::class);
        $detector = new \ReflectionMethod($controller, 'hasPeriodColumns');
        $detector->setAccessible(true);

        $this->assertTrue($detector->invoke($controller, ['rows' => [['values' => [1 => ['prior' => 1, 'current' => 2, 'ytd' => 3]]]], 'totals' => []]));
        $this->assertTrue($detector->invoke($controller, ['rows' => [['values' => [1 => null]]], 'totals' => [1 => ['net_income' => ['prior' => 0, 'current' => 0, 'ytd' => 5]]]]));
        $this->assertFalse($detector->invoke($controller, ['rows' => [['values' => [1 => 500, 2 => null]]], 'totals' => [1 => ['assets' => 500]]]), 'Neraca lama harus tetap satu kolom per aplikasi.');
    }

    public function test_admin_report_page_is_superadmin_only(): void
    {
        $owner = User::factory()->tenantOwner()->for(Tenant::factory()->create())->create();

        $this->actingAs($owner)->get(route('admin.reports.index'))->assertForbidden();
    }

    public function test_superadmin_can_preview_tenant_report(): void
    {
        $tenant = Tenant::factory()->create();
        $superadmin = User::factory()->superadmin()->create();
        $application = TenantApplication::factory()->create(['tenant_id' => $tenant->id]);

        Http::fake(['*/api/v1/holding/reports/*' => Http::response($this->balanceSheetPayload(), 200)]);

        $this->actingAs($superadmin)
            ->get(route('admin.reports.show', ['tenant_id' => $tenant->id, 'apps' => [$application->id], 'type' => 'balance_sheet', 'year' => 2026]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Reports/Index')->has('report.appStates'));
    }

    public function test_tenant_report_view_renders_offline_badge_when_app_host_is_down(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantOwner()->for($tenant)->create();
        $application = TenantApplication::factory()->create(['tenant_id' => $tenant->id, 'instance_url' => 'https://aplikasi.example.test']);

        Http::fake(function (): never {
            throw new ConnectionException('cURL error 6: Could not resolve host');
        });

        $this->actingAs($user)
            ->get(route('tenant.reports.show', ['apps' => [$application->id], 'type' => 'balance_sheet', 'year' => 2026]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('report.appStates', [$application->id => 'offline']));
    }

    public function test_tenant_report_view_accepts_string_boolean_query_parameters(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantOwner()->for($tenant)->create();
        $application = Application::factory()->create();
        $tenantApplication = TenantApplication::factory()->create([
            'tenant_id' => $tenant->id,
            'application_id' => $application->id,
            'is_active' => true,
            'expired_at' => now()->addYear(),
        ]);

        Http::fake([
            '*' => Http::response($this->balanceSheetPayload(), 200),
        ]);

        $this->actingAs($user)
            ->get("/tenant/reports/view?apps[]={$tenantApplication->id}&type=balance_sheet&year=2026&month=&force=false")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Tenant/Reports/Index')
                ->has('report.rows'));

        $this->actingAs($user)
            ->get("/tenant/reports/view?apps[]={$tenantApplication->id}&type=balance_sheet&year=2026&month=&force=true")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Tenant/Reports/Index')
                ->has('report.rows'));
    }

    public function test_income_statement_normalizes_hierarchical_groups_into_triples(): void
    {
        $tenantApplication = TenantApplication::factory()->create(['instance_url' => 'https://sidbm.test']);
        $service = app(SubsidiaryReportService::class);

        Http::fake([
            'https://sidbm.test/api/v1/holding/reports/income-statement?*' => Http::response($this->groupIncomeStatementPayload(), 200),
        ]);

        $report = $service->comparative(collect([$tenantApplication]), 'income_statement', 6, 2026);
        $rows = array_column($report['rows'], null, 'key');

        $this->assertSame(
            ['prior' => 100.0, 'current' => 250.0, 'ytd' => 1_000.0],
            $rows['4.1.01.01||Penjualan']['values'][$tenantApplication->id],
        );
        $this->assertSame(2, $rows['4.1.01.01||Penjualan']['level']);
        $this->assertNull($report['meta']['variant'], 'Pola A tidak mengirim varian CoA.');
        $this->assertSame([], $report['meta']['warnings']);
        $this->assertSame(1_200, $report['totals'][$tenantApplication->id]['revenue']);
    }

    public function test_income_statement_normalizes_akubumdes_sections_and_summary(): void
    {
        $tenantApplication = TenantApplication::factory()->create(['instance_url' => 'https://akubumdes.test']);
        $service = app(SubsidiaryReportService::class);

        Http::fake([
            'https://akubumdes.test/api/v1/holding/reports/income-statement?*' => Http::response($this->sectionIncomeStatementPayload(), 200),
        ]);

        $report = $service->comparative(collect([$tenantApplication]), 'income_statement', 6, 2026);
        $rows = array_column($report['rows'], null, 'key');

        $this->assertSame('trading', $report['meta']['variant']);
        $this->assertSame(
            ['prior' => 0.0, 'current' => 400.0, 'ytd' => 1_000.0],
            $rows['4.1.01.01||Penjualan']['values'][$tenantApplication->id],
        );
        $this->assertSame(
            ['prior' => 0.0, 'current' => 0.0, 'ytd' => 250.0],
            $rows['||Laba Setelah Pajak']['values'][$tenantApplication->id],
        );
        $this->assertSame(
            250.0,
            $report['totals'][$tenantApplication->id]['laba_rugi_normalized_after_tax'],
            'Total laba rugi hasil normalisasi harus skalar agar pengekspor lama tetap terbaca.',
        );
        $this->assertSame(1_200, $report['totals'][$tenantApplication->id]['revenue'], 'Total warisan sumber tidak boleh hilang.');
    }

    public function test_income_statement_matches_two_different_source_shapes_on_one_row(): void
    {
        $tenant = Tenant::factory()->create();
        $sidbm = TenantApplication::factory()->create(['tenant_id' => $tenant->id, 'instance_url' => 'https://sidbm.test']);
        $akubumdes = TenantApplication::factory()->create(['tenant_id' => $tenant->id, 'instance_url' => 'https://akubumdes.test']);
        $service = app(SubsidiaryReportService::class);

        Http::fake([
            'https://sidbm.test/api/v1/holding/reports/income-statement?*' => Http::response($this->groupIncomeStatementPayload(), 200),
            'https://akubumdes.test/api/v1/holding/reports/income-statement?*' => Http::response($this->sectionIncomeStatementPayload(), 200),
        ]);

        $report = $service->comparative(collect([$sidbm, $akubumdes]), 'income_statement', 6, 2026);
        $rows = array_column($report['rows'], null, 'key');

        $this->assertSame(
            ['prior' => 100.0, 'current' => 250.0, 'ytd' => 1_000.0],
            $rows['4.1.01.01||Penjualan']['values'][$sidbm->id],
        );
        $this->assertSame(
            ['prior' => 0.0, 'current' => 400.0, 'ytd' => 1_000.0],
            $rows['4.1.01.01||Penjualan']['values'][$akubumdes->id],
            'Kode dan nama akun yang sama harus bertemu pada satu baris meskipun bentuk sumber berbeda.',
        );
        $closing = $rows['||Laba Setelah Pajak']['values'];
        $this->assertSame(250.0, $closing[$akubumdes->id]['ytd']);
        $this->assertNull($closing[$sidbm->id], 'Sumber tanpa summary tidak boleh mengarang baris penutup.');
    }

    public function test_income_statement_refetches_with_force_and_keeps_normalized_rows(): void
    {
        $tenantApplication = TenantApplication::factory()->create(['instance_url' => 'https://sidbm.test']);
        $service = app(SubsidiaryReportService::class);

        Http::fake([
            'https://sidbm.test/api/v1/holding/reports/income-statement?*' => Http::response($this->groupIncomeStatementPayload(), 200),
        ]);

        $first = $service->comparative(collect([$tenantApplication]), 'income_statement', 6, 2026, false);
        $second = $service->comparative(collect([$tenantApplication]), 'income_statement', 6, 2026, true);

        Http::assertSentCount(2);
        $this->assertSame($first['rows'], $second['rows']);
        $this->assertSame(1, ReportCache::query()->where('report_type', 'income_statement')->count());
    }

    public function test_non_periodic_reports_keep_single_scalar_values(): void
    {
        $tenantApplication = TenantApplication::factory()->create(['instance_url' => 'https://sidbm.test']);
        $service = app(SubsidiaryReportService::class);

        Http::fake([
            'https://sidbm.test/api/v1/holding/reports/cash-flow?*' => Http::response($this->cashFlowPayload(), 200),
        ]);

        $report = $service->comparative(collect([$tenantApplication]), 'cash_flow', 6, 2026);
        $rows = array_column($report['rows'], null, 'key');

        $this->assertSame(610_000, $rows['1||OPERASI']['values'][$tenantApplication->id]);
        $this->assertNull($report['meta']['variant']);
    }

    public function test_tenant_can_export_income_statement_csv_with_period_columns(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantOwner()->for($tenant)->create();
        $sidbm = TenantApplication::factory()->create(['tenant_id' => $tenant->id, 'instance_url' => 'https://sidbm.test']);
        $akubumdes = TenantApplication::factory()->create(['tenant_id' => $tenant->id, 'instance_url' => 'https://akubumdes.test']);

        Http::fake([
            'https://sidbm.test/api/v1/holding/reports/income-statement?*' => Http::response($this->groupIncomeStatementPayload(), 200),
            'https://akubumdes.test/api/v1/holding/reports/income-statement?*' => Http::response($this->sectionIncomeStatementPayload(), 200),
        ]);

        $response = $this->actingAs($user)->get(route('tenant.reports.export.csv', [
            'apps' => [$sidbm->id, $akubumdes->id], 'type' => 'income_statement', 'year' => 2026, 'month' => 6,
        ]));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
        $content = $response->streamedContent();

        $this->assertStringNotContainsString('Server Error', $content);
        foreach ([$sidbm->id, $akubumdes->id] as $applicationId) {
            $this->assertStringContainsString('"'.$applicationId.' s.d lalu"', $content);
            $this->assertStringContainsString('"'.$applicationId.' periode ini"', $content);
            $this->assertStringContainsString('"'.$applicationId.' s.d sekarang"', $content);
        }

        // Pola A (grup hierarkis): Penjualan prior 100 / current 250 / ytd 1.000 pada kolomnya masing-masing.
        $this->assertStringContainsString('4.1.01.01;Penjualan;100,00;250,00;1.000,00;0,00;400,00;1.000,00', $content);
        // Pola B (section Akubumdes): Beban Operasional ytd 175 dan baris penutup hasil normalisasi ikut terbawa.
        $this->assertStringContainsString('175,00', $content);
        $this->assertStringContainsString('775,00', $content);
        // Total warisan sumber (skalar) masuk ke kolom `s.d sekarang` milik aplikasinya, rata dengan header.
        $this->assertStringContainsString('TOTAL;"'.$sidbm->id.' revenue";;;1.200,00;;;'."\n", $content);
        $this->assertStringContainsString('TOTAL;"'.$akubumdes->id.' laba_rugi_normalized_after_tax";;;;;;250,00'."\n", $content);
    }

    public function test_tenant_income_statement_pdf_export_contains_numbers(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantOwner()->for($tenant)->create();
        $sidbm = TenantApplication::factory()->create(['tenant_id' => $tenant->id, 'instance_url' => 'https://sidbm.test']);
        $akubumdes = TenantApplication::factory()->create(['tenant_id' => $tenant->id, 'instance_url' => 'https://akubumdes.test']);

        Http::fake([
            'https://sidbm.test/api/v1/holding/reports/income-statement?*' => Http::response($this->groupIncomeStatementPayload(), 200),
            'https://akubumdes.test/api/v1/holding/reports/income-statement?*' => Http::response($this->sectionIncomeStatementPayload(), 200),
        ]);

        $this->actingAs($user)
            ->get(route('tenant.reports.export.pdf', [
                'apps' => [$sidbm->id, $akubumdes->id], 'type' => 'income_statement', 'year' => 2026, 'month' => 6,
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $applications = TenantApplication::query()->whereIn('id', [$sidbm->id, $akubumdes->id])->orderBy('id')->get();
        $html = view('reports.comparative', [
            'title' => 'Laba Rugi',
            'period' => 'Juni 2026',
            'applications' => $applications,
            'report' => app(SubsidiaryReportService::class)->comparative($applications, 'income_statement', 6, 2026),
        ])->render();

        $this->assertStringContainsString('s.d lalu', $html);
        $this->assertStringContainsString('s.d sekarang', $html);
        $this->assertStringContainsString('1.000,00', $html);
        $this->assertStringContainsString('periode ini', $html);
    }

    public function test_income_statement_export_without_triples_keeps_single_column_per_application(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantOwner()->for($tenant)->create();
        $application = TenantApplication::factory()->create(['tenant_id' => $tenant->id]);

        Http::fake(['*/api/v1/holding/reports/*' => Http::response($this->balanceSheetPayload(), 200)]);

        $content = $this->actingAs($user)->get(route('tenant.reports.export.csv', [
            'apps' => [$application->id], 'type' => 'balance_sheet', 'year' => 2026, 'month' => 6,
        ]))->streamedContent();

        $this->assertStringNotContainsString('s.d sekarang', $content);
        $this->assertStringNotContainsString('s.d lalu', $content);
        $this->assertStringContainsString('Kode;Nama;'.$application->id, $content);
    }

    public function test_consolidated_income_statement_sums_triples_per_column(): void
    {
        $tenant = Tenant::factory()->create();
        $sidbm = TenantApplication::factory()->create(['tenant_id' => $tenant->id, 'instance_url' => 'https://sidbm.test']);
        $akubumdes = TenantApplication::factory()->create(['tenant_id' => $tenant->id, 'instance_url' => 'https://akubumdes.test']);

        Http::fake([
            'https://sidbm.test/api/v1/holding/reports/income-statement?*' => Http::response($this->groupIncomeStatementPayload(), 200),
            'https://akubumdes.test/api/v1/holding/reports/income-statement?*' => Http::response($this->sectionIncomeStatementPayload(), 200),
        ]);

        $consolidated = app(ReportBundleService::class)->consolidated(collect([$sidbm, $akubumdes]), 'income_statement', 6, 2026);
        $rows = array_column($consolidated['rows'], 'value', 'key');

        $this->assertSame(
            ['prior' => 100, 'current' => 650, 'ytd' => 2_000],
            $rows['4.1.01.01||Penjualan'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function groupIncomeStatementPayload(): array
    {
        return [
            'status' => 'success',
            'data' => [
                'groups' => [
                    [
                        'level' => 1,
                        'code' => '4',
                        'name' => 'PENDAPATAN',
                        'prior' => 100.0,
                        'current' => 250.0,
                        'ytd' => 1_200.0,
                        'children' => [
                            [
                                'level' => 2,
                                'code' => '4.1.01.01',
                                'name' => 'Penjualan',
                                'prior' => 100.0,
                                'current' => 250.0,
                                'ytd' => 1_000.0,
                            ],
                            [
                                'level' => 2,
                                'code' => '4.1.01.02',
                                'name' => 'Diskon Penjualan',
                                'prior' => 0.0,
                                'current' => 0.0,
                                'ytd' => 200.0,
                            ],
                        ],
                    ],
                ],
                'totals' => ['revenue' => 1_200, 'net_income' => 1_200],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function sectionIncomeStatementPayload(): array
    {
        return [
            'status' => 'success',
            'data' => [
                'title' => 'Laporan Laba Rugi',
                'coa_variant' => 'trading',
                'sections' => [
                    [
                        'label' => 'Penjualan Bersih',
                        'type' => 'revenue',
                        'rows' => [
                            ['code' => '4.1.01.01', 'name' => 'Penjualan', 'account_type' => 'revenue', 'level' => 4, 'prior' => 0.0, 'current' => 400.0, 'ytd' => 1_000.0],
                            ['code' => '4.1.01.02', 'name' => 'Diskon Penjualan', 'account_type' => 'revenue', 'level' => 4, 'prior' => 0.0, 'current' => 0.0, 'ytd' => -50.0],
                        ],
                        'total' => 950.0,
                        'current' => 0.0,
                        'prior' => 0.0,
                    ],
                    [
                        'label' => 'Beban Lainnya',
                        'type' => 'expense',
                        'rows' => [
                            ['code' => '5.2.01.01', 'name' => 'Beban Operasional', 'account_type' => 'expense', 'level' => 4, 'prior' => 0.0, 'current' => 75.0, 'ytd' => 175.0],
                        ],
                        'total' => 175.0,
                        'current' => 0.0,
                        'prior' => 0.0,
                    ],
                    [
                        'label' => 'Pajak',
                        'type' => 'expense',
                        'rows' => [
                            ['code' => '7.4.01.01', 'name' => 'Pajak Badan', 'account_type' => 'expense', 'level' => 4, 'prior' => 0.0, 'current' => 10.0, 'ytd' => 25.0],
                        ],
                        'total' => 25.0,
                        'current' => 0.0,
                        'prior' => 0.0,
                    ],
                ],
                'summary' => [
                    'operating' => ['prior' => 0.0, 'current' => 0.0, 'ytd' => 775.0],
                    'non_operating' => ['prior' => 0.0, 'current' => 0.0, 'ytd' => -175.0],
                    'before_tax' => ['prior' => 0.0, 'current' => 0.0, 'ytd' => 275.0],
                    'tax' => ['prior' => 0.0, 'current' => 0.0, 'ytd' => 25.0],
                    'after_tax' => ['prior' => 0.0, 'current' => 0.0, 'ytd' => 250.0],
                ],
                'totals' => ['revenue' => 1_200, 'expenses' => 200, 'net_income' => 250],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function cashFlowPayload(): array
    {
        return [
            'status' => 'success',
            'data' => [
                'sections' => [
                    ['code' => '1', 'name' => 'OPERASI', 'level' => 1, 'balance' => 610_000, 'children' => []],
                ],
                'totals' => ['cash_change' => 610_000],
            ],
        ];
    }

    private function balanceSheetPayload(): array
    {
        return [
            'status' => 'success',
            'data' => [
                'sections' => [
                    ['code' => '1', 'name' => 'ASET', 'level' => 1, 'balance' => 500, 'children' => [
                        ['code' => '1-1', 'name' => 'Kas', 'level' => 2, 'balance' => 500, 'children' => [
                            ['code' => '1-1-1', 'name' => 'Bank', 'level' => 3, 'balance' => 1000],
                        ]],
                    ]],
                ],
                'totals' => ['assets' => 500, 'liabilities_equity' => 500, 'net_income' => 0],
                'balanced' => true,
            ],
        ];
    }

    private function alternateBalanceSheetPayload(): array
    {
        return [
            'status' => 'success',
            'data' => [
                'sections' => [
                    ['code' => '1', 'name' => 'ASET', 'level' => 1, 'balance' => 250, 'children' => [
                        ['code' => '1-1', 'name' => 'Kas', 'level' => 2, 'balance' => 250, 'children' => [
                            ['code' => '1-1-1', 'name' => 'Bank Cabang', 'level' => 3, 'balance' => 300],
                        ]],
                    ]],
                ],
                'totals' => ['assets' => 250, 'liabilities_equity' => 250, 'net_income' => 0],
                'balanced' => true,
            ],
        ];
    }
}
