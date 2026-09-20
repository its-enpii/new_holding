<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\SubsidiaryAuthException;
use App\Exceptions\SubsidiaryUnavailableException;
use App\Models\ReportCache;
use App\Models\TenantApplication;
use App\Support\Reports\IncomeStatementNormalizer;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Throwable;

final class SubsidiaryReportService
{
    private const REPORT_TYPES = ['balance_sheet', 'income_statement', 'cash_flow', 'equity_changes', 'calk'];

    /**
     * Kolom nilai cadangan yang dikenali pada payload mentah subsidiary.
     *
     * @var list<string>
     */
    private const VALUE_FIELDS = ['value', 'balance', 'saldo', 'amount', 'ytd'];

    public function __construct(
        private readonly IncomeStatementNormalizer $incomeStatementNormalizer = new IncomeStatementNormalizer,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function fetch(TenantApplication $tenantApplication, string $reportType, ?int $month, int $year, bool $force = false): array
    {
        $this->assertReportType($reportType);
        $period = $year.'-'.($month === null ? 'ALL' : str_pad((string) $month, 2, '0', STR_PAD_LEFT));

        if (! $force) {
            $cached = ReportCache::query()
                ->where('tenant_application_id', $tenantApplication->id)
                ->where('report_type', $reportType)
                ->where('period', $period)
                ->valid()
                ->first();

            if ($cached !== null) {
                /** @var array<string, mixed> $payload */
                $payload = $cached->payload;

                return $payload;
            }
        }

        $endpoint = '/api/v1/holding/reports/'.str_replace('_', '-', $reportType);
        try {
            $response = Http::baseUrl(rtrim($tenantApplication->instance_url, '/'))
                ->acceptJson()
                ->timeout(15)
                ->retry(
                    2,
                    200,
                    fn (Throwable $exception, mixed $request = null, ?string $method = null): bool => $exception instanceof ConnectionException
                        || $exception->response?->serverError() === true,
                    false,
                )
                ->withToken($tenantApplication->api_secret)
                ->get($endpoint, $this->query($year, $month));
        } catch (ConnectionException $exception) {
            throw new SubsidiaryUnavailableException($tenantApplication->instance_url, previous: $exception);
        }

        if ($response->status() === 401 || $response->status() === 403) {
            throw new SubsidiaryAuthException($tenantApplication->instance_url);
        }

        if ($response->failed()) {
            throw new SubsidiaryUnavailableException($tenantApplication->instance_url);
        }

        /** @var array<string, mixed>|null $payload */
        $payload = $response->json();

        if (($payload['status'] ?? null) !== 'success') {
            throw new SubsidiaryUnavailableException($tenantApplication->instance_url);
        }

        $cache = ReportCache::query()->updateOrCreate([
            'tenant_application_id' => $tenantApplication->id,
            'report_type' => $reportType,
            'period' => $period,
        ], [
            'payload' => $payload,
            'fetched_at' => now(),
            'expires_at' => now()->addMinutes(30),
        ]);

        /** @var array<string, mixed> */
        return $cache->payload;
    }

    /**
     * @param  Collection<int, TenantApplication>  $tenantApplications
     * @return array{rows: list<array{level: int, code: string, name: string, key: string, values: array<int, mixed>}>, totals: array<int, array<string, mixed>|null>, appStates: array<int, string>, payloads: array<int, array<string, mixed>>, meta: array{variant: string|null, warnings: list<string>}}
     */
    public function comparative(Collection $tenantApplications, string $reportType, ?int $month, int $year, bool $force = false): array
    {
        $rows = [];
        $rowIndex = [];
        $totals = [];
        $appStates = [];
        $payloads = [];
        $variants = [];
        $warnings = [];

        foreach ($tenantApplications as $tenantApplication) {
            $appId = $tenantApplication->id;

            try {
                $payload = $this->fetch($tenantApplication, $reportType, $month, $year, $force);
                $appStates[$appId] = 'ok';
            } catch (SubsidiaryUnavailableException) {
                $payload = null;
                $appStates[$appId] = 'offline';
            } catch (SubsidiaryAuthException) {
                $payload = null;
                $appStates[$appId] = 'auth_error';
            }

            if ($payload === null) {
                $totals[$appId] = null;

                continue;
            }

            $appStates[$appId] = 'cache';
            $payloads[$appId] = $payload;

            // Kontrak v1: hanya laba rugi yang dinormalisasi. Total warisan (`data.totals`)
            // tetap milik sumber; holding hanya menambah `laba_rugi_normalized` dari `summary`.
            $model = $reportType === 'income_statement'
                ? $this->incomeStatementNormalizer->normalize(is_array($payload['data'] ?? null) ? $payload['data'] : [])
                : null;

            if ($model !== null) {
                $totals[$appId] = $this->totalValues($model, $payload);

                if ($model['meta']['variant'] !== null) {
                    $variants[$appId] = $model['meta']['variant'];
                }

                $warnings = array_values(array_unique([...$warnings, ...$model['meta']['warnings']]));
            } else {
                $totals[$appId] = $payload['data']['totals'] ?? null;
            }

            foreach ($this->reportRows($reportType, $payload, $model) as $row) {
                $key = $row['key'];
                $rowIndex[$key] ??= count($rows);
                $index = $rowIndex[$key];

                if (! isset($rows[$index])) {
                    $rows[$index] = [
                        'level' => $row['level'],
                        'code' => $row['code'],
                        'name' => $row['name'],
                        'key' => $key,
                        'values' => [],
                    ];
                }

                $rows[$index]['values'][$appId] = $row['value'];
            }
        }

        usort($rows, fn (array $left, array $right): int => [$left['code'], $left['level']] <=> [$right['code'], $right['level']]);

        foreach ($rows as &$row) {
            foreach ($tenantApplications as $tenantApplication) {
                $row['values'][$tenantApplication->id] ??= null;
            }

            ksort($row['values']);
        }

        foreach ($tenantApplications as $tenantApplication) {
            $totals[$tenantApplication->id] ??= null;
        }

        return [
            'rows' => $rows,
            'totals' => $totals,
            'appStates' => $appStates,
            'payloads' => $payloads,
            'meta' => [
                // Varian CoA hanyalah penanda (badge); sajian tetap satu halaman untuk semua varian.
                'variant' => $variants === [] ? null : implode(' / ', array_values(array_unique($variants))),
                'warnings' => $warnings,
            ],
        ];
    }

    /**
     * Total warisan sumber dipertahankan apa adanya, ditambah hasil normalisasi laba
     * rugi sebagai kunci skalar `laba_rugi_normalized_*`. Bentuk bersarang tidak dipakai
     * pada batas ini karena pengekspor CSV/PDF lama hanya memahami total skalar, dan
     * holding tidak pernah mengarang angka yang tidak dikirim sumber.
     *
     * @param  array{groups: list<array<string, mixed>>, totals: array<string, mixed>|null, meta: array{variant: string|null, source: string, warnings: list<string>}}  $model
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    private function totalValues(array $model, array $payload): ?array
    {
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $totals = is_array($data['totals'] ?? null) ? $data['totals'] : [];

        foreach ((array) ($model['totals']['laba_rugi_normalized'] ?? []) as $key => $value) {
            $scalar = is_numeric($value)
                ? $value + 0
                : $this->collapse(is_array($value) ? ($this->columns($value) ?? []) : []);

            if ($scalar !== null) {
                $totals['laba_rugi_normalized_'.(string) $key] = $scalar;
            }
        }

        return $totals === [] ? null : $totals;
    }

    private function assertReportType(string $reportType): void
    {
        abort_unless(in_array($reportType, self::REPORT_TYPES, true), 422, 'Jenis laporan tidak valid.');
    }

    /**
     * @return array<string, int|string|null>
     */
    private function query(int $year, ?int $month): array
    {
        return $month === null ? ['year' => $year] : ['year' => $year, 'month' => $month];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array{groups: list<array<string, mixed>>, totals: array<string, mixed>|null, meta: array{variant: string|null, source: string, warnings: list<string>}}|null  $model
     * @return list<array{level: int, code: string, name: string, key: string, value: array<string, int|float|null>|int|float|null}>
     */
    private function reportRows(string $reportType, array $payload, ?array $model = null): array
    {
        $data = $payload['data'] ?? [];

        if ($reportType === 'income_statement') {
            return $this->incomeStatementRows($model ?? $this->incomeStatementNormalizer->normalize(is_array($data) ? $data : []));
        }

        return $this->balanceSheetRows(is_array($data) ? $data : []);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array{level: int, code: string, name: string, key: string, value: array<string, int|float|null>|int|float|null}>
     */
    private function balanceSheetRows(array $data): array
    {
        $rows = [];
        $sections = is_array($data['sections'] ?? null) ? $data['sections'] : [];

        foreach ($sections as $section) {
            $rows = [...$rows, ...$this->flattenNode($section, keepColumns: false)];
        }

        return $rows;
    }

    /**
     * Baris laba rugi selalu dibaca dari hasil normalisasi kontrak v1 sehingga seluruh
     * bentuk payload (grup hierarkis, section bervarian, bucket legacy) turun ke satu sajian.
     *
     * @param  array{groups: list<array<string, mixed>>, totals: array<string, mixed>|null, meta: array{variant: string|null, source: string, warnings: list<string>}}  $model
     * @return list<array{level: int, code: string, name: string, key: string, value: array<string, int|float|null>|int|float|null}>
     */
    private function incomeStatementRows(array $model): array
    {
        $rows = [];

        foreach ($model['groups'] as $group) {
            $rows = [...$rows, ...$this->flattenNode($group, keepColumns: true)];
        }

        return $rows;
    }

    /**
     * @return list<array{level: int, code: string, name: string, key: string, value: array<string, int|float|null>|int|float|null}>
     */
    private function flattenNode(mixed $node, int $level = 1, bool $keepColumns = false): array
    {
        if (! is_array($node)) {
            return [];
        }

        $code = (string) ($node['code'] ?? '');
        $name = (string) ($node['name'] ?? '');
        $level = isset($node['level']) ? max(1, (int) $node['level']) : $level;
        $rows = [[
            'level' => $level,
            'code' => $code,
            'name' => $name,
            'key' => $code.'||'.$name,
            'value' => $this->nodeValue($node, $keepColumns),
        ]];

        foreach (is_array($node['children'] ?? null) ? $node['children'] : [] as $child) {
            $rows = [...$rows, ...$this->flattenNode($child, $level + 1, $keepColumns)];
        }

        return $rows;
    }

    /**
     * Nilai sebuah baris: triple `{prior, current, ytd}` dipertahankan hanya untuk
     * laporan berkala (laba rugi); jenis laporan lain direduksi ke satu angka berjalan
     * sehingga sajian neraca lama tidak berubah bentuk. Kolom cadangan
     * `value|balance|saldo|amount|ytd` membuat sumber non-standar tetap punya angka.
     *
     * @param  array<string, mixed>  $node
     * @return array<string, int|float|null>|int|float|null
     */
    private function nodeValue(array $node, bool $keepColumns = false): array|int|float|null
    {
        foreach (self::VALUE_FIELDS as $field) {
            $candidate = $node[$field] ?? null;

            if (is_numeric($candidate)) {
                return $candidate + 0;
            }

            if (is_array($candidate)) {
                $columns = $this->columns($candidate);

                if ($columns !== null) {
                    return $keepColumns ? $columns : $this->collapse($columns);
                }
            }
        }

        $columns = $this->columns($node);

        return $columns === null ? null : ($keepColumns ? $columns : $this->collapse($columns));
    }

    /**
     * @param  array<string, int|float|null>  $columns
     */
    private function collapse(array $columns): int|float|null
    {
        foreach (['ytd', 'current', 'prior'] as $column) {
            if (is_numeric($columns[$column] ?? null)) {
                return $columns[$column] + 0;
            }
        }

        return null;
    }

    /**
     * @param  array<array-key, mixed>  $source
     * @return array<string, int|float|null>|null
     */
    private function columns(array $source): ?array
    {
        $columns = [];

        foreach (IncomeStatementNormalizer::COLUMNS as $column) {
            if (! array_key_exists($column, $source)) {
                continue;
            }

            $value = $source[$column];
            $columns[$column] = is_numeric($value) ? $value + 0 : null;
        }

        return $columns === [] ? null : $columns;
    }
}
