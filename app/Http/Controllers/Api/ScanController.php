<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GeneralSetting;
use App\Models\Guru;
use App\Models\Siswa;
use App\Services\ScanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Mirrors app/Controllers/Scan.php from the CI4 app, split into two
 * endpoints: `bootstrap` lets the PWA cache the roster + settings needed to
 * validate/queue scans while offline, and the scan endpoint itself accepts
 * an optional `scanned_at` so scans queued offline and synced later are
 * recorded at the time they actually happened (see migration plan Phase 2).
 */
class ScanController extends Controller
{
    public function __construct(private readonly ScanService $scanService) {}

    public function bootstrap(): JsonResponse
    {
        $settings = GeneralSetting::first();

        $roster = [
            ...Siswa::select(['id_siswa as id', 'unique_code', 'rfid_code', 'nama_siswa as nama', 'nis as nomor'])
                ->get()->map(fn ($s) => [...$s->toArray(), 'type' => 'siswa']),
            ...Guru::select(['id_guru as id', 'unique_code', 'rfid_code', 'nama_guru as nama', 'nuptk as nomor'])
                ->get()->map(fn ($g) => [...$g->toArray(), 'type' => 'guru']),
        ];

        return response()->json([
            'roster' => $roster,
            'settings' => [
                'jam_masuk_limit' => $settings?->jam_masuk_limit,
                'jam_pulang_standard' => $settings?->jam_pulang_standard,
                'hari_kerja' => $settings?->hari_kerja,
            ],
            'today' => Carbon::today()->toDateString(),
            'holiday_reason' => $this->scanService->isHolidayToday(),
        ]);
    }

    public function scan(Request $request): JsonResponse
    {
        $data = $request->validate([
            'unique_code' => ['required', 'string'],
            'waktu' => ['required', 'in:masuk,pulang'],
            'scanned_at' => ['nullable', 'date'],
        ]);

        $when = filled($data['scanned_at'] ?? null) ? Carbon::parse($data['scanned_at']) : Carbon::now();

        $holidayReason = $this->scanService->isHolidayToday($when);
        if ($holidayReason) {
            return response()->json([
                'status' => false,
                'message' => "Hari ini sistem presensi dinonaktifkan karena: {$holidayReason}",
            ], 422);
        }

        $person = $this->scanService->resolvePerson($data['unique_code']);
        if (! $person) {
            return response()->json(['status' => false, 'message' => 'Data tidak ditemukan'], 404);
        }

        $result = $data['waktu'] === 'masuk'
            ? $this->scanService->checkIn($person['type'], $person['model'], $when)
            : $this->scanService->checkOut($person['type'], $person['model'], $when);

        return response()->json([
            ...$result,
            'type' => $person['type'],
            'nama' => $person['model']->nama_siswa ?? $person['model']->nama_guru,
            'nomor' => $person['model']->nis ?? $person['model']->nuptk,
        ], $result['status'] ? 200 : 409);
    }
}
