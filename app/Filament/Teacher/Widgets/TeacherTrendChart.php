<?php

namespace App\Filament\Teacher\Widgets;

use App\Services\AttendanceStatusResolver;
use App\Services\WaliKelasResolver;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

/**
 * Mirrors the "grafikKehadiran" chart in Teacher\Dashboard::index(),
 * scoped to the teacher's own class.
 */
class TeacherTrendChart extends ChartWidget
{
    protected static ?string $heading = 'Tren Kehadiran Kelas (7 Hari Terakhir)';

    protected function getData(): array
    {
        $kelas = app(WaliKelasResolver::class)->resolveForUser(auth()->user());

        if (! $kelas) {
            return ['datasets' => [], 'labels' => []];
        }

        $trend = app(AttendanceStatusResolver::class)->trend('siswa', 7, $kelas->id_kelas);

        $labels = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $labels[] = $i === 0 ? 'Hari ini' : $date->translatedFormat('j M');
        }

        return [
            'datasets' => [
                ['label' => 'Hadir', 'data' => $trend['hadir'], 'borderColor' => '#22c55e'],
                ['label' => 'Sakit', 'data' => $trend['sakit'], 'borderColor' => '#eab308'],
                ['label' => 'Izin', 'data' => $trend['izin'], 'borderColor' => '#3b82f6'],
                ['label' => 'Alfa', 'data' => $trend['alfa'], 'borderColor' => '#ef4444'],
                ['label' => 'Belum Scan', 'data' => $trend['belum_absen'], 'borderColor' => '#9ca3af'],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
