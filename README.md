# Absensi Sekolah (Laravel + Filament + Vue)

A school attendance system: QR/RFID scan-based check-in/out, teacher (wali kelas) and admin dashboards, digital izin/sakit requests, WhatsApp notifications, and an installable offline-capable PWA scanner kiosk.

This is a rebuild of a CodeIgniter 4 application (`../absensi-sekolah`) as Laravel 12 + Filament 3 + Vue 3, keeping the original Indonesian database schema (`tb_siswa`, `tb_guru`, `unique_code`, etc.) so existing production data can be imported directly (see [MIGRATION_GUIDE.md](MIGRATION_GUIDE.md)). Every feature from the old app is preserved, plus a real offline-first scanner (the old app's PWA was a non-functional stub) and simpler single-container deployment (see [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md)).

## Features

- **Admin panel** (`/admin`) — students, teachers, classes/majors, staff accounts & roles, holidays, leave-request approval queue, attendance edit with audit trail, QR code generation/printing, PDF reports, database/file backup & restore, general settings, activity log.
- **Teacher panel** (`/teacher`) — wali kelas (homeroom teacher) dashboard scoped to their own class only: attendance edit, QR download/print, monthly report, leave-request approval.
- **Scanner kiosk** (`/scan`, auth required) — camera (QR) or RFID-reader check-in/out, works fully offline (IndexedDB roster cache + queued scans, auto-sync on reconnect).
- **Public portals** — `/izin` (submit a leave/sick request with proof photo) and `/cek-kehadiran` (look up attendance history by NIS + phone number).
- **WhatsApp notifications** on check-in/out via Fonnte (optional, env-gated).

## Requirements

- PHP 8.2+, Composer
- Node.js 18+ (for the Vite/Vue build)
- MySQL 8 (recommended) or SQLite (zero-config, single-device/small deployments — see [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md))

## Local setup

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate
# Edit .env: DB_* credentials, SANCTUM_STATEFUL_DOMAINS (must match the host/port
# you serve the app from, or /api/scan/* will 401 — see the comment in .env.example)

php artisan migrate --seed
npm run build   # or `npm run dev` / `composer run dev` while developing

php artisan serve
```

Default superadmin login: `adminsuper@gmail.com` / `superadmin` (change this immediately in production).

Migrating real data from the old CodeIgniter app instead of starting from the seeded defaults? See [MIGRATION_GUIDE.md](MIGRATION_GUIDE.md).

## Testing

```bash
php artisan test      # PHPUnit — uses SQLite in-memory, no MySQL required
vendor/bin/pint --test   # style check
```

## Deployment

See [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) for the Docker/FrankenPHP setup (single container, automatic HTTPS) and the SQLite zero-config option for small/single-device installs.

## Project structure

- `app/Filament/` — Admin panel (Resources/Pages) and Teacher panel (`app/Filament/Teacher/`)
- `app/Http/Controllers/Api/` — JSON endpoints backing the Vue PWA (`/api/scan`, `/api/izin`, `/api/cek-kehadiran`)
- `app/Services/` — business logic shared between panels/API (attendance, leave approval, QR, reports, backup, audit log, WhatsApp)
- `resources/js/pwa/` — the Vue 3 + Vite scanner/portal PWA
- `app/Console/Commands/ImportLegacyData.php` — one-time import from the old app's `db_absensi.sql` dump (see MIGRATION_GUIDE.md)
