<?php

namespace App\Services;

use App\Models\GeneralSetting;
use App\Models\Guru;
use App\Models\HariLibur;
use App\Models\Kehadiran;
use App\Models\PresensiGuru;
use App\Models\PresensiSiswa;
use App\Models\Siswa;
use App\Services\Whatsapp\WhatsappNotificationManager;
use Illuminate\Support\Carbon;

/**
 * Mirrors app/Controllers/Scan.php from the CI4 app (cekKode/absenMasuk/
 * absenPulang). Takes an explicit $when timestamp rather than always using
 * "now" so that scans queued offline and synced later are recorded with
 * the time they actually happened at, not the time they were uploaded —
 * this is the whole point of Phase 2's offline scanning support.
 */
class ScanService
{
    public function __construct(private readonly WhatsappNotificationManager $whatsapp) {}

    public function isHolidayToday(?Carbon $date = null): ?string
    {
        $date ??= Carbon::now();

        return HariLibur::whereDate('tanggal', $date->toDateString())->value('keterangan');
    }

    /**
     * @return array{type: string, model: Siswa|Guru}|null
     */
    public function resolvePerson(string $code): ?array
    {
        $siswa = Siswa::where('unique_code', $code)->orWhere('rfid_code', $code)->first();
        if ($siswa) {
            return ['type' => 'siswa', 'model' => $siswa];
        }

        $guru = Guru::where('unique_code', $code)->orWhere('rfid_code', $code)->first();
        if ($guru) {
            return ['type' => 'guru', 'model' => $guru];
        }

        return null;
    }

    /**
     * @return array{status: bool, message: string, presensi?: array}
     */
    public function checkIn(string $type, Siswa|Guru $person, Carbon $when): array
    {
        $date = $when->toDateString();
        $time = $when->toTimeString();

        if ($type === 'guru') {
            $existing = PresensiGuru::where('id_guru', $person->id_guru)->whereDate('tanggal', $date)->first();
            if ($existing) {
                return ['status' => false, 'message' => 'Anda sudah absen hari ini', 'presensi' => $this->presensiGuruArray($existing)];
            }

            $presensi = PresensiGuru::create([
                'id_guru' => $person->id_guru,
                'tanggal' => $date,
                'jam_masuk' => $time,
                'id_kehadiran' => Kehadiran::HADIR,
                'keterangan' => '',
            ]);

            $message = "{$person->nama_guru} dengan NIP {$person->nuptk} sudah absen masuk pada tanggal {$date} jam {$time}";
            $this->whatsapp->notify($person->no_hp, $message);

            return ['status' => true, 'message' => 'Absen masuk berhasil', 'presensi' => $this->presensiGuruArray($presensi)];
        }

        $existing = PresensiSiswa::where('id_siswa', $person->id_siswa)->whereDate('tanggal', $date)->first();
        if ($existing) {
            return ['status' => false, 'message' => 'Anda sudah absen hari ini', 'presensi' => $this->presensiSiswaArray($existing)];
        }

        $menitKeterlambatan = $this->calculateLateMinutes($when);

        $presensi = PresensiSiswa::create([
            'id_siswa' => $person->id_siswa,
            'id_kelas' => $person->id_kelas,
            'tanggal' => $date,
            'jam_masuk' => $time,
            'id_kehadiran' => Kehadiran::HADIR,
            'menit_keterlambatan' => $menitKeterlambatan,
            'keterangan' => $menitKeterlambatan > 0 ? "Terlambat {$menitKeterlambatan} menit" : '',
        ]);

        if ($menitKeterlambatan > 0) {
            $person->increment('poin_pelanggaran', $menitKeterlambatan);
        }

        $message = "Siswa {$person->nama_siswa} dengan NIS {$person->nis} sudah absen masuk pada tanggal {$date} jam {$time}";
        if ($menitKeterlambatan > 0) {
            $message .= " (Terlambat {$menitKeterlambatan} menit)";
        }
        $this->whatsapp->notify($person->no_hp, $message);

        return ['status' => true, 'message' => 'Absen masuk berhasil', 'presensi' => $this->presensiSiswaArray($presensi)];
    }

    /**
     * @return array{status: bool, message: string, presensi?: array}
     */
    public function checkOut(string $type, Siswa|Guru $person, Carbon $when): array
    {
        $date = $when->toDateString();
        $time = $when->toTimeString();

        if ($type === 'guru') {
            $existing = PresensiGuru::where('id_guru', $person->id_guru)->whereDate('tanggal', $date)->first();
            if (! $existing) {
                return ['status' => false, 'message' => 'Anda belum absen hari ini'];
            }

            $existing->update(['jam_keluar' => $time, 'keterangan' => '']);

            $message = "{$person->nama_guru} dengan NIP {$person->nuptk} sudah absen pulang pada tanggal {$date} jam {$time}";
            $this->whatsapp->notify($person->no_hp, $message);

            return ['status' => true, 'message' => 'Absen pulang berhasil', 'presensi' => $this->presensiGuruArray($existing->fresh())];
        }

        $existing = PresensiSiswa::where('id_siswa', $person->id_siswa)->whereDate('tanggal', $date)->first();
        if (! $existing) {
            return ['status' => false, 'message' => 'Anda belum absen hari ini'];
        }

        $existing->update(['jam_keluar' => $time, 'keterangan' => '']);

        $message = "Siswa {$person->nama_siswa} dengan NIS {$person->nis} sudah absen pulang pada tanggal {$date} jam {$time}";
        $this->whatsapp->notify($person->no_hp, $message);

        return ['status' => true, 'message' => 'Absen pulang berhasil', 'presensi' => $this->presensiSiswaArray($existing->fresh())];
    }

    /**
     * Mirrors Scan.php:136-146's exact late-minutes calculation.
     */
    private function calculateLateMinutes(Carbon $when): int
    {
        $jamMasukLimit = GeneralSetting::first()?->jam_masuk_limit;

        if (! $jamMasukLimit) {
            return 0;
        }

        $time = $when->toTimeString();

        if ($time <= $jamMasukLimit) {
            return 0;
        }

        $limit = Carbon::parse($when->toDateString().' '.$jamMasukLimit);

        return abs($when->diffInMinutes($limit));
    }

    private function presensiSiswaArray(PresensiSiswa $presensi): array
    {
        return [
            'id_presensi' => $presensi->id_presensi,
            'tanggal' => $presensi->tanggal->toDateString(),
            'jam_masuk' => $presensi->jam_masuk,
            'jam_keluar' => $presensi->jam_keluar,
            'menit_keterlambatan' => $presensi->menit_keterlambatan,
        ];
    }

    private function presensiGuruArray(PresensiGuru $presensi): array
    {
        return [
            'id_presensi' => $presensi->id_presensi,
            'tanggal' => $presensi->tanggal->toDateString(),
            'jam_masuk' => $presensi->jam_masuk,
            'jam_keluar' => $presensi->jam_keluar,
        ];
    }
}
