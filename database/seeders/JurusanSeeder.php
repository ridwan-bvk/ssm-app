<?php

namespace Database\Seeders;

use App\Models\Jurusan;
use Illuminate\Database\Seeder;

class JurusanSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['OTKP', 'BDP', 'AKL', 'RPL'] as $jurusan) {
            Jurusan::firstOrCreate(['jurusan' => $jurusan]);
        }
    }
}
