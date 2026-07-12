<?php

namespace App\Services;

use App\Models\PresensiGuru;
use App\Models\PresensiSiswa;

/**
 * Mirrors DataAbsenSiswa::ubahKehadiran() / DataAbsenGuru::ubahKehadiran()
 * and PresensiSiswaModel::updatePresensi() / PresensiGuruModel's equivalent
 * from the CI4 app: upsert one attendance record for a person+date, keeping
 * the existing keterangan on null, and always writing an audit log entry.
 */
class AttendanceEditService
{
    public function updateSiswa(int $idSiswa, int $idKelas, string $tanggal, int $idKehadiran, ?string $jamMasuk, ?string $jamKeluar, ?string $keterangan, string $namaSiswa): void
    {
        $existing = PresensiSiswa::where('id_siswa', $idSiswa)->whereDate('tanggal', $tanggal)->first();
        $oldData = $existing?->toArray();

        $data = [
            'id_kelas' => $idKelas,
            'id_kehadiran' => $idKehadiran,
            'keterangan' => $keterangan ?? $existing?->keterangan ?? '',
        ];

        if ($jamMasuk !== null) {
            $data['jam_masuk'] = $jamMasuk;
        }
        if ($jamKeluar !== null) {
            $data['jam_keluar'] = $jamKeluar;
        }

        PresensiSiswa::updateOrCreate(['id_siswa' => $idSiswa, 'tanggal' => $tanggal], $data);

        AuditLogService::log(
            "Ubah Kehadiran Siswa: {$namaSiswa}",
            'tb_presensi_siswa',
            $existing?->id_presensi,
            $oldData,
            [...$data, 'id_siswa' => $idSiswa, 'tanggal' => $tanggal],
        );
    }

    public function updateGuru(int $idGuru, string $tanggal, int $idKehadiran, ?string $jamMasuk, ?string $jamKeluar, ?string $keterangan, string $namaGuru): void
    {
        $existing = PresensiGuru::where('id_guru', $idGuru)->whereDate('tanggal', $tanggal)->first();
        $oldData = $existing?->toArray();

        $data = [
            'id_kehadiran' => $idKehadiran,
            'keterangan' => $keterangan ?? $existing?->keterangan ?? '',
        ];

        if ($jamMasuk !== null) {
            $data['jam_masuk'] = $jamMasuk;
        }
        if ($jamKeluar !== null) {
            $data['jam_keluar'] = $jamKeluar;
        }

        PresensiGuru::updateOrCreate(['id_guru' => $idGuru, 'tanggal' => $tanggal], $data);

        AuditLogService::log(
            "Ubah Kehadiran Guru: {$namaGuru}",
            'tb_presensi_guru',
            $existing?->id_presensi,
            $oldData,
            [...$data, 'id_guru' => $idGuru, 'tanggal' => $tanggal],
        );
    }
}
