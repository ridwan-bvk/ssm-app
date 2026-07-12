<?php

namespace App\Services;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\PresensiGuru;
use App\Models\PresensiSiswa;
use App\Models\Siswa;
use App\Support\WorkingDays;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Mirrors Admin\GenerateLaporan + Teacher\Reports from the CI4 app (the two
 * were near-identical duplicated implementations there — unified into one
 * service here per the migration plan). Builds the monthly attendance
 * matrix data; PDF rendering is a real dompdf render instead of the old
 * app's "window.print() on an HTML page" / "HTML served with a .doc mime
 * type" hacks.
 */
class ReportService
{
    /**
     * @return array{dates: Carbon[], siswa: Collection, cells: array, laki: int, perempuan: int}
     */
    public function monthlySiswa(int $idKelas, string $bulan): array
    {
        $kelas = Kelas::with('jurusan')->findOrFail($idKelas);
        $siswa = Siswa::where('id_kelas', $idKelas)->orderBy('nama_siswa')->get();

        $start = Carbon::parse($bulan)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $dates = $this->workingDatesInRange($start, $end);

        $records = PresensiSiswa::whereIn('id_siswa', $siswa->pluck('id_siswa'))
            ->whereBetween('tanggal', [$start->toDateString(), $end->endOfDay()->toDateTimeString()])
            ->get()
            ->groupBy(fn (PresensiSiswa $p) => $p->id_siswa.'|'.$p->tanggal->toDateString());

        $cells = [];
        foreach ($siswa as $s) {
            foreach ($dates as $date) {
                $key = $s->id_siswa.'|'.$date->toDateString();
                $cells[$s->id_siswa][$date->toDateString()] = $records->get($key)?->first()?->id_kehadiran;
            }
        }

        return [
            'kelas' => $kelas,
            'dates' => $dates,
            'siswa' => $siswa,
            'cells' => $cells,
            'laki' => $siswa->where('jenis_kelamin', 'Laki-laki')->count(),
            'perempuan' => $siswa->where('jenis_kelamin', 'Perempuan')->count(),
            'bulan' => $start,
        ];
    }

    public function monthlyGuru(string $bulan): array
    {
        $guru = Guru::orderBy('nama_guru')->get();

        $start = Carbon::parse($bulan)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $dates = $this->workingDatesInRange($start, $end);

        $records = PresensiGuru::whereIn('id_guru', $guru->pluck('id_guru'))
            ->whereBetween('tanggal', [$start->toDateString(), $end->endOfDay()->toDateTimeString()])
            ->get()
            ->groupBy(fn (PresensiGuru $p) => $p->id_guru.'|'.$p->tanggal->toDateString());

        $cells = [];
        foreach ($guru as $g) {
            foreach ($dates as $date) {
                $key = $g->id_guru.'|'.$date->toDateString();
                $cells[$g->id_guru][$date->toDateString()] = $records->get($key)?->first()?->id_kehadiran;
            }
        }

        return [
            'dates' => $dates,
            'guru' => $guru,
            'cells' => $cells,
            'laki' => $guru->where('jenis_kelamin', 'Laki-laki')->count(),
            'perempuan' => $guru->where('jenis_kelamin', 'Perempuan')->count(),
            'bulan' => $start,
        ];
    }

    /**
     * @return Carbon[]
     */
    private function workingDatesInRange(Carbon $start, Carbon $end): array
    {
        $dates = [];

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if (WorkingDays::isWorkingDay($date)) {
                $dates[] = $date->copy();
            }
        }

        return $dates;
    }
}
