<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $title }} — {{ $period }}</title>
    <style>
        @page { margin: 22mm 14mm; }
        body { font-family: "DejaVu Sans", "Helvetica Neue", Arial, sans-serif; color: #191830; font-size: 9.5pt; }
        .kicker { color: #4338ca; font-size: 8pt; letter-spacing: 1.4px; text-transform: uppercase; margin: 0; }
        h1 { font-size: 17pt; font-weight: 600; margin: 2px 0 0; }
        h2 { font-size: 11pt; font-weight: 600; color: #4338ca; margin: 2px 0 0; }
        .meta { border: 1px solid #c6c5da; border-left: 3px solid #4338ca; background: #f1f1f9; padding: 8px 10px; margin: 12px 0 14px; font-size: 8.5pt; }
        .meta span { color: #474663; }
        table { width: 100%; border-collapse: collapse; font-size: 9pt; }
        caption { caption-side: top; text-align: left; font-size: 8.5pt; color: #474663; padding-bottom: 4px; }
        th, td { border-bottom: 1px solid #dadbe8; padding: 5px 7px; text-align: right; }
        th { background: #4338ca; color: #ffffff; font-weight: 600; text-transform: uppercase; font-size: 7.8pt; letter-spacing: .4px; }
        th:first-child, td:first-child { text-align: left; }
        .code { color: #6b6a86; font-size: 8pt; }
        .level-1 { background: #f1f1f9; font-weight: 600; }
        .level-2 td:first-child { padding-left: 18px; }
        .level-3 td:first-child { padding-left: 30px; }
        .elimination td { background: #fff7ed; color: #7c2d12; font-style: italic; }
        .totals { border-top: 2px solid #4338ca; }
        .totals td { font-weight: 600; background: #eef0ff; }
        .note { margin-top: 14px; font-size: 8pt; color: #474663; line-height: 1.5; }
        footer { position: fixed; bottom: -14mm; left: 0; right: 0; font-size: 7.5pt; color: #6b6a86; text-align: center; }
    </style>
</head>
<body>
    <p class="kicker">Laporan Keuangan Konsolidasi — Level Holding</p>
    <h1>{{ $title }}</h1>
    <h2>{{ $holdingName }}</h2>
    <div class="meta">
        <div><span>Periode</span> : {{ $period }}</div>
        <div><span>Basis Penyajian</span> : {{ $subtitle }}</div>
        <div><span>Unit usaha dikonsolidasi</span> : {{ $applications->count() }} — {{ $applications->map(fn (\App\Models\TenantApplication $application) => $application->label ?? $application->application?->name ?? ('Unit '.$application->id))->implode(', ') }}</div>
        <div><span>Disusun</span> : {{ $generatedAt }}</div>
    </div>
    <table>
        <caption>Angka di bawah menyajikan satu kesatuan entitas ekonomi setelah eliminasi saldo dan transaksi resiprokal antar unit.</caption>
        <thead>
            <tr>
                <th style="width: 14%">Kode</th>
                <th style="width: 54%">Nama Akun</th>
                <th style="width: 32%">Saldo Konsolidasi (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report['rows'] as $row)
                <tr class="level-{{ $row['level'] }}">
                    <td><span class="code">{{ $row['code'] ?: '—' }}</span></td>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ is_numeric($row['value']) ? number_format($row['value'], 2, ',', '.') : '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align: center">Tidak ada akun konsolidasi untuk periode ini.</td>
                </tr>
            @endforelse
            @foreach ($report['eliminations'] as $elimination)
                <tr class="elimination">
                    <td><span class="code">{{ $elimination['code'] }}-{{ strtoupper($elimination['side']) }}</span></td>
                    <td>{{ $elimination['name'] }}</td>
                    <td>({{ number_format($elimination['amount'], 2, ',', '.') }})</td>
                </tr>
            @endforeach
        </tbody>
        @if (filled($report['totals']))
            <tfoot class="totals">
                @foreach ($report['totals'] as $key => $total)
                    <tr>
                        <td colspan="2">{{ $totalLabels[$key] ?? ucfirst(str_replace('_', ' ', $key)) }} — konsolidasi</td>
                        <td>{{ is_numeric($total) ? number_format($total, 2, ',', '.') : '—' }}</td>
                    </tr>
                @endforeach
            </tfoot>
        @endif
    </table>
    <p class="note">
        Eliminasi mencakup saldo utang-piutang antar unit, transaksi jual-beli internal untuk mencegah pencatatan ganda,
        serta penyertaan modal holding terhadap ekuitas anak usaha. Unit usaha berstatus offline atau gagal autentikasi
        tidak menyumbang angka pada sajian ini.
        @if (filled($report['offline']))
            Unit terdampak: {{ implode(', ', $report['offline']) }}.
        @endif
    </p>
    <footer>{{ $holdingName }} — {{ $title }} ({{ $period }}) — dibuat oleh Portal Holding</footer>
</body>
</html>
