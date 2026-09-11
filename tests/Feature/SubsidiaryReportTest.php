<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Application;
use App\Models\ReportCache;
use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Models\User;
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
