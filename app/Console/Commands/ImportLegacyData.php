<?php

namespace App\Console\Commands;

use App\Models\GeneralSetting;
use App\Models\Guru;
use App\Models\User;
use App\Support\MysqlBinaryLocator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\Process\Process;

/**
 * One-time import of a legacy CI4 app's db_absensi.sql dump into this
 * app's schema (migration plan §2/§3 Phase 3). Since both schemas share
 * the same table/column names by design, most tables copy straight
 * across preserving primary keys — the one real translation is `users`:
 * the old schema splits email/password into a separate `auth_identities`
 * (Shield) table (type=email_password: secret=email, secret2=password
 * hash), so those get joined and merged onto the new `users` row here.
 *
 * The dump is staged into a throwaway MySQL database (via the `mysql`
 * CLI, same binary-resolution helper as BackupService) so it can be
 * queried with the query builder instead of hand-parsing SQL text.
 */
class ImportLegacyData extends Command
{
    protected $signature = 'absensi:import-legacy
        {path : Path to the legacy app\'s db_absensi.sql dump}
        {--force : Import even if target tables already contain data (no dedup — may throw on PK collisions)}
        {--keep-temp-db : Do not drop the temporary staging database afterward, for debugging}';

    protected $description = "Import a legacy CodeIgniter app's db_absensi.sql dump into this app's database";

    private const TEMP_CONNECTION = 'legacy_import';

    /** @var array<int, int> maps legacy users.id => new users.id */
    private array $userIdMap = [];

    public function handle(): int
    {
        $path = $this->argument('path');

        if (config('database.default') !== 'mysql') {
            $this->error('This command requires the mysql driver (it stages the dump via the mysql CLI). Set DB_CONNECTION=mysql and try again.');

            return self::FAILURE;
        }

        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $freshInstall = ! $this->targetTablesHaveData();

        if (! $freshInstall && ! $this->option('force')) {
            $this->error('Target tables already contain data. This command is meant for onboarding a fresh install from a legacy dump, not merging into an active dataset (no dedup logic). Re-run with --force only if you understand the risk of duplicate/colliding rows.');

            return self::FAILURE;
        }

        $tempDatabase = 'absensi_legacy_import_'.now()->format('YmdHis');

        $this->info("Staging dump into temporary database `{$tempDatabase}`...");
        $this->createTempDatabase($tempDatabase);

        try {
            $this->loadDumpIntoTempDatabase($tempDatabase, $path);
            $this->registerTempConnection($tempDatabase);

            DB::transaction(function () use ($freshInstall): void {
                if ($freshInstall) {
                    // Jurusan/Kelas are always non-empty on a fresh install
                    // (RolesAndPermissionsSeeder's siblings pre-seed 4
                    // placeholder jurusan + 12 placeholder kelas) — safe to
                    // clear here since nothing else in a fresh install can
                    // reference them yet, and it lets the legacy IDs land
                    // without colliding with the placeholder ones.
                    DB::table('tb_kelas')->delete();
                    DB::table('tb_jurusan')->delete();
                }

                $this->importJurusan();
                $this->importGuru();
                $this->importKelas();
                $this->importSiswa();
                $this->importHariLibur();
                $this->importPresensiGuru();
                $this->importPresensiSiswa();
                $this->importUsers();
                $this->importPerizinan();
                $this->importAuditLogs();
                $this->importGeneralSettings();
            });

            $this->info('Legacy data imported successfully.');
        } finally {
            if (! $this->option('keep-temp-db')) {
                $this->dropTempDatabase($tempDatabase);
            } else {
                $this->warn("Temporary database `{$tempDatabase}` kept for inspection — drop it manually when done.");
            }
        }

        return self::SUCCESS;
    }

    /**
     * Deliberately excludes tb_jurusan/tb_kelas — a fresh install always
     * has the seeder's placeholder jurusan/kelas rows, so their presence
     * doesn't indicate real usage the way any of these other tables do.
     */
    private function targetTablesHaveData(): bool
    {
        $tables = [
            'tb_guru', 'tb_siswa', 'tb_hari_libur',
            'tb_presensi_guru', 'tb_presensi_siswa', 'tb_perizinan',
        ];

        foreach ($tables as $table) {
            if (DB::table($table)->exists()) {
                return true;
            }
        }

        return false;
    }

    private function createTempDatabase(string $database): void
    {
        DB::statement("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    private function dropTempDatabase(string $database): void
    {
        DB::statement("DROP DATABASE IF EXISTS `{$database}`");
    }

    private function loadDumpIntoTempDatabase(string $database, string $dumpPath): void
    {
        $config = config('database.connections.'.config('database.default'));

        $process = new Process([
            MysqlBinaryLocator::binary('mysql'),
            '--host='.$config['host'],
            '--port='.$config['port'],
            '--user='.$config['username'],
            '--password='.$config['password'],
            '--ssl-mode=DISABLED',
            $database,
        ]);

        $process->setInput(fopen($dumpPath, 'r'));
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('Gagal memuat dump legacy: '.$process->getErrorOutput());
        }
    }

    private function registerTempConnection(string $database): void
    {
        $base = config('database.connections.'.config('database.default'));

        config(['database.connections.'.self::TEMP_CONNECTION => array_merge($base, [
            'database' => $database,
        ])]);

        DB::purge(self::TEMP_CONNECTION);
    }

    private function legacy(string $table)
    {
        return DB::connection(self::TEMP_CONNECTION)->table($table);
    }

    private function importJurusan(): void
    {
        $rows = $this->legacy('tb_jurusan')->get();

        foreach ($rows as $row) {
            DB::table('tb_jurusan')->insert([
                'id' => $row->id,
                'jurusan' => $row->jurusan,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
                'deleted_at' => $row->deleted_at,
            ]);
        }

        $this->info(" - Jurusan: {$rows->count()} imported");
    }

    private function importGuru(): void
    {
        $rows = $this->legacy('tb_guru')->get();

        foreach ($rows as $row) {
            DB::table('tb_guru')->insert([
                'id_guru' => $row->id_guru,
                'nuptk' => $row->nuptk,
                'nama_guru' => $row->nama_guru,
                'jenis_kelamin' => $row->jenis_kelamin,
                'alamat' => $row->alamat,
                'no_hp' => $row->no_hp,
                'unique_code' => $row->unique_code,
                'rfid_code' => $row->rfid_code,
                // Legacy tb_guru has no timestamp columns at all.
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->info(" - Guru: {$rows->count()} imported");
    }

    private function importKelas(): void
    {
        $rows = $this->legacy('tb_kelas')->get();

        foreach ($rows as $row) {
            DB::table('tb_kelas')->insert([
                'id_kelas' => $row->id_kelas,
                'tingkat' => $row->tingkat,
                'id_jurusan' => $row->id_jurusan,
                'index_kelas' => $row->index_kelas,
                'id_wali_kelas' => $row->id_wali_kelas,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
                'deleted_at' => $row->deleted_at,
            ]);
        }

        $this->info(" - Kelas: {$rows->count()} imported");
    }

    private function importSiswa(): void
    {
        $rows = $this->legacy('tb_siswa')->get();

        foreach ($rows as $row) {
            DB::table('tb_siswa')->insert([
                'id_siswa' => $row->id_siswa,
                'nis' => $row->nis,
                'nama_siswa' => $row->nama_siswa,
                'id_kelas' => $row->id_kelas,
                'jenis_kelamin' => $row->jenis_kelamin,
                'no_hp' => $row->no_hp,
                'poin_pelanggaran' => $row->poin_pelanggaran,
                'unique_code' => $row->unique_code,
                'rfid_code' => $row->rfid_code,
                // Legacy tb_siswa has no timestamp columns at all.
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->info(" - Siswa: {$rows->count()} imported");
    }

    private function importHariLibur(): void
    {
        $rows = $this->legacy('tb_hari_libur')->get();

        foreach ($rows as $row) {
            DB::table('tb_hari_libur')->insert([
                'id' => $row->id,
                'tanggal' => $row->tanggal,
                'keterangan' => $row->keterangan,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }

        $this->info(" - Hari Libur: {$rows->count()} imported");
    }

    private function importPresensiGuru(): void
    {
        $rows = $this->legacy('tb_presensi_guru')->get();

        foreach ($rows as $row) {
            DB::table('tb_presensi_guru')->insert([
                'id_presensi' => $row->id_presensi,
                'id_guru' => $row->id_guru,
                'tanggal' => $row->tanggal,
                'jam_masuk' => $row->jam_masuk,
                'jam_keluar' => $row->jam_keluar,
                'id_kehadiran' => $row->id_kehadiran,
                'keterangan' => $row->keterangan,
            ]);
        }

        $this->info(" - Presensi Guru: {$rows->count()} imported");
    }

    private function importPresensiSiswa(): void
    {
        $rows = $this->legacy('tb_presensi_siswa')->get();

        foreach ($rows as $row) {
            DB::table('tb_presensi_siswa')->insert([
                'id_presensi' => $row->id_presensi,
                'id_siswa' => $row->id_siswa,
                'id_kelas' => $row->id_kelas,
                'tanggal' => $row->tanggal,
                'jam_masuk' => $row->jam_masuk,
                'jam_keluar' => $row->jam_keluar,
                'id_kehadiran' => $row->id_kehadiran,
                // The legacy schema never recorded per-scan lateness on this
                // table — only a running total on tb_siswa.poin_pelanggaran
                // (preserved separately in importSiswa()) — so there is
                // nothing to backfill here.
                'menit_keterlambatan' => 0,
                'keterangan' => $row->keterangan,
            ]);
        }

        $this->info(" - Presensi Siswa: {$rows->count()} imported");
    }

    /**
     * Old schema splits email/password into `auth_identities`
     * (type=email_password: secret=email, secret2=password hash) rather
     * than storing them on `users` directly. Both frameworks hash with
     * PHP's password_hash()/password_verify() (bcrypt by default), so a
     * copied hash lets legacy users log in with their existing password
     * unchanged — no reset required, unless no identity is found.
     *
     * IDs are intentionally NOT preserved here (unlike every other
     * table): a fresh install already has a seeded superadmin at id=1,
     * so new users get fresh auto-increment IDs and every FK that
     * pointed at a legacy user.id gets remapped through $userIdMap.
     */
    private function importUsers(): void
    {
        $legacyUsers = $this->legacy('users')->whereNull('deleted_at')->get();
        $identities = $this->legacy('auth_identities')
            ->where('type', 'email_password')
            ->get()
            ->keyBy('user_id');
        $groups = $this->legacy('auth_groups_users')->get()->groupBy('user_id');
        $directPermissions = $this->legacy('auth_permissions_users')->get()->groupBy('user_id');

        $knownRoles = Role::pluck('name')->all();
        $knownPermissions = Permission::pluck('name')->all();

        $created = 0;
        $skippedDuplicate = 0;
        $skippedNoPassword = [];

        foreach ($legacyUsers as $row) {
            $identity = $identities->get($row->id);
            $email = $identity->secret ?? ($row->username ? "{$row->username}@legacy.local" : null);

            if (! $email) {
                $this->warn(" - Skipping legacy user id {$row->id}: no email or username to key on.");

                continue;
            }

            $existing = DB::table('users')->where('email', $email)->first();
            if ($existing) {
                $this->userIdMap[$row->id] = $existing->id;
                $skippedDuplicate++;

                continue;
            }

            $name = $row->id_guru
                ? (Guru::find($row->id_guru)?->nama_guru ?? $row->username ?? $email)
                : ($row->username ?? $email);

            if (! $identity) {
                $skippedNoPassword[] = $email;
            }

            $newId = DB::table('users')->insertGetId([
                'id_guru' => $row->id_guru,
                'name' => $name,
                'email' => $email,
                'password' => $identity->secret2 ?? Hash::make(str()->random(40)),
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);

            $this->userIdMap[$row->id] = $newId;
            $created++;

            $user = User::find($newId);

            $rolesForUser = array_values(array_intersect(
                $groups->get($row->id, collect())->pluck('group')->all(),
                $knownRoles
            ));
            if ($rolesForUser) {
                $user->syncRoles($rolesForUser);
            }

            $permissionsForUser = array_values(array_intersect(
                $directPermissions->get($row->id, collect())->pluck('permission')->all(),
                $knownPermissions
            ));
            if ($permissionsForUser) {
                $user->syncPermissions($permissionsForUser);
            }
        }

        $this->info(" - Users: {$created} imported, {$skippedDuplicate} matched an existing account by email");

        if ($skippedNoPassword) {
            $this->warn(' - No email_password identity found for: '.implode(', ', $skippedNoPassword).' — given a random password, they will need a password reset.');
        }
    }

    private function importPerizinan(): void
    {
        $rows = $this->legacy('tb_perizinan')->get();

        foreach ($rows as $row) {
            DB::table('tb_perizinan')->insert([
                'id_perizinan' => $row->id_perizinan,
                'id_siswa' => $row->id_siswa,
                'id_guru' => $row->id_guru,
                'tanggal_mulai' => $row->tanggal_mulai,
                'tanggal_selesai' => $row->tanggal_selesai,
                'tipe_izin' => $row->tipe_izin,
                'alasan' => $row->alasan,
                'bukti' => $row->bukti,
                'status' => $row->status,
                'id_petugas' => $row->id_petugas ? ($this->userIdMap[$row->id_petugas] ?? null) : null,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }

        $this->info(" - Perizinan: {$rows->count()} imported");
    }

    private function importAuditLogs(): void
    {
        $rows = $this->legacy('tb_audit_logs')->get();

        foreach ($rows as $row) {
            DB::table('tb_audit_logs')->insert([
                'id' => $row->id,
                'id_user' => $row->id_user ? ($this->userIdMap[$row->id_user] ?? null) : null,
                'aksi' => $row->aksi,
                'tabel' => $row->tabel,
                'id_record' => $row->id_record,
                'data_lama' => $row->data_lama,
                'data_baru' => $row->data_baru,
                'ip_address' => $row->ip_address,
                'created_at' => $row->created_at,
            ]);
        }

        $this->info(" - Audit log: {$rows->count()} imported");
    }

    private function importGeneralSettings(): void
    {
        $row = $this->legacy('general_settings')->first();

        if (! $row) {
            return;
        }

        GeneralSetting::query()->updateOrCreate(['id' => 1], [
            'logo' => $row->logo,
            'school_name' => $row->school_name,
            'school_year' => $row->school_year,
            'jam_masuk_limit' => $row->jam_masuk_limit,
            'jam_pulang_standard' => $row->jam_pulang_standard,
            'hari_kerja' => $row->hari_kerja,
            'copyright' => $row->copyright,
        ]);

        $this->info(' - General settings: imported');
    }
}
