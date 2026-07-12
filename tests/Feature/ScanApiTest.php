<?php

namespace Tests\Feature;

use App\Models\GeneralSetting;
use App\Models\Guru;
use App\Models\Jurusan;
use App\Models\Kehadiran;
use App\Models\Kelas;
use App\Models\PresensiGuru;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScanApiTest extends TestCase
{
    use RefreshDatabase;

    private function seedBase(): Kelas
    {
        foreach (['Hadir', 'Sakit', 'Izin', 'Tanpa keterangan'] as $i => $name) {
            Kehadiran::create(['id_kehadiran' => $i + 1, 'kehadiran' => $name]);
        }
        $jurusan = Jurusan::create(['jurusan' => 'RPL']);
        GeneralSetting::create(['jam_masuk_limit' => '07:00:00', 'jam_pulang_standard' => '14:00:00', 'hari_kerja' => '1,2,3,4,5']);

        return Kelas::create(['tingkat' => 'X', 'id_jurusan' => $jurusan->id, 'index_kelas' => 'A']);
    }

    public function test_scan_bootstrap_requires_auth(): void
    {
        $this->getJson('/api/scan/bootstrap')->assertStatus(401);
    }

    public function test_scan_bootstrap_returns_roster_for_authenticated_user(): void
    {
        $kelas = $this->seedBase();
        $siswa = Siswa::create(['nis' => '1', 'nama_siswa' => 'A', 'id_kelas' => $kelas->id_kelas, 'jenis_kelamin' => 'Laki-laki', 'no_hp' => '08']);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/scan/bootstrap');

        $response->assertOk();
        $response->assertJsonPath('roster.0.unique_code', $siswa->unique_code);
    }

    public function test_scan_check_in_then_duplicate_then_check_out(): void
    {
        $kelas = $this->seedBase();
        $siswa = Siswa::create(['nis' => '2', 'nama_siswa' => 'B', 'id_kelas' => $kelas->id_kelas, 'jenis_kelamin' => 'Perempuan', 'no_hp' => '08']);
        $user = User::factory()->create();

        $checkIn = $this->actingAs($user)->postJson('/api/scan', [
            'unique_code' => $siswa->unique_code,
            'waktu' => 'masuk',
        ]);
        $checkIn->assertOk();
        $checkIn->assertJsonPath('status', true);

        $duplicate = $this->actingAs($user)->postJson('/api/scan', [
            'unique_code' => $siswa->unique_code,
            'waktu' => 'masuk',
        ]);
        $duplicate->assertStatus(409);

        $checkOut = $this->actingAs($user)->postJson('/api/scan', [
            'unique_code' => $siswa->unique_code,
            'waktu' => 'pulang',
        ]);
        $checkOut->assertOk();
        $checkOut->assertJsonPath('status', true);
    }

    public function test_scan_with_past_scanned_at_records_historical_time_not_now(): void
    {
        $kelas = $this->seedBase();
        $guru = Guru::create(['nuptk' => '1', 'nama_guru' => 'C', 'jenis_kelamin' => 'Laki-laki', 'alamat' => 'x', 'no_hp' => '08']);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/scan', [
            'unique_code' => $guru->unique_code,
            'waktu' => 'masuk',
            'scanned_at' => now()->subDay()->setTime(6, 45)->toIso8601String(),
        ]);

        $response->assertOk();
        $presensi = PresensiGuru::where('id_guru', $guru->id_guru)->first();
        $this->assertNotNull($presensi);
        $this->assertSame(now()->subDay()->toDateString(), $presensi->tanggal->toDateString());
        $this->assertSame('06:45:00', $presensi->jam_masuk);
    }

    public function test_scan_rejects_unknown_code(): void
    {
        $this->seedBase();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/scan', [
            'unique_code' => 'does-not-exist',
            'waktu' => 'masuk',
        ]);

        $response->assertStatus(404);
    }
}
