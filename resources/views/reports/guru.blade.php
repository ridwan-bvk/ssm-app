<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Absensi Guru</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; }
        h2, h4 { text-align: center; margin: 2px 0; }
        table { border-collapse: collapse; width: 100%; }
        table.matrix th, table.matrix td { border: 1px solid #333; padding: 3px; text-align: center; }
        .h { background-color: lightgreen; }
        .s, .i { background-color: yellow; }
        .a { background-color: #f88; }
        .info { margin-top: 8px; }
    </style>
</head>
<body>
    <h2>DAFTAR HADIR GURU</h2>
    <h4>{{ $generalSettings->school_name }}</h4>
    <h4>TAHUN PELAJARAN {{ $generalSettings->school_year }}</h4>

    <p>Bulan: {{ $bulan->translatedFormat('F Y') }}</p>

    <table class="matrix">
        <tr>
            <th rowspan="2">No</th>
            <th rowspan="2">Nama</th>
            @foreach ($dates as $date)
                <th>{{ $date->format('d') }}</th>
            @endforeach
            <th colspan="4">Total</th>
        </tr>
        <tr>
            @foreach ($dates as $date)
                <th>{{ $date->translatedFormat('D') }}</th>
            @endforeach
            <th class="h">H</th>
            <th class="s">S</th>
            <th class="i">I</th>
            <th class="a">A</th>
        </tr>
        @foreach ($guru as $index => $g)
            @php
                $row = $cells[$g->id_guru] ?? [];
                $counts = ['h' => 0, 's' => 0, 'i' => 0, 'a' => 0];
                foreach ($row as $date => $idKehadiran) {
                    if (\Illuminate\Support\Carbon::parse($date)->isFuture()) {
                        continue;
                    }
                    match ($idKehadiran) {
                        1 => $counts['h']++,
                        2 => $counts['s']++,
                        3 => $counts['i']++,
                        default => $counts['a']++,
                    };
                }
            @endphp
            <tr>
                <td>{{ $index + 1 }}</td>
                <td style="text-align:left">{{ $g->nama_guru }}</td>
                @foreach ($dates as $date)
                    @php
                        $idKehadiran = $row[$date->toDateString()] ?? null;
                    @endphp
                    @if ($date->isFuture())
                        <td></td>
                    @elseif ($idKehadiran === 1)
                        <td class="h">H</td>
                    @elseif ($idKehadiran === 2)
                        <td class="s">S</td>
                    @elseif ($idKehadiran === 3)
                        <td class="i">I</td>
                    @else
                        <td class="a">A</td>
                    @endif
                @endforeach
                <td>{{ $counts['h'] ?: '-' }}</td>
                <td>{{ $counts['s'] ?: '-' }}</td>
                <td>{{ $counts['i'] ?: '-' }}</td>
                <td>{{ $counts['a'] ?: '-' }}</td>
            </tr>
        @endforeach
    </table>

    <table class="info">
        <tr><td>Jumlah guru</td><td>: {{ $guru->count() }}</td></tr>
        <tr><td>Laki-laki</td><td>: {{ $laki }}</td></tr>
        <tr><td>Perempuan</td><td>: {{ $perempuan }}</td></tr>
    </table>
</body>
</html>
