<?php

namespace Database\Seeders;

use App\Models\Jurusan;
use App\Models\Kelas;
use Illuminate\Database\Seeder;

class KelasSeeder extends Seeder
{
    public function run(): void
    {
        $jurusanIds = Jurusan::orderBy('id')->pluck('id');

        foreach (['X', 'XI', 'XII'] as $tingkat) {
            foreach ($jurusanIds as $idJurusan) {
                Kelas::firstOrCreate([
                    'tingkat' => $tingkat,
                    'id_jurusan' => $idJurusan,
                    'index_kelas' => 'A',
                ]);
            }
        }
    }
}
