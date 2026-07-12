<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use App\Services\QrService;
use App\Services\WaliKelasResolver;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Teacher-scoped QR print routes — always re-derives the wali-kelas class
 * from the logged-in user via WaliKelasResolver rather than trusting a
 * client-supplied id_kelas, unlike Admin\QRGenerator's shared print routes
 * which are fine for admin/superadmin (who may legitimately view any
 * class) but would be an IDOR gap if reused as-is for teachers.
 */
class TeacherQrPrintController extends Controller
{
    public function __construct(
        private readonly QrService $qr,
        private readonly WaliKelasResolver $waliKelas,
    ) {}

    public function siswa(): View|Response
    {
        $kelas = $this->waliKelas->resolveForUser(auth()->user());

        if (! $kelas) {
            abort(403, 'Anda belum ditugaskan sebagai wali kelas.');
        }

        $items = Siswa::where('id_kelas', $kelas->id_kelas)->orderBy('nama_siswa')->get()->map(function (Siswa $siswa) {
            $path = $this->qr->generateForSiswa($siswa);

            return [
                'nama' => $siswa->nama_siswa,
                'nomor' => $siswa->nis,
                'nomor_label' => 'NIS',
                'kelas' => '',
                'qr_url' => asset('storage/'.$path),
            ];
        });

        return view('qr.print', [
            'title' => 'Cetak QR Siswa - '.$kelas->tingkat.' '.$kelas->index_kelas,
            'type' => 'siswa',
            'groupInfo' => "Kelas: {$kelas->tingkat} {$kelas->jurusan?->jurusan} {$kelas->index_kelas} - {$items->count()} Siswa",
            'items' => $items,
        ]);
    }

    public function siswaSingle(Siswa $siswa): View|Response
    {
        $kelas = $this->waliKelas->resolveForUser(auth()->user());

        if (! $kelas || $siswa->id_kelas !== $kelas->id_kelas) {
            abort(403, 'Siswa ini bukan bagian dari kelas Anda.');
        }

        $path = $this->qr->generateForSiswa($siswa);

        return view('qr.print', [
            'title' => 'Cetak QR - '.$siswa->nama_siswa,
            'type' => 'siswa',
            'groupInfo' => "{$siswa->nama_siswa} (NIS: {$siswa->nis})",
            'items' => [[
                'nama' => $siswa->nama_siswa,
                'nomor' => $siswa->nis,
                'nomor_label' => 'NIS',
                'kelas' => '',
                'qr_url' => asset('storage/'.$path),
            ]],
        ]);
    }
}
