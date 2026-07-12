<?php

namespace Database\Seeders;

use App\Models\Kehadiran;
use Illuminate\Database\Seeder;

class KehadiranSeeder extends Seeder
{
    public function run(): void
    {
        if (Kehadiran::count() > 0) {
            return;
        }

        $statuses = [
            ['id_kehadiran' => 1, 'kehadiran' => 'Hadir'],
            ['id_kehadiran' => 2, 'kehadiran' => 'Sakit'],
            ['id_kehadiran' => 3, 'kehadiran' => 'Izin'],
            ['id_kehadiran' => 4, 'kehadiran' => 'Tanpa keterangan'],
        ];

        foreach ($statuses as $status) {
            Kehadiran::create($status);
        }
    }
}
