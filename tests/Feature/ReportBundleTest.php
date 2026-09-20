<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Models\User;
use App\Services\ReportBundleService;
use App\Services\SubsidiaryReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use ZipArchive;

final class ReportBundleTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_owner_can_download_combined_bundle(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantOwner()->for($tenant)->create();
        $applications = $this->applications($tenant, [
            'unit-satu' => ['label' => 'Unit Sukamaju'],
            'unit-dua' => ['label' => 'Unit Mekarwangi'],
        ]);

        Http::fake(['*/api/v1/holding/reports/*' => Http::response($this->payload(), 200)]);

        $this->actingAs($user)->get($this->bundleUrl('tenant.reports.bundle', $applications, ['month' => 6]))
            ->assertOk()
            ->assertHeader('content-type', 'application/zip')
            ->assertDownload('bundle-laporan-gabungan-'.$tenant->slug.'-2026-06.zip');

        $this->assertNotNull(ActivityLog::query()
            ->where('action', 'export_bundle_report')
            ->where('user_id', $user->id)
            ->where('metadata->mode', 'gabungan')
            ->where('metadata->format', 'zip')
            ->first());
    }

    public function test_tenant_owner_can_download_consolidated_bundle(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantOwner()->for($tenant)->create();
        $applications = $this->applications($tenant, ['unit-satu' => []]);

        Http::fake(['*/api/v1/holding/reports/*' => Http::response($this->payload(), 200)]);

        $this->actingAs($user)->get($this->bundleUrl('tenant.reports.bundle', $applications, mode: 'konsolidasi'))
            ->assertOk()
            ->assertHeader('content-type', 'application/zip')
            ->assertDownload('bundle-laporan-konsolidasi-'.$tenant->slug.'-2026-ALL.zip');
    }

    public function test_bundle_archive_contains_five_pdfs_and_one_readme_manifest(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantOwner()->for($tenant)->create();
        $applications = $this->applications($tenant, ['unit-satu' => [], 'unit-dua' => []]);

        Http::fake(['*/api/v1/holding/reports/*' => Http::response($this->payload(), 200)]);

        $entries = $this->entries($this->actingAs($user)->get($this->bundleUrl('tenant.reports.bundle', $applications, ['month' => 6])));

        $this->assertSame([
            '01_Neraca_gabungan_2026-06.pdf',
            '02_Laba_Rugi_gabungan_2026-06.pdf',
            '03_Arus_Kas_gabungan_2026-06.pdf',
            '04_Perubahan_Ekuitas_gabungan_2026-06.pdf',
            '05_CALK_gabungan_2026-06.pdf',
            'README_Manifest.txt',
        ], $entries['names']);

        $this->assertCount(5, array_filter($entries['names'], fn (string $name): bool => str_ends_with($name, '.pdf')));
    }

    public function test_bundle_manifest_documents_holding_period_mode_and_unit_usaha(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'BUMDesma Maju Bersama']);
        $user = User::factory()->tenantOwner()->for($tenant)->create();
        $applications = $this->applications($tenant, ['unit-satu' => ['label' => 'Unit Usaha Sukamaju']]);

        Http::fake(['*/api/v1/holding/reports/*' => Http::response($this->payload(), 200)]);

        $manifest = $this->entries($this->actingAs($user)->get($this->bundleUrl('tenant.reports.bundle', $applications)))['manifest'];

        $this->assertStringContainsString('BUMDesma Maju Bersama', $manifest);
        $this->assertStringContainsString('Tahunan 2026', $manifest);
        $this->assertStringContainsString('GABUNGAN (komparatif antar unit usaha)', $manifest);
        $this->assertStringContainsString('Unit Usaha Sukamaju', $manifest);
        $this->assertStringContainsString('README_Manifest.txt (berkas ini)', $manifest);
        $this->assertStringContainsString('bukan angka konsolidasi', $manifest);
    }

    public function test_consolidated_mode_aggregates_units_and_eliminates_internal_balances(): void
    {
        $tenant = Tenant::factory()->create();
        $applications = $this->applications($tenant, ['unit-satu' => [], 'unit-dua' => []]);

        Http::fake([
            'https://unit-satu.test/*' => Http::response($this->payload([
                ['code' => '1-1300', 'name' => 'Piutang Antar Unit Usaha', 'level' => 2, 'balance' => 150],
            ], 500), 200),
            'https://unit-dua.test/*' => Http::response($this->payload([
                ['code' => '2-1300', 'name' => 'Utang Antar Unit Usaha', 'level' => 2, 'balance' => 150],
            ], 250), 200),
        ]);

        $consolidated = app(ReportBundleService::class)->consolidated($applications, 'balance_sheet', null, 2026);
        $values = array_column($consolidated['rows'], 'value', 'code');

        $this->assertSame(600, $values['1'], 'Total aset ikut terselizasi bersama akun induknya.');
        $this->assertSame(0, $values['1-1300']);
        $this->assertSame(0, $values['2-1300']);
        $this->assertSame(600, $consolidated['totals']['assets']);
        $this->assertSame(600, $consolidated['totals']['liabilities_equity']);
        $this->assertCount(2, $consolidated['eliminations']);
        $this->assertSame(150, $consolidated['eliminations'][0]['amount']);
        $this->assertSame([], $consolidated['offline']);
    }

    public function test_combined_mode_keeps_internal_balances_without_elimination(): void
    {
        $tenant = Tenant::factory()->create();
        $applications = $this->applications($tenant, ['unit-satu' => [], 'unit-dua' => []]);

        Http::fake([
            'https://unit-satu.test/*' => Http::response($this->payload([
                ['code' => '1-1300', 'name' => 'Piutang Antar Unit Usaha', 'level' => 2, 'balance' => 150],
            ], 500), 200),
            'https://unit-dua.test/*' => Http::response($this->payload([
                ['code' => '2-1300', 'name' => 'Utang Antar Unit Usaha', 'level' => 2, 'balance' => 150],
            ], 250), 200),
        ]);

        $comparative = app(SubsidiaryReportService::class)->comparative($applications, 'balance_sheet', null, 2026);

        $this->assertSame(150, array_column($comparative['rows'], 'values', 'code')['1-1300'][$applications[0]->id]);
        $this->assertSame(150, array_column($comparative['rows'], 'values', 'code')['2-1300'][$applications[1]->id]);
    }

    public function test_offline_unit_usaha_is_reported_and_contributes_nothing(): void
    {
        $tenant = Tenant::factory()->create();
        $applications = $this->applications($tenant, ['unit-satu' => [], 'unit-dua' => []]);

        Http::fake([
            'https://unit-satu.test/*' => Http::response($this->payload(), 200),
            'https://unit-dua.test/*' => Http::response('boom', 500),
        ]);

        $consolidated = app(ReportBundleService::class)->consolidated($applications, 'balance_sheet', null, 2026);

        $this->assertSame([$applications[1]->id], $consolidated['offline']);
        $this->assertSame(500, array_column($consolidated['rows'], 'value', 'code')['1']);
    }

    public function test_tenant_cannot_download_bundle_of_another_tenant(): void
    {
        $user = User::factory()->tenantOwner()->for(Tenant::factory()->create())->create();
        $foreignApplications = $this->applications(Tenant::factory()->create(), ['unit-satu' => []]);

        $this->actingAs($user)->get($this->bundleUrl('tenant.reports.bundle', $foreignApplications))->assertForbidden();

        $this->assertSame(0, ActivityLog::query()->where('action', 'export_bundle_report')->count());
    }

    public function test_guest_is_redirected_from_tenant_bundle_route(): void
    {
        $applications = $this->applications(Tenant::factory()->create(), ['unit-satu' => []]);

        $this->get($this->bundleUrl('tenant.reports.bundle', $applications))->assertRedirect(route('login'));
    }

    public function test_invalid_bundle_mode_is_rejected(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantOwner()->for($tenant)->create();
        $applications = $this->applications($tenant, ['unit-satu' => []]);

        $this->actingAs($user)->get($this->bundleUrl('tenant.reports.bundle', $applications, mode: 'konsolidasi-palsu'))
            ->assertSessionHasErrors('mode');
    }

    public function test_superadmin_can_download_tenant_bundle_via_admin_route(): void
    {
        $tenant = Tenant::factory()->create();
        $superadmin = User::factory()->superadmin()->create();
        $applications = $this->applications($tenant, ['unit-satu' => []]);

        Http::fake(['*/api/v1/holding/reports/*' => Http::response($this->payload(), 200)]);

        $this->actingAs($superadmin)->get($this->bundleUrl('admin.reports.bundle', $applications, [
            'month' => 3,
            'tenant_id' => $tenant->id,
        ], mode: 'konsolidasi'))
            ->assertOk()
            ->assertHeader('content-type', 'application/zip')
            ->assertDownload('bundle-laporan-konsolidasi-'.$tenant->slug.'-2026-03.zip');

        $this->assertNotNull(ActivityLog::query()
            ->where('action', 'export_bundle_report')
            ->where('user_id', $superadmin->id)
            ->where('subject_type', Tenant::class)
            ->where('subject_id', $tenant->id)
            ->where('metadata->mode', 'konsolidasi')
            ->first());
    }

    public function test_admin_bundle_route_rejects_apps_outside_the_selected_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $superadmin = User::factory()->superadmin()->create();
        $foreignApplications = $this->applications(Tenant::factory()->create(), ['unit-satu' => []]);

        $this->actingAs($superadmin)
            ->get($this->bundleUrl('admin.reports.bundle', $foreignApplications, ['tenant_id' => $tenant->id]))
            ->assertStatus(422);
    }

    public function test_tenant_owner_cannot_reach_admin_bundle_route(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantOwner()->for($tenant)->create();
        $applications = $this->applications($tenant, ['unit-satu' => []]);

        $this->actingAs($user)->get($this->bundleUrl('admin.reports.bundle', $applications, ['tenant_id' => $tenant->id]))
            ->assertForbidden();
    }

    /**
     * @param  array<string, array<string, mixed>>  $units
     * @return Collection<int, TenantApplication>
     */
    private function applications(Tenant $tenant, array $units): Collection
    {
        return collect($units)
            ->map(fn (array $attributes, string $host): TenantApplication => TenantApplication::factory()
                ->for($tenant)
                ->create([...$attributes, 'instance_url' => 'https://'.$host.'.test'])
            )
            ->values();
    }

    public function test_consolidated_income_statement_eliminates_only_the_sent_column(): void
    {
        $tenant = Tenant::factory()->create();
        $applications = $this->applications($tenant, ['unit-satu' => [], 'unit-dua' => []]);

        Http::fake([
            'https://unit-satu.test/*' => Http::response($this->incomeStatementPayload(
                [['code' => '4-1300', 'name' => 'Pendapatan Antar Unit Usaha', 'prior' => 0, 'current' => 100, 'ytd' => 200]],
                1_000,
            ), 200),
            'https://unit-dua.test/*' => Http::response($this->incomeStatementPayload([], 800), 200),
        ]);

        $consolidated = app(ReportBundleService::class)->consolidated($applications, 'income_statement', null, 2026);
        $values = array_column($consolidated['rows'], 'value', 'code');

        $this->assertSame(0, $values['4-1300']['ytd'], 'Pendapatan internal tereliminasi penuh pada kolom ytd.');
        $this->assertSame(100, $values['4-1300']['current'], 'Kolom yang tidak punya pasangan debit tidak ikut dikurangi.');
        $this->assertSame(200, $values['5-1300']['ytd']);
        $this->assertSame(1_600, $values['4']['ytd'], 'Akun pendapatan induk ikut terselizasi.');
        $this->assertSame(1_600, $consolidated['totals']['revenue']);
        $this->assertSame(200, $consolidated['totals']['expenses']);
        $this->assertSame(200, $consolidated['eliminations'][0]['amount']);
    }

    /**
     * @param  list<array<string, mixed>>  $internalRevenue
     * @return array<string, mixed>
     */
    private function incomeStatementPayload(array $internalRevenue, int|float $revenue): array
    {
        return [
            'status' => 'success',
            'data' => [
                'groups' => [
                    [
                        'level' => 1,
                        'code' => '4',
                        'name' => 'PENDAPATAN',
                        'prior' => 0,
                        'current' => $revenue,
                        'ytd' => $revenue,
                        'children' => [
                            ['level' => 2, 'code' => '4-1000', 'name' => 'Pendapatan Usaha', 'prior' => 0, 'current' => $revenue, 'ytd' => $revenue],
                            ...$internalRevenue,
                        ],
                    ],
                    [
                        'level' => 1,
                        'code' => '5',
                        'name' => 'BEBAN',
                        'prior' => 0,
                        'current' => 200,
                        'ytd' => 200,
                        'children' => [
                            ['level' => 2, 'code' => '5-1300', 'name' => 'Beban Antar Unit Usaha', 'prior' => 0, 'current' => 0, 'ytd' => 200],
                        ],
                    ],
                ],
                'totals' => ['revenue' => $revenue, 'expenses' => 200, 'net_income' => $revenue - 200],
            ],
        ];
    }

    /**
     * @param  Collection<int, TenantApplication>  $applications
     * @param  array<string, mixed>  $extra
     */
    private function bundleUrl(string $route, Collection $applications, array $extra = [], string $mode = 'gabungan'): string
    {
        return route($route, [
            'apps' => $applications->pluck('id')->all(),
            'year' => 2026,
            'mode' => $mode,
            ...$extra,
        ]);
    }

    /**
     * @return array{names: list<string>, manifest: string}
     */
    private function entries(TestResponse $response): array
    {
        $file = $response->getFile();
        $this->assertNotNull($file, 'Berkas arsip sementara hilang sebelum dikirim.');

        $archive = new ZipArchive;
        $this->assertTrue($archive->open($file->getPathname()) === true);

        $names = [];

        for ($index = 0; $index < $archive->numFiles; $index++) {
            $names[] = (string) $archive->getNameIndex($index);
        }

        $manifest = (string) $archive->getFromName('README_Manifest.txt');
        $this->assertStringStartsWith('%PDF-', (string) $archive->getFromName($names[0]));
        $archive->close();
        @unlink($file->getPathname());

        return ['names' => $names, 'manifest' => $manifest];
    }

    /**
     * @param  list<array<string, mixed>>  $extraAccounts
     */
    private function payload(array $extraAccounts = [], int $assets = 500): array
    {
        return [
            'status' => 'success',
            'data' => [
                'sections' => [
                    ['code' => '1', 'name' => 'ASET', 'level' => 1, 'balance' => $assets, 'children' => [
                        ['code' => '1-1000', 'name' => 'Kas dan Setara Kas', 'level' => 2, 'balance' => $assets, 'children' => []],
                    ]],
                    ['code' => '2', 'name' => 'KEWAJIBAN', 'level' => 1, 'balance' => 100, 'children' => [
                        ['code' => '2-1000', 'name' => 'Utang Bank', 'level' => 2, 'balance' => 100, 'children' => []],
                    ]],
                    ['code' => '3', 'name' => 'MODAL', 'level' => 1, 'balance' => $assets - 100, 'children' => [
                        ['code' => '3-1000', 'name' => 'Modal Disetor', 'level' => 2, 'balance' => $assets - 100, 'children' => []],
                    ]],
                    ...array_map(fn (array $account): array => [...$account, 'children' => []], $extraAccounts),
                ],
                'totals' => ['assets' => $assets, 'liabilities_equity' => $assets, 'net_income' => 0],
                'balanced' => true,
            ],
        ];
    }
}
