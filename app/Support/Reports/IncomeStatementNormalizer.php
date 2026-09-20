<?php

declare(strict_types=1);

namespace App\Support\Reports;

/**
 * Penetapan SATU format global (kontrak v1) laporan Laba Rugi level holding.
 *
 * Holding hanya mengenal satu model internal. Seluruh bentuk payload subsidiary
 * (grup hierarkis ala SIDBM, section bervarian CoA ala Akubumdes, maupun bucket
 * tunggal legacy) dinormalisasi ke model tersebut di sisi holding; subsidiary
 * tidak pernah dipaksa mengubah kirimannya dan angkanya tidak pernah dihitung
 * ulang — hanya bentuknya yangdiseragamkan.
 *
 * Model global:
 *  - groups: list bersarang (kunci `children`) atau list datar, tiap baris
 *    `{level, code, name, value, children?}` dengan `value` triple
 *    `{prior, current, ytd}` atau skalar.
 *  - totals: `data.totals` warisan apa adanya, ditambah `laba_rugi_normalized`
 *    HANYA bila sumber mengirim `summary`.
 *  - meta: `{variant, source, warnings}`.
 */
final class IncomeStatementNormalizer
{
    public const SOURCE_GROUPS = 'groups';

    public const SOURCE_SECTIONS = 'sections';

    public const SOURCE_NORMALIZED = 'normalized';

    /** @var list<string> */
    public const COLUMNS = ['prior', 'current', 'ytd'];

    /** Nama bucket penutup hasil normalisasi `summary`. */
    private const CLOSING_LABEL = 'Laba (Rugi)';

    /** @var array<string, string> */
    private const SUMMARY_LABELS = [
        'before_tax' => 'Laba Sebelum Pajak',
        'tax' => 'Pajak Penghasilan',
        'after_tax' => 'Laba Setelah Pajak',
    ];

    /** @var array<string, string> */
    private const LEGACY_COLUMNS = ['s_d_lalu' => 'prior', 'periode_ini' => 'current', 's_d_sekarang' => 'ytd'];

    /**
     * @param  array<string, mixed>  $data
     * @return array{groups: list<array<string, mixed>>, totals: array<string, mixed>|null, meta: array{variant: string|null, source: string, warnings: list<string>}}
     */
    public function normalize(array $data): array
    {
        $variant = isset($data['coa_variant']) ? (string) $data['coa_variant'] : null;
        $totals = $this->totals($data);

        if ($this->isGroupFormat($data)) {
            /** @var list<array<string, mixed>> $groups */
            $groups = $this->groupNodes(array_values($data['groups']));

            return $this->model($groups, $totals, $variant, self::SOURCE_GROUPS, []);
        }

        if ($this->isSectionFormat($data)) {
            /** @var list<array<string, mixed>> $groups */
            $groups = $this->sectionNodes(array_values($data['sections']), is_array($data['summary'] ?? null) ? $data['summary'] : null);

            return $this->model($groups, $totals, $variant, self::SOURCE_SECTIONS, []);
        }

        if ($this->isLegacyFormat($data)) {
            /** @var list<array<string, mixed>> $groups */
            $groups = $this->legacyNodes($data);

            return $this->model($groups, $totals, $variant, self::SOURCE_NORMALIZED, []);
        }

        /** @var list<array<string, mixed>> $groups */
        $groups = array_values($this->unknownNodes($data));

        return $this->model($groups, $totals, $variant, self::SOURCE_NORMALIZED, ['format_tidak_dikenal']);
    }

    /**
     * @param  list<array<string, mixed>>  $groups
     * @param  array<string, mixed>|null  $totals
     * @param  list<string>  $warnings
     * @return array{groups: list<array<string, mixed>>, totals: array<string, mixed>|null, meta: array{variant: string|null, source: string, warnings: list<string>}}
     */
    private function model(array $groups, ?array $totals, ?string $variant, string $source, array $warnings): array
    {
        return [
            'groups' => $groups,
            'totals' => $totals,
            'meta' => [
                'variant' => $variant,
                'source' => $source,
                'warnings' => $warnings,
            ],
        ];
    }

    /**
     * Total warisan diteruskan apa adanya; total baru tidak pernah dikarang holding.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    private function totals(array $data): ?array
    {
        $totals = is_array($data['totals'] ?? null) ? $data['totals'] : [];

        if (is_array($data['summary'] ?? null)) {
            $normalized = [];

            foreach ($data['summary'] as $key => $entry) {
                if (is_numeric($entry)) {
                    $normalized[(string) $key] = (float) $entry;
                } elseif (is_array($entry)) {
                    $normalized[(string) $key] = $this->triple($entry);
                }
            }

            if ($normalized !== []) {
                $totals['laba_rugi_normalized'] = $normalized;
            }
        }

        return $totals === [] ? null : $totals;
    }

    /**
     * Pola A: hierarki grup dengan kolom prior/current/ytd pada tiap simpul.
     *
     * @param  array<string, mixed>  $data
     */
    private function isGroupFormat(array $data): bool
    {
        $groups = $data['groups'] ?? null;

        if (! is_array($groups) || ! array_is_list($groups) || $groups === []) {
            return false;
        }

        $first = reset($groups);

        if (! is_array($first) || array_key_exists('rows', $first) || ! array_key_exists('name', $first)) {
            return false;
        }

        return $this->treeHasColumns($groups);
    }

    /**
     * @param  array<array-key, mixed>  $nodes
     */
    private function treeHasColumns(array $nodes): bool
    {
        foreach ($nodes as $node) {
            if (! is_array($node)) {
                continue;
            }

            foreach (self::COLUMNS as $column) {
                if (array_key_exists($column, $node)) {
                    return true;
                }
            }

            if (is_array($node['children'] ?? null) && $this->treeHasColumns($node['children'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<mixed>  $nodes
     * @param  positive-int  $level
     * @return list<array<string, mixed>>
     */
    private function groupNodes(array $nodes, int $level = 1): array
    {
        $rows = [];

        foreach ($nodes as $node) {
            if (! is_array($node)) {
                continue;
            }

            $children = is_array($node['children'] ?? null) ? $this->groupNodes(array_values($node['children']), $level + 1) : [];
            $row = [
                'level' => isset($node['level']) ? max(1, (int) $node['level']) : $level,
                'code' => (string) ($node['code'] ?? ''),
                'name' => (string) ($node['name'] ?? ''),
                'value' => $this->hasAnyColumn($node) ? $this->triple($node) : null,
            ];

            if ($children !== []) {
                $row['children'] = $children;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Pola B: section bervarian CoA (Akubumdes) dengan summary penutup.
     *
     * @param  array<string, mixed>  $data
     */
    private function isSectionFormat(array $data): bool
    {
        $sections = $data['sections'] ?? null;

        if (! is_array($sections) || ! array_is_list($sections) || $sections === []) {
            return false;
        }

        $hasSectionShape = false;

        foreach ($sections as $section) {
            if (is_array($section) && (array_key_exists('rows', $section) || array_key_exists('label', $section))) {
                $hasSectionShape = true;

                break;
            }
        }

        if (! $hasSectionShape) {
            return false;
        }

        if (is_array($data['summary'] ?? null)) {
            return true;
        }

        foreach ($sections as $section) {
            if (! is_array($section)) {
                continue;
            }

            foreach (is_array($section['rows'] ?? null) ? $section['rows'] : [] as $row) {
                if (in_array((string) ($row['account_type'] ?? ''), ['revenue', 'expense'], true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  list<mixed>  $sections
     * @param  array<string, mixed>|null  $summary
     * @return list<array<string, mixed>>
     */
    private function sectionNodes(array $sections, ?array $summary): array
    {
        $nodes = [];

        foreach ($sections as $section) {
            if (! is_array($section)) {
                continue;
            }

            $total = $section['total'] ?? null;
            $children = [];

            foreach (is_array($section['rows'] ?? null) ? $section['rows'] : [] as $row) {
                if (! is_array($row)) {
                    continue;
                }

                $children[] = [
                    'level' => 2,
                    'code' => (string) ($row['code'] ?? ''),
                    'name' => (string) ($row['name'] ?? ''),
                    'value' => $this->triple($row),
                ];
            }

            $label = (string) ($section['label'] ?? '');
            $node = [
                'level' => 1,
                'code' => '',
                'name' => is_numeric($total) ? $label.' (YTD)' : $label,
                // `total` pada pola B adalah total YTD section; `prior`/`current`
                // section tidak selalu terisi oleh sumber sehingga menjadi 0.0.
                'value' => [
                    'prior' => $this->number($section, 'prior'),
                    'current' => $this->number($section, 'current'),
                    'ytd' => is_numeric($total) ? (float) $total : 0.0,
                ],
            ];

            if ($children !== []) {
                $node['children'] = $children;
            }

            $nodes[] = $node;
        }

        $closing = $this->closingNodes($summary);

        if ($closing !== []) {
            $nodes[] = [
                'level' => 1,
                'code' => '',
                'name' => self::CLOSING_LABEL,
                'value' => null,
                'children' => $closing,
            ];
        }

        return $nodes;
    }

    /**
     * @param  array<string, mixed>|null  $summary
     * @return list<array<string, mixed>>
     */
    private function closingNodes(?array $summary): array
    {
        if ($summary === null) {
            return [];
        }

        $rows = [];

        foreach (self::SUMMARY_LABELS as $key => $label) {
            if (! array_key_exists($key, $summary)) {
                continue;
            }

            $rows[] = [
                'level' => 2,
                'code' => '',
                'name' => $label,
                'value' => is_array($summary[$key])
                    ? $this->triple($summary[$key])
                    : (is_numeric($summary[$key]) ? (float) $summary[$key] : null),
            ];
        }

        return $rows;
    }

    /**
     * Pola C: bucket tunggal legacy (`pendapatan`/`beban`).
     *
     * @param  array<string, mixed>  $data
     */
    private function isLegacyFormat(array $data): bool
    {
        foreach (['pendapatan' => 'Pendapatan', 'beban' => 'Beban'] as $key => $label) {
            $bucket = $data[$key] ?? null;

            if (is_array($bucket) && array_is_list($bucket) && $bucket !== []) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array<string, mixed>>
     */
    private function legacyNodes(array $data): array
    {
        $nodes = [];

        foreach (['pendapatan' => 'Pendapatan', 'beban' => 'Beban'] as $key => $label) {
            $bucket = $data[$key] ?? null;

            if (! is_array($bucket) || $bucket === []) {
                continue;
            }

            $children = $this->legacyChildren(array_values($bucket));

            if ($children === []) {
                continue;
            }

            $nodes[] = [
                'level' => 1,
                'code' => '',
                'name' => $label,
                'value' => null,
                'children' => $children,
            ];
        }

        return $nodes;
    }

    /**
     * @param  list<mixed>  $rows
     * @return list<array<string, mixed>>
     */
    private function legacyChildren(array $rows): array
    {
        $children = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $grandChildren = is_array($row['rincian_akun'] ?? null) ? $this->legacyChildren(array_values($row['rincian_akun'])) : [];
            $child = [
                'level' => 2,
                'code' => (string) ($row['kode_akun'] ?? ''),
                'name' => (string) ($row['nama_akun'] ?? ''),
                'value' => $this->legacyValue($row['saldo'] ?? null),
            ];

            if ($grandChildren !== []) {
                $child['children'] = $grandChildren;
            }

            $children[] = $child;
        }

        return $children;
    }

    /**
     * Saldo skalar dianggap nilai berjalan tahunan (ytd) tanpa mengarang kolom lain.
     */
    private function legacyValue(mixed $saldo): array
    {
        if (is_numeric($saldo)) {
            return ['prior' => 0.0, 'current' => 0.0, 'ytd' => (float) $saldo];
        }

        if (! is_array($saldo)) {
            return ['prior' => 0.0, 'current' => 0.0, 'ytd' => 0.0];
        }

        $mapped = [];

        foreach (self::LEGACY_COLUMNS as $source => $column) {
            if (array_key_exists($source, $saldo)) {
                $mapped[$column] = $saldo[$source];
            }
        }

        return $this->triple($mapped === [] ? $saldo : $mapped);
    }

    /**
     * Fallback: bentuk mentah diteruskan sebagai baseline; `flattenNode` pada service
     * membacanya lewat `balance`/`saldo`/`amount`/`ytd` sehingga tidak ada sajian mentah.
     *
     * @param  array<string, mixed>  $data
     * @return array<array-key, mixed>
     */
    private function unknownNodes(array $data): array
    {
        foreach (['groups', 'sections'] as $key) {
            if (is_array($data[$key] ?? null)) {
                return $data[$key];
            }
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function hasAnyColumn(array $node): bool
    {
        foreach (self::COLUMNS as $column) {
            if (array_key_exists($column, $node)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<array-key, mixed>  $source
     * @return array{prior: float, current: float, ytd: float}
     */
    private function triple(array $source): array
    {
        $triple = [];

        foreach (self::COLUMNS as $column) {
            $triple[$column] = $this->number($source, $column);
        }

        return $triple;
    }

    /**
     * @param  array<array-key, mixed>  $source
     */
    private function number(array $source, string $column): float
    {
        return is_numeric($source[$column] ?? null) ? (float) $source[$column] : 0.0;
    }
}
