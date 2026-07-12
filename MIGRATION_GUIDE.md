# Migrating from the old CodeIgniter 4 app

If a school is already running the old app (`../absensi-sekolah`) and wants to move to this one with its real data intact, use the `absensi:import-legacy` command instead of starting from the seeded defaults.

## What it does

Both schemas share the same table/column names by design (`tb_siswa`, `tb_guru`, `unique_code`, etc.), so most tables copy across directly, preserving their original primary keys:

| Old table | Imported into | Notes |
|---|---|---|
| `tb_jurusan`, `tb_kelas` | same | IDs preserved. The seeder's placeholder jurusan/kelas rows are cleared first (see "Fresh install only" below). |
| `tb_guru`, `tb_siswa` | same | IDs preserved, including `unique_code`/`rfid_code`/`poin_pelanggaran`. |
| `tb_hari_libur`, `tb_perizinan` | same | IDs preserved. `tb_perizinan.id_petugas` is remapped (see Users below). |
| `tb_presensi_guru`, `tb_presensi_siswa` | same | IDs preserved. The old schema never recorded per-scan lateness on `tb_presensi_siswa` (only a running total on `tb_siswa.poin_pelanggaran`, which *is* carried over) — the new `menit_keterlambatan` column is backfilled to `0` for historical rows. |
| `tb_audit_logs` | same | IDs preserved, `id_user` remapped (see Users below). |
| `general_settings` | same | Overwrites the seeded default row (id 1) with the school's real settings. |
| `users` + `auth_identities` + `auth_groups_users` + `auth_permissions_users` | `users` + Spatie roles/permissions | See below — this is the one real translation, not a direct copy. |
| `tb_kehadiran` | *(skipped)* | Already seeded with the same fixed 4 rows (Hadir/Sakit/Izin/Tanpa keterangan) both apps share. |
| `settings`, `factories`, `auth_logins`, `auth_token_logins`, `auth_remember_tokens`, `migrations` | *(skipped)* | Framework-internal tables (CodeIgniter session/login history, its own migration tracker) with no equivalent or purpose in the new app. |

### Users, roles, and passwords

The old schema keeps `users` separate from credentials: email and password hash actually live in `auth_identities` (type `email_password`: `secret` = email, `secret2` = password hash). Both frameworks hash passwords with PHP's `password_hash()`/`password_verify()` (bcrypt by default), so a copied hash lets legacy users log in with their **existing password, unchanged** — no reset required, unless no matching identity is found (that user gets a random password and is flagged in the command's output as needing a manual reset).

User IDs are **not** preserved — a fresh install already has a seeded superadmin at id 1, so imported users get fresh IDs, and every foreign key that pointed at a legacy `users.id` (`tb_perizinan.id_petugas`, `tb_audit_logs.id_user`) is remapped automatically. If a legacy user's email already matches an existing account (e.g. the seeded superadmin), the import links to that existing account instead of creating a duplicate. Legacy `auth_groups_users` rows become Spatie roles (`superadmin`/`admin`/`kepsek`/`scanner`/`guru` — same names, both sides); any legacy `auth_permissions_users` direct-grant rows are copied too, as long as the permission name still exists in the new permission set. Soft-deleted legacy users (`users.deleted_at` not null) are skipped.

## Usage

```bash
php artisan absensi:import-legacy /path/to/db_absensi.sql
```

Requires `DB_CONNECTION=mysql` (it stages the dump via the `mysql` CLI into a throwaway database on the same server, then reads it with the query builder — this doesn't work against SQLite). If `mysql`/`mysqldump` aren't on PATH, set `MYSQL_BIN_PATH` in `.env` (same setting `BackupService` uses).

### Fresh install only, by default

This command is meant for onboarding a **fresh install** — before any real siswa/guru/attendance data has been recorded. It checks that those tables are empty and refuses to run otherwise:

```
Target tables already contain data. This command is meant for onboarding a fresh
install from a legacy dump, not merging into an active dataset (no dedup logic).
Re-run with --force only if you understand the risk of duplicate/colliding rows.
```

Pass `--force` only if you understand the risk (no deduplication — colliding primary keys will throw and roll back the whole import, since it runs inside a transaction). On a genuine fresh install, the seeder's placeholder jurusan/kelas rows are cleared automatically before the legacy ones are imported, so their IDs don't collide.

`--keep-temp-db` keeps the throwaway staging database around afterward, for debugging a failed import.

### Verifying before cutover

Per the original migration plan: before decommissioning the old app, run both systems side-by-side against the same physical scan session for at least one full school day and diff the resulting attendance tables, and spot-check a handful of imported staff logins actually work with their existing password.

## Deliberately not changed

The old app's `general_settings.hari_kerja` (configured workdays) has never actually been consulted by scan-gating or reports — only `tb_hari_libur` (explicit holiday dates) is checked, so changing `hari_kerja` has no effect until someone manually regenerates weekend holidays. This rebuild keeps that exact behavior (a deliberate decision, not an oversight) so no real school's attendance numbers change as a side effect of migrating. Revisit this separately if you want `hari_kerja` to become the actual source of truth.
