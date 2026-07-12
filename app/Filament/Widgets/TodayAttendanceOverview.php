<?php

namespace App\Filament\Widgets;

use App\Models\Kehadiran;
use App\Services\AttendanceStatusResolver;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

/**
 * Mirrors the "jumlahKehadiranSiswa" stat cards from Admin\Dashboard::index()
 * in the CI4 app: today's Hadir/Sakit/Izin/Alfa counts for students.
 */
class TodayAttendanceOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $resolver = app(AttendanceStatusResolver::class);
        $today = Carbon::today()->toDateString();

        return [
            Stat::make('Hadir', $resolver->countByStatus('siswa', Kehadiran::HADIR, $today))
                ->color('success'),
            Stat::make('Sakit', $resolver->countByStatus('siswa', Kehadiran::SAKIT, $today))
                ->color('warning'),
            Stat::make('Izin', $resolver->countByStatus('siswa', Kehadiran::IZIN, $today))
                ->color('info'),
            Stat::make(
                $resolver->isAfterSchool($today) ? 'Alfa' : 'Belum Scan',
                $resolver->countByStatus('siswa', Kehadiran::TANPA_KETERANGAN, $today)
            )->color('danger'),
        ];
    }
}
