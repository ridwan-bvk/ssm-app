<?php

namespace App\Filament\Teacher\Widgets;

use App\Models\Kehadiran;
use App\Services\AttendanceStatusResolver;
use App\Services\WaliKelasResolver;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

/**
 * Mirrors Teacher\Dashboard::index()'s "summary" stats from the CI4 app,
 * scoped to the logged-in teacher's own wali-kelas class (never trusts
 * any client input for which class — always re-derived server-side).
 */
class TeacherClassOverview extends BaseWidget
{
    public function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $kelas = app(WaliKelasResolver::class)->resolveForUser(auth()->user());

        if (! $kelas) {
            return [
                Stat::make('Kelas', 'Belum ditugaskan')
                    ->description('Anda belum ditugaskan sebagai wali kelas'),
            ];
        }

        $resolver = app(AttendanceStatusResolver::class);
        $today = Carbon::today()->toDateString();

        return [
            Stat::make('Kelas', "{$kelas->tingkat} {$kelas->jurusan?->jurusan} {$kelas->index_kelas}")
                ->description($kelas->siswa()->count().' siswa'),
            Stat::make('Hadir', $resolver->countByStatus('siswa', Kehadiran::HADIR, $today, $kelas->id_kelas))
                ->color('success'),
            Stat::make('Sakit', $resolver->countByStatus('siswa', Kehadiran::SAKIT, $today, $kelas->id_kelas))
                ->color('warning'),
            Stat::make('Izin', $resolver->countByStatus('siswa', Kehadiran::IZIN, $today, $kelas->id_kelas))
                ->color('info'),
        ];
    }
}
