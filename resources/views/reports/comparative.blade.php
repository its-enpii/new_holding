<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $title }} — {{ $period }}</title>
    <style>
        body { font-family: "Plus Jakarta Sans", sans-serif; color: #191830; }
        h1 { font-size: 18px; font-weight: 600; margin: 0; }
        p { color: #474663; font-size: 10px; margin: 2px 0 12px; }
        table { width: 100%; border-collapse: collapse; font-size: 9px; }
        th, td { border-bottom: 1px solid #c6c5da; padding: 5px 6px; text-align: right; }
        th:first-child, td:first-child { text-align: left; }
        th { background: #f1f1f9; font-weight: 600; }
        .section { background: #f1f1f9; font-weight: 600; }
        .total { border-top: 2px solid #4338ca; font-weight: 600; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p>{{ $period }} — {{ $applications->pluck('label')->implode(' | ') }}</p>
    <table>
        <thead>
            <tr>
                <th>Kode / Nama</th>
                @foreach ($applications as $application)
                    <th>{{ $application->label ?? $application->application->name }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($report['rows'] as $row)
                <tr @class(['section' => $row['level'] === 1])>
                    <td>{{ $row['code'] }} — {{ $row['name'] }}</td>
                    @foreach ($applications as $application)
                        <td>{{ $row['values'][$application->id] ?? '-' }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
