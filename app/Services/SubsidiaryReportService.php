<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\SubsidiaryAuthException;
use App\Exceptions\SubsidiaryUnavailableException;
use App\Models\ReportCache;
use App\Models\TenantApplication;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Throwable;

final class SubsidiaryReportService
{
    private const REPORT_TYPES = ['balance_sheet', 'income_statement', 'cash_flow', 'equity_changes', 'calk'];

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
     * @return array{rows: list<array{level: int, code: string, name: string, key: string, values: array<int, mixed>}>, totals: array<int, array<string, int|float|null>|null>, appStates: array<int, string>, payloads: array<int, array<string, mixed>>}
     */
    public function comparative(Collection $tenantApplications, string $reportType, ?int $month, int $year, bool $force = false): array
    {
        $rows = [];
        $rowIndex = [];
        $totals = [];
        $appStates = [];
        $payloads = [];

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
            $totals[$appId] = $payload['data']['totals'] ?? null;

            foreach ($this->reportRows($reportType, $payload) as $row) {
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
        ];
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
     * @return list<array{level: int, code: string, name: string, key: string, value: int|float|null}>
     */
    private function reportRows(string $reportType, array $payload): array
    {
        $data = $payload['data'] ?? [];

        if ($reportType === 'income_statement') {
            return $this->incomeStatementRows(is_array($data) ? $data : []);
        }

        return $this->balanceSheetRows(is_array($data) ? $data : []);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array{level: int, code: string, name: string, key: string, value: int|float|null}>
     */
    private function balanceSheetRows(array $data): array
    {
        $rows = [];
        $sections = is_array($data['sections'] ?? null) ? $data['sections'] : [];

        foreach ($sections as $section) {
            $rows = [...$rows, ...$this->flattenNode($section)];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array{level: int, code: string, name: string, key: string, value: int|float|null}>
     */
    private function incomeStatementRows(array $data): array
    {
        $rows = [];
        $groups = is_array($data['groups'] ?? null) ? $data['groups'] : [];

        foreach ($groups as $group) {
            $rows = [...$rows, ...$this->flattenNode($group)];
        }

        return $rows;
    }

    /**
     * @return list<array{level: int, code: string, name: string, key: string, value: int|float|null}>
     */
    private function flattenNode(mixed $node): array
    {
        if (! is_array($node)) {
            return [];
        }

        $code = (string) ($node['code'] ?? '');
        $name = (string) ($node['name'] ?? '');
        $level = (int) ($node['level'] ?? 1);
        $rows = [[
            'level' => $level,
            'code' => $code,
            'name' => $name,
            'key' => $code.'||'.$name,
            'value' => is_numeric($node['balance'] ?? null) ? $node['balance'] : null,
        ]];

        foreach (is_array($node['children'] ?? null) ? $node['children'] : [] as $child) {
            $rows = [...$rows, ...$this->flattenNode($child)];
        }

        return $rows;
    }
}
