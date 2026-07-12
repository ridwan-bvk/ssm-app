<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\Perizinan;
use App\Models\Siswa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mirrors app/Controllers/Perizinan.php from the CI4 app: the public,
 * unauthenticated izin/sakit digital submission portal.
 */
class IzinController extends Controller
{
    public function lookup(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:siswa,guru'],
            'identifier' => ['required', 'string'],
        ]);

        if ($data['type'] === 'guru') {
            $guru = Guru::where('nuptk', $data['identifier'])->first();

            if ($guru) {
                return response()->json(['status' => 'success', 'data' => ['id' => $guru->id_guru, 'nama' => $guru->nama_guru]]);
            }

            return response()->json(['status' => 'error', 'message' => 'Guru dengan NUPTK tersebut tidak ditemukan.'], 404);
        }

        $siswa = Siswa::where('nis', $data['identifier'])->first();

        if ($siswa) {
            return response()->json(['status' => 'success', 'data' => ['id' => $siswa->id_siswa, 'nama' => $siswa->nama_siswa]]);
        }

        return response()->json(['status' => 'error', 'message' => 'Siswa dengan NIS tersebut tidak ditemukan.'], 404);
    }

    public function submit(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:siswa,guru'],
            'id_target' => ['required', 'integer'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'tipe_izin' => ['required', 'in:Sakit,Izin'],
            'alasan' => ['required', 'string'],
            'bukti' => ['required', 'image', 'max:2048', 'mimes:jpg,jpeg,png'],
        ]);

        if ($data['type'] === 'siswa' && ! Siswa::where('id_siswa', $data['id_target'])->exists()) {
            return response()->json(['status' => 'error', 'message' => 'Siswa tidak ditemukan.'], 404);
        }
        if ($data['type'] === 'guru' && ! Guru::where('id_guru', $data['id_target'])->exists()) {
            return response()->json(['status' => 'error', 'message' => 'Guru tidak ditemukan.'], 404);
        }

        $path = $request->file('bukti')->store('perizinan', 'public');

        Perizinan::create([
            'id_siswa' => $data['type'] === 'siswa' ? $data['id_target'] : null,
            'id_guru' => $data['type'] === 'guru' ? $data['id_target'] : null,
            'tanggal_mulai' => $data['tanggal_mulai'],
            'tanggal_selesai' => $data['tanggal_selesai'],
            'tipe_izin' => $data['tipe_izin'],
            'alasan' => $data['alasan'],
            'bukti' => $path,
            'status' => 'Pending',
        ]);

        return response()->json(['status' => 'success', 'message' => 'Pengajuan izin berhasil dikirim. Silakan tunggu konfirmasi dari Admin.']);
    }
}
