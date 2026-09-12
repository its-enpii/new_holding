@php
    $states = $report['appStates'] ?? [];
    $totals = $report['totals'] ?? [];
    $totalLabels = $totalLabels ?? [];
    $stateLabels = ['ok' => 'live', 'cache' => 'cache', 'offline' => 'offline', 'auth_error' => 'auth gagal'];
    $money = fn (int|float|null $value): string => number_format($value, 2, ',', '.');
    $labelFor = fn (\App\Models\TenantApplication $application): string => $application->label
        ?? $application->application?->name
        ?? ('Unit '.$application->id);
    $combinedValue = function (array $row) use ($applications): int|float|null {
        $sum = null;

        foreach ($applications as $application) {
            $value = $row['values'][$application->id] ?? null;

            if (is_numeric($value)) {
                $sum = ($sum ?? 0) + $value + 0;
            }
        }

        return $sum;
    };
    $combinedTotal = function (string $key) use ($applications, $totals): int|float|null {
        $sum = null;

        foreach ($applications as $application) {
            $value = $totals[$application->id][$key] ?? null;

            if (is_numeric($value)) {
                $sum = ($sum ?? 0) + $value + 0;
            }
        }

        return $sum;
    };
    $totalKeys = [];

    foreach ($applications as $application) {
        foreach ((array) ($totals[$application->id] ?? []) as $key => $value) {
            if (is_numeric($value)) {
                $totalKeys[] = $key;
            }
        }
    }

    $totalKeys = array_values(array_unique($totalKeys));
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $title }} — {{ $period }}</title>
    <style>
        @page { margin: 16mm 10mm; }
        body { font-family: "DejaVu Sans", "Helvetica Neue", Arial, sans-serif; color: #191830; font-size: 8.5pt; }
        .kicker { color: #4338ca; font-size: 7.5pt; letter-spacing: 1.2px; text-transform: uppercase; margin: 0; }
        h1 { font-size: 16pt; font-weight: 600; margin: 2px 0 0; }
        h2 { font-size: 10.5pt; font-weight: 600; color: #4338ca; margin: 2px 0 0; }
        .meta { border: 1px solid #c6c5da; border-left: 3px solid #4338ca; background: #f1f1f9; padding: 6px 9px; margin: 10px 0 12px; font-size: 7.8pt; }
        .meta span { color: #474663; }
        table { width: 100%; border-collapse: collapse; font-size: 8pt; }
        caption { caption-side: top; text-align: left; font-size: 7.8pt; color: #474663; padding-bottom: 4px; }
        th, td { border-bottom: 1px solid #dadbe8; padding: 4px 5px; text-align: right; }
        th:first-child, td:first-child { text-align: left; }
        th { background: #4338ca; color: #ffffff; font-weight: 600; }
        th .state { display: block; font-size: 6.2pt; font-weight: 400; opacity: .85; }
        th.total { background: #312a86; }
        td.total { background: #eef0ff; font-weight: 600; }
        .section { background: #f1f1f9; font-weight: 600; }
        .level-2 td:first-child { padding-left: 14px; }
        .level-3 td:first-child { padding-left: 26px; }
        .code { color: #6b6a86; }
        .totals td { border-top: 2px solid #4338ca; background: #f1f1f9; font-weight: 600; }
        .note { margin-top: 12px; font-size: 7.5pt; color: #474663; line-height: 1.5; }
        footer { position: fixed; bottom: -10mm; left: 0; right: 0; font-size: 7pt; color: #6b6a86; text-align: center; }
    </style>
</head>
<body>
    @isset($subtitle)
        <p class="kicker">Laporan Keuangan Gabungan — Level Holding</p>
    @endisset
    <h1>{{ $title }}</h1>
    @isset($subtitle)
        <h2>{{ $holdingName }}</h2>
    @endisset
    <div class="meta">
        <div><span>Periode</span> : {{ $period }}</div>
        @isset($subtitle)
            <div><span>Basis Penyajian</span> : {{ $subtitle }}</div>
        @endisset
        <div><span>Unit usaha</span> : {{ $applications->map($labelFor(...))->implode(' | ') }}</div>
        @isset($generatedAt)
            <div><span>Disusun</span> : {{ $generatedAt }}</div>
        @endisset
    </div>
    <table>
        @isset($subtitle)
            <caption>Kolom Total Gabungan menjumlahkan seluruh unit usaha tanpa eliminasi transaksi internal antar unit.</caption>
        @endisset
        <thead>
            <tr>
                <th style="width: {{ $applications->count() > 4 ? '18' : '26' }}%">Kode / Nama</th>
                @foreach ($applications as $application)
                    <th>
                        {{ $application->label ?? $application->application?->name }}
                        @isset($subtitle)
                            <span class="state">{{ $stateLabels[$states[$application->id] ?? 'offline'] ?? '-' }}</span>
                        @endisset
                    </th>
                @endforeach
                <th class="total">Total Gabungan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report['rows'] as $row)
                @php $combined = $combinedValue($row); @endphp
                <tr class="level-{{ $row['level'] }} @class(['section' => $row['level'] === 1])">
                    <td><span class="code">{{ $row['code'] ?: '—' }}</span> {{ $row['name'] }}</td>
                    @foreach ($applications as $application)
                        @php $value = $row['values'][$application->id] ?? null; @endphp
                        <td>{{ is_numeric($value) ? $money($value) : '-' }}</td>
                    @endforeach
                    <td class="total">{{ $combined === null ? '-' : $money($combined) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $applications->count() + 2 }}" style="text-align: center">Tidak ada akun yang berhasil ditarik dari unit usaha untuk periode ini.</td>
                </tr>
            @endforelse
        </tbody>
        @if ($totalKeys !== [])
            <tfoot class="totals">
                @foreach ($totalKeys as $key)
                    @php $combined = $combinedTotal($key); @endphp
                    <tr>
                        <td>{{ $totalLabels[$key] ?? ucfirst(str_replace('_', ' ', $key)) }}</td>
                        @foreach ($applications as $application)
                            @php $value = $totals[$application->id][$key] ?? null; @endphp
                            <td>{{ is_numeric($value) ? $money($value) : '-' }}</td>
                        @endforeach
                        <td class="total">{{ $combined === null ? '-' : $money($combined) }}</td>
                    </tr>
                @endforeach
            </tfoot>
        @endif
    </table>
    @isset($subtitle)
        <p class="note">
            Transaksi dan saldo resiprokal antar unit usaha (utang-piutang internal, jual-beli antar unit, penyertaan vs
            modal) tidak dieliminasi, sehingga kolom Total Gabungan merupakan akumulasi agregat dan bukan angka konsolidasi.
            Unit usaha berstatus offline atau gagal autentikasi tidak menyumbang angka.
        </p>
    @endisset
    <footer>{{ $title }} — {{ $period }} — Portal Holding</footer>
</body>
</html>
