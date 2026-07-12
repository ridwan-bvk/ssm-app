<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Services\QrService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Mirrors the printQrSiswa/printQrGuru/printQrSiswaSingle/printQrGuruSingle
 * actions on Admin\QRGenerator from the CI4 app: a standalone (no panel
 * chrome) 4-column print view, generating QR files on the fly to guarantee
 * they're up to date.
 */
class QrPrintController extends Controller
{
    public function __construct(private readonly QrService $qr) {}

    public function siswa(Request $request): View
    {
        $idKelas = $request->query('id_kelas');
        $kelas = $idKelas ? Kelas::with('jurusan')->find($idKelas) : null;
        $siswaList = $idKelas ? Siswa::where('id_kelas', $idKelas)->orderBy('nama_siswa')->get() : Siswa::with('kelas.jurusan')->orderBy('nama_siswa')->get();

        $items = $siswaList->map(function (Siswa $siswa) {
            $path = $this->qr->generateForSiswa($siswa);

            return [
                'nama' => $siswa->nama_siswa,
                'nomor' => $siswa->nis,
                'nomor_label' => 'NIS',
                'kelas' => $siswa->kelas ? "{$siswa->kelas->tingkat} {$siswa->kelas->jurusan?->jurusan} {$siswa->kelas->index_kelas}" : '',
                'qr_url' => asset('storage/'.$path),
            ];
        });

        $groupInfo = ($kelas ? "Kelas: {$kelas->tingkat} {$kelas->jurusan?->jurusan} {$kelas->index_kelas}" : 'Semua Kelas').' - '.$items->count().' Siswa';

        return view('qr.print', [
            'title' => 'Cetak QR Siswa',
            'type' => 'siswa',
            'groupInfo' => $groupInfo,
            'items' => $items,
        ]);
    }

    public function siswaSingle(Siswa $siswa): View
    {
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

    public function guru(): View
    {
        $items = Guru::orderBy('nama_guru')->get()->map(function (Guru $guru) {
            $path = $this->qr->generateForGuru($guru);

            return [
                'nama' => $guru->nama_guru,
                'nomor' => $guru->nuptk,
                'nomor_label' => 'NUPTK',
                'kelas' => '',
                'qr_url' => asset('storage/'.$path),
            ];
        });

        return view('qr.print', [
            'title' => 'Cetak QR Guru',
            'type' => 'guru',
            'groupInfo' => 'Semua Guru - '.$items->count().' Guru',
            'items' => $items,
        ]);
    }

    public function guruSingle(Guru $guru): View
    {
        $path = $this->qr->generateForGuru($guru);

        return view('qr.print', [
            'title' => 'Cetak QR - '.$guru->nama_guru,
            'type' => 'guru',
            'groupInfo' => "{$guru->nama_guru} (NUPTK: {$guru->nuptk})",
            'items' => [[
                'nama' => $guru->nama_guru,
                'nomor' => $guru->nuptk,
                'nomor_label' => 'NUPTK',
                'kelas' => '',
                'qr_url' => asset('storage/'.$path),
            ]],
        ]);
    }
}
