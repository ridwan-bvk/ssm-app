# Deployment

## Docker (FrankenPHP) — default option

A single container running FrankenPHP (PHP + Caddy in one binary), plus MySQL and phpMyAdmin via Compose:

```bash
cp .env.example .env
docker compose up -d --build
```

- App: `http://localhost:8080`
- phpMyAdmin: `http://localhost:8081`
- MySQL: `localhost:3306`

`docker-entrypoint.sh` waits for the database, generates `APP_KEY` if missing, runs `php artisan migrate --force`, and links the storage disk on every container start. Set real values for `DB_PASSWORD` and `APP_KEY` (or let the entrypoint generate one) before running this anywhere but a local machine — the compose file's defaults (`changeme`) are placeholders only.

**In production, also set `SANCTUM_STATEFUL_DOMAINS`** to whatever domain(s)/port(s) actually serve the app — the scanner kiosk (`/scan`) and public portals (`/izin`, `/cek-kehadiran`) authenticate through Sanctum's session-based "stateful" mode, and a mismatch here causes `/api/scan/*` to 401 despite a valid admin session, with no other visible error.

> **Environment note:** the Docker setup has been reviewed line-by-line (including fixing a `--skip-ssl` vs `--ssl-mode=DISABLED` MySQL-client incompatibility in the entrypoint's DB-wait loop, and adding a `node:20-alpine` build stage that compiles `public/build/*` and the PWA service worker — both are gitignored, so without that stage a fresh clone would build an image with no CSS/JS at all), but has **not been `docker build`-tested end-to-end**, since Docker isn't available in this development environment. Build and smoke-test it before relying on it for a real deployment.

## SQLite (zero-config, small/single-device installs)

For a school running the app on a single device with light usage, MySQL is unnecessary. Laravel abstracts the driver, so switching is a `.env` change with no code changes:

```env
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database.sqlite
```

```bash
touch database/database.sqlite
php artisan migrate --seed
```

Notes:
- `php artisan absensi:import-legacy` (see [MIGRATION_GUIDE.md](../MIGRATION_GUIDE.md)) requires MySQL — it stages the legacy dump via the `mysql` CLI. Import legacy data on MySQL first, then switch to SQLite afterward if desired, or start fresh with SQLite via the normal seeders.
- The automated test suite already runs against SQLite in CI, so the schema and business logic (including cross-database date-comparison handling) are verified there.

## Backup & restore

Use the admin panel's Backup page (`/admin/backup`) rather than shelling out manually — it wraps `spatie/db-dumper` (dump) and a piped `mysql` restore, both via `MYSQL_BIN_PATH` if the binaries aren't on PATH (common on Windows/Laragon/XAMPP dev setups; set it in `.env`). File uploads (`storage/app/public`) are backed up/restored as a separate zip from the same page.

## CI

`.github/workflows/ci.yml` runs the test suite against SQLite and a Pint style check on every push — no external services required.
