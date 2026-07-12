<?php

namespace App\Services;

use App\Models\GeneralSetting;
use App\Models\Guru;
use App\Models\HariLibur;
use App\Models\Kehadiran;
use App\Models\PresensiSiswa;
use App\Models\Siswa;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Consolidates the "Belum Scan vs Alfa" logic that was duplicated nearly
 * verbatim across PresensiSiswaModel::getPresensiByKehadiran()/getAttendanceTrend()
 * and PresensiGuruModel's equivalents in the CI4 app — one implementation
 * here, used for both siswa and guru (see migration plan §1).
 *
 * The rule (unchanged from the old app): a person with no Hadir/Sakit/Izin
 * record for a date is "Belum Scan" (pending) until jam_pulang_standard has
 * passed on that date (or the date is in the past), at which point they are
 * "Alfa" (finalized absence).
 *
 * This is also the single choke point behind every dashboard widget in both
 * the admin and teacher panels (TodayAttendanceOverview, AttendanceTrendChart,
 * TopLateStudents, AbsenteeAlerts and their Teacher\Widgets mirrors), which
 * is why the three query methods below are cached here rather than in each
 * widget individually. TTL-based rather than invalidated: CACHE_STORE is
 * `database`, which doesn't support cache tags, and there are no model
 * observers on the attendance tables to hook exact invalidation into. A
 * short TTL is an acceptable staleness window for "glance" dashboard stats
 * — the actual attendance-edit pages (AbsensiGuru/AbsensiSiswa/
 * TeacherAttendance) query presensi() directly and never go through this
 * cache, so they're never stale. If CACHE_STORE ever becomes `redis`, switch
 * to Cache::tags(['attendance'])->remember() here and flush that tag from
 * the attendance write paths (ScanService, AttendanceEditService,
 * LeaveApprovalService) instead of relying on TTL.
 */
class AttendanceStatusResolver
{
    private const CACHE_TTL = 45;

    public function isAfterSchool(string $date, ?string $jamPulangStandard = null): bool
    {
        $jamPulangStandard ??= GeneralSetting::first()?->jam_pulang_standard ?? '14:00:00';
        $now = CarbonImmutable::now();
        $today = $now->toDateString();

        return $today > $date || ($today === $date && $now->toTimeString() > $jamPulangStandard);
    }

    /**
     * @return int count of people in the given attendance-status bucket for
     *             the date. Passing Kehadiran::TANPA_KETERANGAN returns
     *             everyone with no Hadir/Sakit/Izin record (Alfa + Belum Scan).
     */
    public function countByStatus(string $type, int $idKehadiran, string $date, ?int $idKelas = null): int
    {
        $key = "attendance:count:{$type}:{$idKehadiran}:{$date}:".($idKelas ?? 'all');

        return Cache::remember($key, self::CACHE_TTL, fn () => $this->query($type, $date, $idKehadiran, $idKelas)->count());
    }

    /**
     * @return array{hadir: int[], sakit: int[], izin: int[], alfa: int[], belum_absen: int[]}
     */
    public function trend(string $type, int $days = 7, ?int $idKelas = null): array
    {
        $key = "attendance:trend:{$type}:{$days}:".($idKelas ?? 'all');

        return Cache::remember($key, self::CACHE_TTL, function () use ($type, $days, $idKelas) {
            $result = ['hadir' => [], 'sakit' => [], 'izin' => [], 'alfa' => [], 'belum_absen' => []];
            $now = CarbonImmutable::now();

            for ($i = $days - 1; $i >= 0; $i--) {
                $date = $now->subDays($i)->toDateString();

                if (HariLibur::whereDate('tanggal', $date)->exists()) {
                    foreach ($result as &$bucket) {
                        $bucket[] = 0;
                    }
                    unset($bucket);

                    continue;
                }

                $result['hadir'][] = $this->countByStatus($type, Kehadiran::HADIR, $date, $idKelas);
                $result['sakit'][] = $this->countByStatus($type, Kehadiran::SAKIT, $date, $idKelas);
                $result['izin'][] = $this->countByStatus($type, Kehadiran::IZIN, $date, $idKelas);

                $notPresent = $this->countByStatus($type, Kehadiran::TANPA_KETERANGAN, $date, $idKelas);

                if ($this->isAfterSchool($date)) {
                    $result['alfa'][] = $notPresent;
                    $result['belum_absen'][] = 0;
                } else {
                    $result['alfa'][] = 0;
                    $result['belum_absen'][] = $notPresent;
                }
            }

            return $result;
        });
    }

    /**
     * Mirrors PresensiSiswaModel::getConsecutiveAbsences(): flags students
     * with no Hadir/Sakit/Izin record across the last N *distinct dates on
     * record* (not necessarily N calendar working days), skipping students
     * with zero attendance history at all (e.g. freshly imported via CSV).
     *
     * @return Collection<int, Siswa>
     */
    public function consecutiveAbsences(int $consecutiveDays = 3, ?int $idKelas = null): Collection
    {
        $key = "attendance:consecutive:{$consecutiveDays}:".($idKelas ?? 'all');

        return Cache::remember($key, self::CACHE_TTL, function () use ($consecutiveDays, $idKelas) {
            $dates = PresensiSiswa::query()
                ->select('tanggal')
                ->groupBy('tanggal')
                ->orderByDesc('tanggal')
                ->limit($consecutiveDays)
                ->pluck('tanggal')
                ->map(fn ($date) => $date->toDateString());

            if ($dates->count() < $consecutiveDays) {
                return collect();
            }

            $students = Siswa::query()->with('kelas.jurusan')
                ->when($idKelas, fn ($q) => $q->where('id_kelas', $idKelas))
                ->get();

            return $students->filter(function (Siswa $student) use ($dates, $consecutiveDays) {
                if ($student->presensi()->count() === 0) {
                    return false;
                }

                $absentCount = $dates->filter(function (string $date) use ($student) {
                    return ! $student->presensi()
                        ->whereDate('tanggal', $date)
                        ->whereIn('id_kehadiran', [Kehadiran::HADIR, Kehadiran::SAKIT, Kehadiran::IZIN])
                        ->exists();
                })->count();

                return $absentCount >= $consecutiveDays;
            })->values();
        });
    }

    private function query(string $type, string $date, int $idKehadiran, ?int $idKelas = null)
    {
        [$roster, $rosterTable, $presensiTable, $rosterKey] = $type === 'siswa'
            ? [Siswa::query(), 'tb_siswa', 'tb_presensi_siswa', 'id_siswa']
            : [Guru::query(), 'tb_guru', 'tb_presensi_guru', 'id_guru'];

        $query = $roster
            ->leftJoin($presensiTable, function ($join) use ($presensiTable, $rosterTable, $rosterKey, $date) {
                $join->on("{$presensiTable}.{$rosterKey}", '=', "{$rosterTable}.{$rosterKey}")
                    ->whereDate("{$presensiTable}.tanggal", $date);
            });

        if ($idKelas && $type === 'siswa') {
            $query->where('tb_siswa.id_kelas', $idKelas);
        }

        if ($idKehadiran === Kehadiran::TANPA_KETERANGAN) {
            return $query->where(function ($q) use ($presensiTable) {
                $q->whereNull("{$presensiTable}.id_kehadiran")
                    ->orWhereNotIn("{$presensiTable}.id_kehadiran", [Kehadiran::HADIR, Kehadiran::SAKIT, Kehadiran::IZIN]);
            });
        }

        return $query->where("{$presensiTable}.id_kehadiran", $idKehadiran);
    }
}
