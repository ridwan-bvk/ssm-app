<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kehadiran;
use App\Models\Siswa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Mirrors app/Controllers/CekKehadiran.php from the CI4 app: the public,
 * unauthenticated "check my attendance" portal. NIS + phone number combo
 * is the only "auth" — same as the old app, kept as-is (not a Phase 2
 * scope-creep fix; the migration plan flags rate-limiting this as a
 * Phase-1-era gap, handled via routes/api.php throttling instead).
 */
class CekKehadiranController extends Controller
{
    public function view(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nis' => ['required', 'string'],
            'no_hp' => ['required', 'string'],
        ]);

        $siswa = Siswa::where('nis', $data['nis'])->where('no_hp', $data['no_hp'])->first();

        if (! $siswa) {
            return response()->json(['status' => 'error', 'message' => 'Kombinasi NIS dan Nomor HP tidak cocok.'], 404);
        }

        $year = Carbon::now()->year;
        $month = Carbon::now()->month;

        $history = $siswa->presensi()
            ->with('kehadiran')
            ->whereYear('tanggal', $year)
            ->orderByDesc('tanggal')
            ->get();

        $stats = ['hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alfa' => 0];

        foreach ($history as $h) {
            if ($h->tanggal->month !== $month) {
                continue;
            }

            match ($h->id_kehadiran) {
                Kehadiran::HADIR => $stats['hadir']++,
                Kehadiran::SAKIT => $stats['sakit']++,
                Kehadiran::IZIN => $stats['izin']++,
                Kehadiran::TANPA_KETERANGAN => $stats['alfa']++,
                default => null,
            };
        }

        return response()->json([
            'status' => 'success',
            'siswa' => ['nama_siswa' => $siswa->nama_siswa, 'nis' => $siswa->nis],
            'history' => $history->map(fn ($h) => [
                'tanggal' => $h->tanggal->toDateString(),
                'jam_masuk' => $h->jam_masuk,
                'jam_keluar' => $h->jam_keluar,
                'kehadiran' => $h->kehadiran?->kehadiran,
            ]),
            'stats' => $stats,
            'month_name' => Carbon::now()->translatedFormat('F Y'),
        ]);
    }
}
