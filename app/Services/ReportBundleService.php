<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TenantApplication;
use App\Support\Reports\IncomeStatementNormalizer;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPDF;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

/**
 * Merakit bundle ZIP berisi lima laporan keuangan level holding.
 *
 * Mode `gabungan` menyajikan tiap unit usaha pada kolom berdampingan tanpa
 * eliminasi transaksi internal (combined statements) ditambah kolom Total
 * Gabungan. Mode `konsolidasi` mengagregasi seluruh unit menjadi satu kolom
 * entitas ekonomi tunggal setelah saldo dan transaksi resiprokal antar unit
 * dieliminasi.
 */
final class ReportBundleService
{
    public const MODE_GABUNGAN = 'gabungan';

    public const MODE_KONSOLIDASI = 'konsolidasi';

    /** @var list<string> */
    public const ACCEPTED_MODES = [self::MODE_GABUNGAN, self::MODE_KONSOLIDASI, 'comparative', 'consolidated'];

    /** @var list<string> */
    public const REPORT_TYPES = ['balance_sheet', 'income_statement', 'cash_flow', 'equity_changes', 'calk'];

    /** Kolom nilai berjalan pada triple kontrak v1. */
    private const YTD_COLUMN = 'ytd';

    /** Penanda sel tunggal untuk nilai skalar (laporan non-berkala). */
    private const SCALAR_COLUMN = '_value';

    /**
     * Urutan pelaporan menentukan penomoran berkas di dalam arsip ZIP.
     *
     * @var array<string, array{sequence: string, filename: string, label: string}>
     */
    private const REPORTS = [
        'balance_sheet' => ['sequence' => '01', 'filename' => 'Neraca', 'label' => 'Neraca'],
        'income_statement' => ['sequence' => '02', 'filename' => 'Laba_Rugi', 'label' => 'Laba Rugi'],
        'cash_flow' => ['sequence' => '03', 'filename' => 'Arus_Kas', 'label' => 'Arus Kas'],
        'equity_changes' => ['sequence' => '04', 'filename' => 'Perubahan_Ekuitas', 'label' => 'Perubahan Ekuitas'],
        'calk' => ['sequence' => '05', 'filename' => 'CALK', 'label' => 'CALK'],
    ];

    /** @var array<string, string> */
    private const TOTAL_LABELS = [
        'assets' => 'Total Aset',
        'liabilities' => 'Total Kewajiban',
        'liabilities_equity' => 'Total Kewajiban dan Ekuitas',
        'equity' => 'Total Ekuitas',
        'revenue' => 'Total Pendapatan',
        'expenses' => 'Total Beban',
        'net_income' => 'Laba Bersih',
        'cash_change' => 'Kenaikan (Penurunan) Kas Bersih',
        'closing_cash' => 'Kas Akhir Periode',
        'closing_equity' => 'Ekuitas Akhir',
        'participants' => 'Jumlah Pihak Terkait',
        'laba_rugi_normalized_before_tax' => 'Laba Sebelum Pajak',
        'laba_rugi_normalized_tax' => 'Pajak Penghasilan',
        'laba_rugi_normalized_after_tax' => 'Laba Setelah Pajak',
    ];

    /**
     * Penanda bahwa akun tersebut benar-benar resiprokal antar unit dalam satu grup holding.
     */
    private const RECIPROCAL_PATTERN = '/\b(holding|induk|antar[\s-]?unit|antar[\s-]?perusahaan|antar[\s-]?entitas|internal|afiliasi|anak[\s-]?usaha|grup|group)\b/i';

    /**
     * Pasangan kategori yang saling dihapukan pada laporan konsolidasi.
     *
     * `totals` memetakan kunci total agregat yang ikut dikurangi, per jenis laporan.
     *
     * @var array<string, array{debit: string, credit: string, label: string, totals: array<string, list<string>>}>
     */
    private const ELIMINATION_PAIRS = [
        'receivable_payable' => [
            'debit' => 'piutang|investasi',
            'credit' => 'utang|kewajiban|pinjaman',
            'label' => 'saldo piutang-utang antar unit usaha',
            'totals' => [
                'balance_sheet' => ['assets', 'liabilities', 'liabilities_equity'],
                'cash_flow' => ['cash_change', 'closing_cash'],
            ],
        ],
        'investment_capital' => [
            'debit' => 'penyertaan',
            'credit' => 'modal|saham',
            'label' => 'penyertaan holding terhadap ekuitas anak usaha',
            'totals' => [
                'balance_sheet' => ['assets', 'equity', 'liabilities_equity'],
                'equity_changes' => ['closing_equity'],
            ],
        ],
        'expense_revenue' => [
            'debit' => 'beban|biaya|pembelian',
            'credit' => 'pendapatan|penjualan|omzet',
            'label' => 'transaksi jual-beli internal antar unit usaha',
            'totals' => [
                'income_statement' => ['revenue', 'expenses'],
            ],
        ],
    ];

    public function __construct(
        private readonly SubsidiaryReportService $reportService,
    ) {}

    /**
     * @param  Collection<int, TenantApplication>  $applications
     */
    public function download(Collection $applications, int $year, ?int $month = null, string $mode = self::MODE_GABUNGAN, bool $force = false): BinaryFileResponse
    {
        $bundle = $this->build($applications, $year, $month, $mode, $force);

        return response()
            ->download($bundle['path'], $bundle['filename'], ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend(true);
    }

    /**
     * @param  Collection<int, TenantApplication>  $applications
     * @return array{path: string, filename: string, files: list<string>, manifest: string}
     */
    public function build(Collection $applications, int $year, ?int $month = null, string $mode = self::MODE_GABUNGAN, bool $force = false): array
    {
        $mode = $this->normalizeMode($mode);
        $period = $this->periodSlug($year, $month);
        $files = [];
        $summaries = [];

        $archive = new ZipArchive;
        $path = $this->temporaryArchivePath($period);
        $status = $archive->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($status !== true) {
            throw new RuntimeException('Gagal menyiapkan arsip laporan keuangan holding.');
        }

        try {
            foreach (self::REPORTS as $reportType => $definition) {
                $comparative = $this->reportService->comparative($applications, $reportType, $month, $year, $force);
                $consolidated = $mode === self::MODE_KONSOLIDASI
                    ? $this->consolidate($applications, $comparative, $reportType)
                    : null;
                $filename = sprintf('%s_%s_%s_%s.pdf', $definition['sequence'], $definition['filename'], $mode, $period);

                $archive->addFromString($filename, $this->pdf($applications, $comparative, $consolidated, $reportType, $year, $month, $mode)->output());

                $files[] = $filename;
                $summaries[] = [
                    'label' => $definition['label'],
                    'filename' => $filename,
                    'rows' => count($consolidated['rows'] ?? $comparative['rows']),
                    'eliminations' => intdiv(count($consolidated['eliminations'] ?? []), 2),
                ];
            }

            $manifest = $this->manifest($applications, $year, $month, $mode, $summaries);
            $archive->addFromString($this->manifestName(), $manifest);
            $archive->close();
        } catch (\Throwable $exception) {
            if ($archive->status === ZipArchive::ER_OK) {
                @$archive->close();
            }

            @unlink($path);

            throw $exception;
        }

        return [
            'path' => $path,
            'filename' => $this->archiveName($applications, $mode, $period),
            'files' => $files,
            'manifest' => $manifest,
        ];
    }

    /**
     * Laporan konsolidasi satu kolom: agregat seluruh unit usaha dikurangi eliminasi internal.
     *
     * @param  Collection<int, TenantApplication>  $applications
     * @return array{rows: list<array{level: int, code: string, name: string, key: string, value: array<string, int|float>|int|float|null}>, totals: array<string, int|float|null>, eliminations: list<array{side: string, code: string, name: string, amount: int|float}>, offline: list<int>}
     */
    public function consolidated(Collection $applications, string $reportType, ?int $month, int $year, bool $force = false): array
    {
        return $this->consolidate(
            $applications,
            $this->reportService->comparative($applications, $reportType, $month, $year, $force),
            $reportType,
        );
    }

    /**
     * @param  Collection<int, TenantApplication>  $applications
     * @param  array{rows: list<array{level: int, code: string, name: string, key: string, values: array<int, mixed>}>, totals: array<int, array<string, int|float|null>|null>, appStates: array<int, string>}  $comparative
     * @return array{rows: list<array{level: int, code: string, name: string, key: string, value: array<string, int|float>|int|float|null}>, totals: array<string, int|float|null>, eliminations: list<array{side: string, code: string, name: string, amount: int|float}>, offline: list<int>}
     */
    private function consolidate(Collection $applications, array $comparative, string $reportType): array
    {
        $rows = [];

        foreach ($comparative['rows'] as $row) {
            $key = $row['key'];
            $rows[$key] ??= ['level' => $row['level'], 'code' => $row['code'], 'name' => $row['name'], 'key' => $key, 'value' => null];

            $value = $this->aggregate($applications, $row['values']);

            if ($value !== null) {
                $rows[$key]['value'] = $value;
            }
        }

        $totals = [];

        foreach ($comparative['totals'] as $applicationTotals) {
            foreach ((array) $applicationTotals as $totalKey => $totalValue) {
                if (is_numeric($totalValue)) {
                    $totals[$totalKey] = ($totals[$totalKey] ?? 0) + $totalValue + 0;
                }
            }
        }

        $eliminations = $this->eliminateInternalBalances($rows, $totals, $reportType);

        return [
            'rows' => array_values($rows),
            'totals' => $totals,
            'eliminations' => $eliminations,
            'offline' => $applications
                ->filter(fn (TenantApplication $application): bool => ($comparative['appStates'][$application->id] ?? 'offline') === 'offline')
                ->pluck('id')
                ->all(),
        ];
    }

    /**
     * Menghapus saldo dan transaksi resiprokal antar unit dari angka konsolidasi.
     *
     * Tiap pasangan kategori (piutang-utang, penyertaan-modal, beban-pendapatan internal)
     * saling dihapukan sebesar nilai terkecil di antara kedua sisi, dikurangkan proporsional
     * pada akun rinci beserta akun induknya, lalu tercermin pada total agregatnya.
     *
     * @param  array<string, array{level: int, code: string, name: string, key: string, value: int|float|null}>  $rows
     * @param  array<string, int|float|null>  $totals
     * @return list<array{side: string, code: string, name: string, amount: int|float}>
     */
    private function eliminateInternalBalances(array &$rows, array &$totals, string $reportType): array
    {
        $internal = [];

        foreach ($rows as $key => $row) {
            $category = $this->internalCategory($row);

            if ($category !== null) {
                $internal[$key] = [...$row, ...$category];
            }
        }

        $eliminations = [];

        foreach (self::ELIMINATION_PAIRS as $pair => $definition) {
            $affected = $this->leafRows(array_filter($internal, fn (array $row): bool => $row['pair'] === $pair));
            $debits = array_filter($affected, fn (array $row): bool => $row['side'] === 'debit');
            $credits = array_filter($affected, fn (array $row): bool => $row['side'] === 'credit');
            $amounts = $this->eliminationAmounts($debits, $credits);
            $amount = $this->basisAmount($amounts);

            if ($amount === null || $amount <= 0) {
                continue;
            }

            $this->reduceRows($rows, $debits, $amounts);
            $this->reduceRows($rows, $credits, $amounts);
            $this->rollUpToAncestors($rows, $internal, array_merge(array_keys($debits), array_keys($credits)), $amounts);

            foreach ($definition['totals'][$reportType] ?? [] as $totalKey) {
                if (array_key_exists($totalKey, $totals)) {
                    $totals[$totalKey] = $this->numeric($totals[$totalKey]) - $amount;
                }
            }

            $eliminations[] = ['side' => 'debit', 'code' => 'ELIM', 'name' => 'Eliminasi '.$definition['label'].' (sisi debit)', 'amount' => $amount];
            $eliminations[] = ['side' => 'credit', 'code' => 'ELIM', 'name' => 'Eliminasi '.$definition['label'].' (sisi kredit)', 'amount' => $amount];
        }

        return $eliminations;
    }

    /**
     * Menandai akun internal beserta sisi debit/kredit dan kategori pasangannya.
     *
     * @param  array{code: string, name: string, value: int|float|null}  $row
     * @return array{side: string, pair: string}|null
     */
    private function internalCategory(array $row): ?array
    {
        $basis = $this->scalarOf($row['value']);

        if ($basis === null || $basis === 0) {
            return null;
        }

        $label = $row['code'].' '.$row['name'];

        if (preg_match(self::RECIPROCAL_PATTERN, $label) !== 1) {
            return null;
        }

        foreach (self::ELIMINATION_PAIRS as $pair => $definition) {
            if (preg_match('/\b('.$definition['credit'].')\b/i', $label) === 1) {
                return ['side' => 'credit', 'pair' => $pair];
            }
        }

        foreach (self::ELIMINATION_PAIRS as $pair => $definition) {
            if (preg_match('/\b('.$definition['debit'].')\b/i', $label) === 1) {
                return ['side' => 'debit', 'pair' => $pair];
            }
        }

        return null;
    }

    /**
     * Menyisakan akun internal paling rinci agar satu eliminasi tidak dihitung dua kali.
     *
     * @param  array<string, array{level: int, code: string, name: string, key: string, value: int|float|null, side: string, pair: string}>  $rows
     * @return array<string, array{level: int, code: string, name: string, key: string, value: int|float|null, side: string, pair: string}>
     */
    private function leafRows(array $rows): array
    {
        return array_filter($rows, function (array $row) use ($rows): bool {
            foreach ($rows as $other) {
                if ($other['key'] !== $row['key'] && $this->isAncestor($row, $other)) {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * @param  array{code: string, level: int}  $ancestor
     * @param  array{code: string, level: int}  $descendant
     */
    private function isAncestor(array $ancestor, array $descendant): bool
    {
        return $ancestor['code'] !== ''
            && $descendant['level'] > $ancestor['level']
            && str_starts_with($descendant['code'].'-', $ancestor['code'].'-');
    }

    /**
     * Menyesuaikan akun induk dari baris yang dieliminasi agar sub-total tetap konsisten.
     *
     * @param  array<string, array{level: int, code: string, name: string, key: string, value: mixed}>  $rows
     * @param  array<string, array{level: int, code: string, name: string, key: string, value: mixed, side: string, pair: string}>  $internal
     * @param  list<string>  $eliminatedKeys
     * @param  array<string, int|float>  $amounts
     */
    private function rollUpToAncestors(array &$rows, array $internal, array $eliminatedKeys, array $amounts): void
    {
        foreach ($eliminatedKeys as $eliminatedKey) {
            foreach (array_keys($amounts) as $column) {
                $delta = $this->numeric($this->cell($internal[$eliminatedKey]['value'], $column) ?? 0)
                    - $this->numeric($this->cell($rows[$eliminatedKey]['value'], $column) ?? 0);

                if ($delta === 0) {
                    continue;
                }

                foreach ($rows as $key => $row) {
                    $needsAdjustment = $key !== $eliminatedKey
                        && ! in_array($key, $eliminatedKeys, true)
                        && ! array_key_exists($key, $internal)
                        && $this->isAncestor($row, $internal[$eliminatedKey]);

                    if ($needsAdjustment && array_key_exists($column, $this->cells($row['value']))) {
                        $rows[$key]['value'] = $this->putCell(
                            $row['value'],
                            $column,
                            $this->numeric($this->cell($row['value'], $column) ?? 0) - $delta,
                        );
                    }
                }
            }
        }
    }

    /**
     * Mengurangi nilai akun terdampak secara proporsional sampai total eliminasi tercapai,
     * per kolom nilai (`prior`/`current`/`ytd` maupun skalar).
     *
     * @param  array<string, array{level: int, code: string, name: string, key: string, value: mixed}>  $rows
     * @param  array<string, array{level: int, code: string, name: string, key: string, value: mixed, side: string, pair: string}>  $affected
     * @param  array<string, int|float>  $amounts
     */
    private function reduceRows(array &$rows, array $affected, array $amounts): void
    {
        foreach ($amounts as $column => $elimination) {
            $affectedCells = array_filter($affected, fn (array $row): bool => array_key_exists($column, $this->cells($row['value'])));
            $base = $this->absoluteSum($affectedCells, $column);

            if ($base <= 0) {
                continue;
            }

            $keys = array_keys($affectedCells);
            $lastKey = $keys[count($keys) - 1];
            $allocated = 0;

            foreach ($affectedCells as $key => $row) {
                $value = $this->cell($row['value'], $column) ?? 0;
                $magnitude = abs($value);
                $share = $key === $lastKey
                    ? min($magnitude, max($elimination - $allocated, 0))
                    : min($magnitude, round($elimination * $magnitude / $base, 2));

                $rows[$key]['value'] = $this->putCell(
                    $row['value'],
                    $column,
                    $this->numeric($value) - ($value <=> 0) * $share,
                );
                $allocated += $share;
            }
        }
    }

    /**
     * Menulis kembali satu sel tanpa mengubah bentuk nilai baris (triple tetap triple).
     */
    private function putCell(mixed $value, string $column, int|float $written): mixed
    {
        if ($column === self::SCALAR_COLUMN) {
            return $written;
        }

        $cells = is_array($value) ? $value : [];
        $cells[$column] = $written;

        return $cells;
    }

    /**
     * @param  Collection<int, TenantApplication>  $applications
     * @param  array<int, mixed>  $values
     * @return array<string, int|float>|int|float|null
     */
    private function aggregate(Collection $applications, array $values): array|int|float|null
    {
        $sum = null;

        foreach ($applications as $application) {
            $value = $values[$application->id] ?? null;

            if (is_array($value)) {
                $sum = $this->addNumeric($sum, $value);

                continue;
            }

            if (is_numeric($value)) {
                $sum = is_array($sum)
                    ? $this->addNumeric($sum, ['ytd' => $value + 0])
                    : $this->numeric(($sum ?? 0) + $value + 0);
            }
        }

        return $sum;
    }

    /**
     * Menjumlah nilai kontrak v1 per kolom (`prior`/`current`/`ytd`) tanpa mengarang
     * kolom yang tidak dikirim sumber. Skalar yang bercampur dengan triple dianggap
     * nilai berjalan (ytd) agar agregat tetap terbaca.
     *
     * @param  array<string, int|float>|int|float|null  $left
     * @param  array<string, mixed>  $right
     * @return array<string, int|float>
     */
    private function addNumeric(array|int|float|null $left, array $right): array
    {
        $columns = is_array($left) ? $left : [];

        if (! is_array($left) && is_numeric($left)) {
            $columns['ytd'] = $left + 0;
        }

        foreach ($right as $column => $value) {
            if (! is_numeric($value)) {
                continue;
            }

            $existing = $columns[$column] ?? null;
            $columns[$column] = $this->numeric((is_numeric($existing) ? $existing + 0 : 0) + $value + 0);
        }

        return $columns;
    }

    /**
     * @param  array<string, array{value: mixed}>  $rows
     */
    private function absoluteSum(array $rows, string $column = self::SCALAR_COLUMN): int|float
    {
        $sum = 0;

        foreach ($rows as $row) {
            $value = $this->cell($row['value'], $column);
            $sum += $value === null ? 0 : abs($value);
        }

        return $sum;
    }

    /**
     * Besaran eliminasi dihitung per kolom nilai: kolom yang tidak terkirim sumber
     * (atau nol) tidak ikut dikurangi sehingga sajian skalar lama tidak berubah.
     *
     * @param  array<string, array{value: mixed}>  $debits
     * @param  array<string, array{value: mixed}>  $credits
     * @return array<string, int|float>
     */
    private function eliminationAmounts(array $debits, array $credits): array
    {
        $amounts = [];

        foreach ($this->valueColumns([...$debits, ...$credits]) as $column) {
            $amount = min($this->absoluteSum($debits, $column), $this->absoluteSum($credits, $column));

            if ($amount > 0) {
                $amounts[$column] = $amount;
            }
        }

        return $amounts;
    }

    /**
     * Kolom yang dipakai sebagai angka eliminasi yang dilaporkan: ytd lebih dulu,
     * kemudian kolom tersisa, lalu nilai skalar.
     *
     * @param  array<string, int|float>  $amounts
     */
    private function basisAmount(array $amounts): int|float|null
    {
        foreach ([self::YTD_COLUMN, 'current', 'prior', self::SCALAR_COLUMN] as $column) {
            if (array_key_exists($column, $amounts)) {
                return $amounts[$column];
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $rows
     * @return list<string>
     */
    private function valueColumns(array $rows): array
    {
        $columns = [];

        foreach ($rows as $row) {
            foreach (array_keys($this->cells($row['value'] ?? null)) as $column) {
                $columns[$column] = true;
            }
        }

        return array_keys($columns);
    }

    /**
     * Pecah nilai baris menjadi sel per kolom. Nilai skalar menjadi satu sel tanpa kolom.
     *
     * @return array<string, int|float|null>
     */
    private function cells(mixed $value): array
    {
        if (is_array($value)) {
            $cells = [];

            foreach (IncomeStatementNormalizer::COLUMNS as $column) {
                if (array_key_exists($column, $value)) {
                    $cells[$column] = is_numeric($value[$column]) ? $value[$column] + 0 : null;
                }
            }

            return $cells === [] ? [self::SCALAR_COLUMN => null] : $cells;
        }

        return [self::SCALAR_COLUMN => is_numeric($value) ? $value + 0 : null];
    }

    private function cell(mixed $value, string $column): int|float|null
    {
        return $this->cells($value)[$column] ?? null;
    }

    /**
     * Angka representatif sebuah baris untuk pencocokan akun internal.
     */
    private function scalarOf(mixed $value): int|float|null
    {
        $cells = $this->cells($value);

        foreach ([self::YTD_COLUMN, 'current', 'prior', self::SCALAR_COLUMN] as $column) {
            if (is_numeric($cells[$column] ?? null)) {
                return $cells[$column] + 0;
            }
        }

        return null;
    }

    private function numeric(int|float|null $value): int|float
    {
        if ($value === null || (is_float($value) && fmod($value, 1.0) === 0.0)) {
            return (int) ($value ?? 0);
        }

        return $value;
    }

    /**
     * @param  Collection<int, TenantApplication>  $applications
     * @param  array{rows: list<array{level: int, code: string, name: string, key: string, values: array<int, mixed>}>, totals: array<int, array<string, int|float|null>|null>, appStates: array<int, string>}  $comparative
     * @param  array{rows: list<array{level: int, code: string, name: string, key: string, value: int|float|null}>, totals: array<string, int|float|null>, eliminations: list<array{side: string, code: string, name: string, amount: int|float}>, offline: list<int>}|null  $consolidated
     */
    private function pdf(Collection $applications, array $comparative, ?array $consolidated, string $reportType, int $year, ?int $month, string $mode): DomPDF
    {
        $data = [
            'holdingName' => $this->holdingName($applications),
            'period' => $this->periodLabel($year, $month),
            'applications' => $applications,
            'totalLabels' => self::TOTAL_LABELS,
            'generatedAt' => Carbon::now()->translatedFormat('d F Y H:i'),
        ];

        if ($mode === self::MODE_KONSOLIDASI) {
            return Pdf::loadView('reports.consolidated', [
                ...$data,
                'title' => self::REPORTS[$reportType]['label'].' Konsolidasi',
                'subtitle' => 'Entitas ekonomi tunggal — holding beserta seluruh unit usaha (setelah eliminasi internal)',
                'report' => $consolidated,
            ])->setPaper('a4', 'portrait');
        }

        return Pdf::loadView('reports.comparative', [
            ...$data,
            'title' => self::REPORTS[$reportType]['label'].' Gabungan',
            'subtitle' => 'Sajian komparatif antar unit usaha — tanpa eliminasi transaksi internal',
            'report' => $comparative,
        ])->setPaper('a4', 'landscape');
    }

    /**
     * @param  Collection<int, TenantApplication>  $applications
     * @param  list<array{label: string, filename: string, rows: int, eliminations: int}>  $summaries
     */
    private function manifest(Collection $applications, int $year, ?int $month, string $mode, array $summaries): string
    {
        $consolidated = $mode === self::MODE_KONSOLIDASI;
        $lines = [
            'BUNDLE LAPORAN KEUANGAN LEVEL HOLDING',
            str_repeat('=', 72),
            'Holding         : '.$this->holdingName($applications),
            'Periode         : '.$this->periodLabel($year, $month),
            'Mode Laporan    : '.($consolidated ? 'KONSOLIDASI (satu entitas ekonomi tunggal)' : 'GABUNGAN (komparatif antar unit usaha)'),
            'Basis Eliminasi : '.($consolidated
                ? 'Saldo dan transaksi resiprokal antar unit dieliminasi: utang-piutang internal, jual-beli internal, serta penyertaan versus modal anak usaha.'
                : 'Tanpa eliminasi. Saldo antar unit tetap dicatat apa adanya pada kolom masing-masing unit usaha.'),
            'Tanggal Unduh   : '.Carbon::now()->toDateTimeString().' ('.config('app.timezone').')',
            '',
            'UNIT USAHA YANG DISERTAKAN ('.$applications->count().')',
            str_repeat('-', 72),
        ];

        foreach ($applications->values() as $index => $application) {
            $lines[] = sprintf(
                '%d. %s | aplikasi: %s | instance: %s',
                $index + 1,
                $application->label ?? ($application->application?->name ?? 'Unit '.$application->id),
                $application->application?->name ?? '-',
                $application->instance_url ?? '-',
            );
        }

        $lines[] = '';
        $lines[] = 'BERKAS DALAM ARSIP';
        $lines[] = str_repeat('-', 72);

        foreach ($summaries as $summary) {
            $lines[] = sprintf('%s (%d baris laporan)', $summary['filename'], $summary['rows']);
        }

        $lines[] = $this->manifestName().' (berkas ini)';
        $lines[] = '';
        $lines[] = 'CATATAN ATAS ANGKA';
        $lines[] = str_repeat('-', 72);

        if ($consolidated) {
            foreach ($summaries as $summary) {
                $lines[] = sprintf(
                    '%s: %s',
                    $summary['label'],
                    $summary['eliminations'] > 0
                        ? $summary['eliminations'].' kelompok saldo/transaksi internal dieliminasi.'
                        : 'tidak ditemukan saldo atau transaksi resiprokal antar unit untuk dieliminasi.',
                );
            }
        } else {
            $lines[] = 'Kolom Total Gabungan pada tiap berkas merupakan akumulasi agregat antar unit usaha dan bukan angka konsolidasi.';
        }

        $lines[] = 'Unit usaha berstatus offline atau gagal autentikasi tidak menyumbang angka pada laporan.';

        return implode("\n", $lines)."\n";
    }

    private function normalizeMode(string $mode): string
    {
        return match (strtolower(trim($mode))) {
            self::MODE_GABUNGAN, 'comparative', 'combined' => self::MODE_GABUNGAN,
            self::MODE_KONSOLIDASI, 'consolidated' => self::MODE_KONSOLIDASI,
            default => abort(422, 'Mode laporan tidak valid. Gunakan gabungan atau konsolidasi.'),
        };
    }

    /**
     * @param  Collection<int, TenantApplication>  $applications
     */
    private function holdingName(Collection $applications): string
    {
        return $applications->first()?->tenant?->name ?? 'Holding';
    }

    /**
     * @param  Collection<int, TenantApplication>  $applications
     */
    private function archiveName(Collection $applications, string $mode, string $period): string
    {
        $slug = str($applications->first()?->tenant?->slug ?: 'tenant')->slug()->value() ?: 'tenant';

        return sprintf('bundle-laporan-%s-%s-%s.zip', $mode, $slug, $period);
    }

    private function manifestName(): string
    {
        return 'README_Manifest.txt';
    }

    private function periodSlug(int $year, ?int $month): string
    {
        return $month === null ? $year.'-ALL' : sprintf('%d-%02d', $year, $month);
    }

    private function periodLabel(int $year, ?int $month): string
    {
        return $month === null ? 'Tahunan '.$year : Carbon::create($year, $month)->translatedFormat('F Y');
    }

    private function temporaryArchivePath(string $period): string
    {
        $path = tempnam(sys_get_temp_dir(), 'bundle-laporan-'.$period);

        if ($path === false) {
            throw new RuntimeException('Gagal membuat berkas arsip sementara.');
        }

        rename($path, $path.'.zip');

        return $path.'.zip';
    }
}
