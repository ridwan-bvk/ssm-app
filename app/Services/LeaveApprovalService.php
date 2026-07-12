<?php

namespace App\Services;

use App\Models\Kehadiran;
use App\Models\Perizinan;
use App\Models\PresensiGuru;
use App\Models\PresensiSiswa;
use Illuminate\Support\Facades\DB;

/**
 * Mirrors PerizinanModel::konfirmasiPerizinan() from the CI4 app: approving
 * a leave request bulk-writes/overwrites attendance rows for every day in
 * the requested range. The old app did NOT write an audit log entry for
 * this bulk mutation — the migration plan flags that as a gap to fix, so
 * this port adds the missing AuditLogService call (see plan §5.3).
 */
class LeaveApprovalService
{
    public function confirm(Perizinan $perizinan, string $status, int $idPetugas): void
    {
        DB::transaction(function () use ($perizinan, $status, $idPetugas) {
            $oldStatus = $perizinan->status;

            $perizinan->update([
                'status' => $status,
                'id_petugas' => $idPetugas,
            ]);

            if ($status === 'Disetujui') {
                $this->applyToAttendance($perizinan);
            }

            AuditLogService::log(
                "Konfirmasi Perizinan: {$oldStatus} -> {$status}",
                'tb_perizinan',
                $perizinan->id_perizinan,
                ['status' => $oldStatus],
                ['status' => $status],
            );
        });
    }

    private function applyToAttendance(Perizinan $perizinan): void
    {
        $idKehadiran = $perizinan->tipe_izin === 'Sakit' ? Kehadiran::SAKIT : Kehadiran::IZIN;
        $period = $perizinan->tanggal_mulai->toPeriod($perizinan->tanggal_selesai);

        foreach ($period as $date) {
            $tanggal = $date->toDateString();

            // Not updateOrCreate(): its lookup does a plain where('tanggal', ...)
            // which only matches reliably on drivers with a real DATE column
            // type (MySQL normalizes on write). On SQLite the date-cast
            // column stores the full "Y-m-d 00:00:00" string, so a plain
            // string match would miss the existing row and attempt a
            // duplicate insert, violating the (id_siswa/id_guru, tanggal)
            // unique index. whereDate() is portable across both.
            if ($perizinan->id_siswa) {
                $existing = PresensiSiswa::where('id_siswa', $perizinan->id_siswa)->whereDate('tanggal', $tanggal)->first();
                $data = [
                    'id_siswa' => $perizinan->id_siswa,
                    'id_kelas' => $perizinan->siswa->id_kelas,
                    'tanggal' => $tanggal,
                    'id_kehadiran' => $idKehadiran,
                    'keterangan' => $perizinan->alasan,
                ];
                $existing ? $existing->update($data) : PresensiSiswa::create($data);
            } elseif ($perizinan->id_guru) {
                $existing = PresensiGuru::where('id_guru', $perizinan->id_guru)->whereDate('tanggal', $tanggal)->first();
                $data = [
                    'id_guru' => $perizinan->id_guru,
                    'tanggal' => $tanggal,
                    'id_kehadiran' => $idKehadiran,
                    'keterangan' => $perizinan->alasan,
                ];
                $existing ? $existing->update($data) : PresensiGuru::create($data);
            }
        }
    }
}
