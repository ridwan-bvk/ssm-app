<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Order mirrors app/Database/Seeds/DatabaseSeeder.php from the CI4 app.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            KehadiranSeeder::class,
            JurusanSeeder::class,
            KelasSeeder::class,
            SuperadminSeeder::class,
            GeneralSettingsSeeder::class,
        ]);
    }
}
