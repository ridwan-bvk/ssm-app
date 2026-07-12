<?php

namespace Database\Seeders;

use App\Models\GeneralSetting;
use Illuminate\Database\Seeder;

class GeneralSettingsSeeder extends Seeder
{
    public function run(): void
    {
        if (GeneralSetting::count() > 0) {
            return;
        }

        GeneralSetting::create([
            'school_name' => 'SMK 1 Indonesia',
            'school_year' => '2024/2025',
            'copyright' => '© 2025 All rights reserved.',
            'hari_kerja' => '1,2,3,4,5',
        ]);
    }
}
