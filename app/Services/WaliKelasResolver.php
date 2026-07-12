<?php

namespace App\Services;

use App\Models\Kelas;
use App\Models\User;

/**
 * Mirrors KelasModel::getKelasByWali($user->id_guru), which every
 * Teacher\* controller in the CI4 app called to scope data to "the class
 * this teacher is wali kelas of." This is the SINGLE place that derives
 * that scope in the Laravel port — every teacher-panel page must call
 * this rather than trusting any client-supplied id_kelas/id_siswa, which
 * closes the IDOR-shaped gaps flagged in the migration plan (§5.1):
 * Teacher\Dashboard::getAttendanceList/getEditModal/updateSingleAttendance
 * and Teacher\Perizinan::konfirmasi all trusted client input in the old app.
 */
class WaliKelasResolver
{
    public function resolveForUser(User $user): ?Kelas
    {
        if (! $user->id_guru) {
            return null;
        }

        return Kelas::with('jurusan')->where('id_wali_kelas', $user->id_guru)->first();
    }
}
