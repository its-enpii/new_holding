<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Reports\IncomeStatementNormalizer;
use PHPUnit\Framework\TestCase;

final class IncomeStatementNormalizerTest extends TestCase
{
    private IncomeStatementNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->normalizer = new IncomeStatementNormalizer;
    }

    public function test_hierarchical_groups_keep_triples_and_children(): void
    {
        $model = $this->normalizer->normalize([
            'groups' => [
                [
                    'level' => 1,
                    'code' => '4',
                    'name' => 'PENDAPATAN',
                    'prior' => 10.0,
                    'current' => 20.0,
                    'ytd' => 30.0,
                    'children' => [
                        ['level' => 2, 'code' => '4.1', 'name' => 'Pendapatan Usaha', 'prior' => 10.0, 'current' => 20.0, 'ytd' => 30.0],
                    ],
                ],
            ],
            'totals' => ['revenue' => 30],
        ]);

        $this->assertSame(IncomeStatementNormalizer::SOURCE_GROUPS, $model['meta']['source']);
        $this->assertSame(['revenue' => 30], $model['totals']);
        $this->assertSame(['prior' => 10.0, 'current' => 20.0, 'ytd' => 30.0], $model['groups'][0]['value']);
        $this->assertSame(30.0, $model['groups'][0]['children'][0]['value']['ytd']);
        $this->assertArrayNotHasKey('laba_rugi_normalized', $model['totals']);
    }

    public function test_sections_become_level_one_groups_and_summary_becomes_closing_group(): void
    {
        $model = $this->normalizer->normalize($this->tradingData());

        $this->assertSame(IncomeStatementNormalizer::SOURCE_SECTIONS, $model['meta']['source']);
        $this->assertSame('trading', $model['meta']['variant']);
        $this->assertContains('Penjualan Bersih (YTD)', array_column($model['groups'], 'name'));
        $this->assertSame('Laba (Rugi)', $model['groups'][count($model['groups']) - 1]['name']);

        $closing = array_column($model['groups'][count($model['groups']) - 1]['children'], 'value', 'name');

        $this->assertSame(250.0, $closing['Laba Setelah Pajak']['ytd']);
        $this->assertSame(25.0, $closing['Pajak Penghasilan']['ytd']);
        $this->assertSame(250.0, $model['totals']['laba_rugi_normalized']['after_tax']['ytd']);
    }

    public function test_section_rows_keep_their_account_codes(): void
    {
        $model = $this->normalizer->normalize($this->tradingData());

        $rows = array_column($model['groups'][0]['children'], null, 'code');

        $this->assertArrayHasKey('4.1.01.01', $rows);
        $this->assertSame('Penjualan', $rows['4.1.01.01']['name']);
        $this->assertSame(2, $rows['4.1.01.01']['level']);
        $this->assertSame(1_000.0, $rows['4.1.01.01']['value']['ytd']);
    }

    public function test_legacy_buckets_map_indonesian_columns_to_triples(): void
    {
        $model = $this->normalizer->normalize([
            'pendapatan' => [
                ['kode_akun' => '4.1.01.01', 'nama_akun' => 'Penjualan', 'saldo' => ['s_d_lalu' => 100, 'periode_ini' => 200, 's_d_sekarang' => 300]],
            ],
            'beban' => [
                ['kode_akun' => '5.1.01.01', 'nama_akun' => 'Beban Pokok', 'saldo' => 50],
            ],
        ]);

        $this->assertSame(IncomeStatementNormalizer::SOURCE_NORMALIZED, $model['meta']['source']);
        $this->assertSame(['Pendapatan', 'Beban'], array_column($model['groups'], 'name'));
        $this->assertSame(
            ['prior' => 100.0, 'current' => 200.0, 'ytd' => 300.0],
            $model['groups'][0]['children'][0]['value'],
        );
        $this->assertSame(50.0, $model['groups'][1]['children'][0]['value']['ytd']);
        $this->assertSame(0.0, $model['groups'][1]['children'][0]['value']['prior']);
    }

    public function test_unknown_format_is_flagged_without_inventing_rows(): void
    {
        $model = $this->normalizer->normalize(['foo' => 'bar', 'totals' => ['net_income' => 5]]);

        $this->assertSame(['format_tidak_dikenal'], $model['meta']['warnings']);
        $this->assertSame([], $model['groups']);
        $this->assertSame(['net_income' => 5], $model['totals']);
    }

    public function test_numeric_summary_is_normalized_without_losing_inherited_totals(): void
    {
        $model = $this->normalizer->normalize([
            'sections' => [
                ['label' => 'Pendapatan', 'rows' => [['code' => '4.1', 'name' => 'Penjualan', 'prior' => 0, 'current' => 5, 'ytd' => 5]], 'total' => 5],
            ],
            'summary' => ['before_tax' => 5, 'tax' => 1, 'after_tax' => 4],
            'totals' => ['revenue' => 5],
        ]);

        $this->assertSame(5, $model['totals']['revenue'], 'Total warisan sumber tidak boleh hilang.');
        $this->assertSame(4.0, $model['totals']['laba_rugi_normalized']['after_tax']);
    }

    /**
     * @return array<string, mixed>
     */
    private function tradingData(): array
    {
        return [
            'coa_variant' => 'trading',
            'sections' => [
                [
                    'label' => 'Penjualan Bersih',
                    'type' => 'revenue',
                    'rows' => [
                        ['code' => '4.1.01.01', 'name' => 'Penjualan', 'account_type' => 'revenue', 'prior' => 0.0, 'current' => 400.0, 'ytd' => 1_000.0],
                        ['code' => '4.1.01.02', 'name' => 'Diskon Penjualan', 'account_type' => 'revenue', 'prior' => 0.0, 'current' => -10.0, 'ytd' => -50.0],
                    ],
                    'total' => 950.0,
                    'current' => 0.0,
                    'prior' => 0.0,
                ],
                [
                    'label' => 'Harga Pokok Penjualan',
                    'type' => 'expense',
                    'rows' => [
                        ['code' => '5.1.01.01', 'name' => 'Beban Pokok', 'account_type' => 'expense', 'prior' => 0.0, 'current' => 200.0, 'ytd' => 500.0],
                    ],
                    'total' => 500.0,
                    'current' => 0.0,
                    'prior' => 0.0,
                ],
                [
                    'label' => 'Beban Lainnya',
                    'type' => 'expense',
                    'rows' => [
                        ['code' => '5.2.01.01', 'name' => 'Beban Operasional', 'account_type' => 'expense', 'prior' => 0.0, 'current' => 75.0, 'ytd' => 175.0],
                    ],
                    'total' => 175.0,
                    'current' => 0.0,
                    'prior' => 0.0,
                ],
                [
                    'label' => 'Pajak',
                    'type' => 'expense',
                    'rows' => [
                        ['code' => '7.4.01.01', 'name' => 'Pajak Badan', 'account_type' => 'expense', 'prior' => 0.0, 'current' => 10.0, 'ytd' => 25.0],
                    ],
                    'total' => 25.0,
                    'current' => 0.0,
                    'prior' => 0.0,
                ],
            ],
            'summary' => [
                'operating' => ['prior' => 0.0, 'current' => 0.0, 'ytd' => 450.0],
                'non_operating' => ['prior' => 0.0, 'current' => 0.0, 'ytd' => -175.0],
                'before_tax' => ['prior' => 0.0, 'current' => 0.0, 'ytd' => 275.0],
                'tax' => ['prior' => 0.0, 'current' => 0.0, 'ytd' => 25.0],
                'after_tax' => ['prior' => 0.0, 'current' => 0.0, 'ytd' => 250.0],
            ],
        ];
    }
}
