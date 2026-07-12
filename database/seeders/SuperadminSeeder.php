<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Mirrors app/Database/Migrations/2023-08-18-000004_AddSuperadmin.php +
 * SuperadminSeeder.php from the CI4 app: same default credentials, so the
 * migration guide's login instructions still work against the new repo.
 */
class SuperadminSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'adminsuper@gmail.com'],
            ['name' => 'superadmin', 'password' => Hash::make('superadmin')],
        );

        $user->syncRoles(['superadmin', 'admin']);
    }
}
