import Dexie from 'dexie';

/**
 * Offline data store for the scanner PWA:
 * - `roster`: a local mirror of siswa+guru unique_code/rfid_code -> identity,
 *   synced from GET /api/scan/bootstrap, so a scan can be validated locally
 *   even with no network connection.
 * - `pendingScans`: scans made while offline, queued here with the client
 *   timestamp they actually happened at, flushed to the server once
 *   connectivity returns (via Background Sync when supported, or a
 *   foreground retry loop otherwise).
 * - `settings`: cached general_settings fields (jam_masuk_limit etc.) and
 *   today's holiday status, needed to show a sensible offline UI.
 */
export const db = new Dexie('absensi-scanner');

db.version(1).stores({
    roster: 'unique_code, rfid_code, id, type',
    pendingScans: '++localId, unique_code, waktu, scanned_at, synced',
    settings: 'key',
});

export async function saveBootstrap({ roster, settings, today, holiday_reason }) {
    await db.transaction('rw', db.roster, db.settings, async () => {
        await db.roster.clear();
        await db.roster.bulkPut(roster);
        await db.settings.put({ key: 'general', value: settings });
        await db.settings.put({ key: 'today', value: today });
        await db.settings.put({ key: 'holiday_reason', value: holiday_reason ?? null });
    });
}

export async function findInRoster(code) {
    const byUniqueCode = await db.roster.where('unique_code').equals(code).first();
    if (byUniqueCode) return byUniqueCode;

    return await db.roster.where('rfid_code').equals(code).first();
}

export async function getSetting(key) {
    const row = await db.settings.get(key);
    return row?.value ?? null;
}

export async function queueScan({ unique_code, waktu }) {
    return db.pendingScans.add({
        unique_code,
        waktu,
        scanned_at: new Date().toISOString(),
        synced: 0,
    });
}

export async function getPendingScans() {
    return db.pendingScans.where('synced').equals(0).toArray();
}

export async function markScanSynced(localId) {
    return db.pendingScans.update(localId, { synced: 1 });
}

export async function pendingScanCount() {
    return db.pendingScans.where('synced').equals(0).count();
}
