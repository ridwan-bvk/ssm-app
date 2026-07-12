<script setup>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue';
import { BrowserMultiFormatReader } from '@zxing/browser';
import api from '../api';
import { db, saveBootstrap, findInRoster, getSetting, queueScan, getPendingScans, markScanSynced, pendingScanCount } from '../db';

const mode = ref('masuk'); // 'masuk' | 'pulang'
const useCamera = ref(true);
const cameras = ref([]);
const selectedCamera = ref(null);
const rfidFocused = ref(true);
const rfidValue = ref('');
const rfidInput = ref(null);
const videoEl = ref(null);

const isOnline = ref(navigator.onLine);
const pendingCount = ref(0);
const lastResult = ref(null); // { status, message, nama, nomor, type }
const holidayReason = ref(null);

let codeReader = null;
let scannerControls = null;
let lastDecodeAt = 0;

async function refreshPendingCount() {
    pendingCount.value = await pendingScanCount();
}

async function bootstrap() {
    try {
        const { data } = await api.get('/scan/bootstrap');
        await saveBootstrap(data);
        holidayReason.value = data.holiday_reason ?? null;
    } catch (e) {
        // Offline or request failed — fall back to whatever was cached
        // from the last successful bootstrap.
        holidayReason.value = await getSetting('holiday_reason');
    }
}

function playBeep() {
    const audio = new Audio('/assets/audio/beep.mp3');
    audio.play().catch(() => {});
}

/**
 * Every scanned code goes through here, whether it came from the camera
 * or the RFID reader. Mirrors Scan::cekKode()'s flow, but validates
 * against the local roster cache first so a result can be shown
 * immediately even offline, then tries the live API and falls back to
 * queuing locally if that fails.
 */
async function processCode(code) {
    playBeep();

    const person = await findInRoster(code);
    if (!person) {
        lastResult.value = { status: false, message: 'Data tidak ditemukan', code };
        return;
    }

    if (isOnline.value) {
        try {
            const { data } = await api.post('/scan', { unique_code: code, waktu: mode.value });
            lastResult.value = data;
            await flushPendingScans();
            return;
        } catch (e) {
            if (e.response && e.response.status !== 0) {
                // A real server-side rejection (already scanned, holiday, etc.)
                // — not an offline/network failure, so surface it directly
                // rather than silently queuing a scan that will just fail
                // again the same way when synced.
                lastResult.value = e.response.data;
                return;
            }
            // Network failure even though navigator.onLine said we were
            // online (flaky connection) — fall through to offline queueing.
        }
    }

    await queueScan({ unique_code: code, waktu: mode.value });
    await refreshPendingCount();
    lastResult.value = {
        status: true,
        offline: true,
        message: `Tersimpan offline (akan disinkronkan) — ${mode.value}`,
        nama: person.nama,
        nomor: person.nomor,
        type: person.type,
    };
}

async function flushPendingScans() {
    const pending = await getPendingScans();
    let syncedCount = 0;

    for (const scan of pending) {
        try {
            await api.post('/scan', {
                unique_code: scan.unique_code,
                waktu: scan.waktu,
                scanned_at: scan.scanned_at,
            });
            await markScanSynced(scan.localId);
            syncedCount++;
        } catch (e) {
            if (e.response) {
                // Server processed it and rejected it (e.g. duplicate) —
                // still mark as synced so it doesn't retry forever.
                await markScanSynced(scan.localId);
                syncedCount++;
            } else {
                // Still offline — stop and try again next time.
                break;
            }
        }
    }

    await refreshPendingCount();

    if (syncedCount > 0) {
        lastResult.value = {
            status: true,
            message: `${syncedCount} scan offline berhasil disinkronkan`,
        };
    }
}

function handleOnline() {
    isOnline.value = true;
    bootstrap().then(flushPendingScans);
}

function handleOffline() {
    isOnline.value = false;
}

async function startCamera() {
    if (!codeReader) codeReader = new BrowserMultiFormatReader();

    try {
        const devices = await BrowserMultiFormatReader.listVideoInputDevices();
        cameras.value = devices;
        if (!selectedCamera.value && devices.length) {
            selectedCamera.value = devices[0].deviceId;
        }
    } catch (e) {
        // Camera enumeration can fail without permission yet — decodeFromVideoDevice
        // below will prompt for it.
    }

    if (!videoEl.value) return;

    try {
        // Continuous decode: the callback fires on every attempt (successful
        // or not), so debounce on our side to avoid re-processing the same
        // code every ~100ms while it's still in frame.
        scannerControls = await codeReader.decodeFromVideoDevice(selectedCamera.value ?? undefined, videoEl.value, (result) => {
            if (!result) return;

            const now = Date.now();
            if (now - lastDecodeAt < 2500) return;
            lastDecodeAt = now;

            processCode(result.getText());
        });
    } catch (e) {
        // No camera available/permitted — fall back to RFID mode so the
        // kiosk is still usable.
        console.warn('Camera unavailable, falling back to RFID input:', e);
        useCamera.value = false;
        focusRfid();
    }
}

function stopCamera() {
    scannerControls?.stop();
    scannerControls = null;
}

function toggleCamera() {
    useCamera.value = !useCamera.value;
    if (useCamera.value) {
        startCamera();
    } else {
        stopCamera();
        focusRfid();
    }
}

function focusRfid() {
    rfidInput.value?.focus();
}

function onRfidKeypress(e) {
    if (e.key === 'Enter') {
        const code = rfidValue.value.trim();
        rfidValue.value = '';
        if (code) processCode(code);
    }
}

const statusLabel = computed(() => (isOnline.value ? 'Online' : 'Offline — scan tetap tersimpan'));

onMounted(async () => {
    await bootstrap();
    await refreshPendingCount();
    await flushPendingScans();

    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);

    if (useCamera.value) startCamera();

    document.addEventListener('click', (e) => {
        if (!useCamera.value && !e.target.closest('.camera-controls')) focusRfid();
    });
});

onBeforeUnmount(() => {
    stopCamera();
    window.removeEventListener('online', handleOnline);
    window.removeEventListener('offline', handleOffline);
});
</script>

<template>
    <div class="mx-auto max-w-md p-4">
        <h1 class="text-xl font-bold text-center mb-2">Absensi Siswa &amp; Guru</h1>

        <div class="text-center text-sm mb-4" :class="isOnline ? 'text-green-600' : 'text-red-600'">
            {{ statusLabel }}
            <span v-if="pendingCount > 0" class="ml-2 text-amber-600">({{ pendingCount }} menunggu sinkronisasi)</span>
        </div>

        <div v-if="holidayReason" class="bg-red-100 text-red-700 p-3 rounded mb-4 text-center">
            Hari ini presensi dinonaktifkan: {{ holidayReason }}
        </div>

        <div class="flex justify-center gap-2 mb-4">
            <button
                class="px-4 py-2 rounded"
                :class="mode === 'masuk' ? 'bg-purple-600 text-white' : 'bg-gray-200'"
                @click="mode = 'masuk'"
            >
                Masuk
            </button>
            <button
                class="px-4 py-2 rounded"
                :class="mode === 'pulang' ? 'bg-purple-600 text-white' : 'bg-gray-200'"
                @click="mode = 'pulang'"
            >
                Pulang
            </button>
        </div>

        <div class="camera-controls text-center mb-2">
            <label class="text-sm">
                <input type="checkbox" :checked="useCamera" @change="toggleCamera" />
                Gunakan Kamera
            </label>
        </div>

        <div v-if="useCamera">
            <select v-if="cameras.length > 1" v-model="selectedCamera" class="w-full mb-2 border rounded p-1">
                <option v-for="c in cameras" :key="c.deviceId" :value="c.deviceId">{{ c.label || c.deviceId }}</option>
            </select>
            <video ref="videoEl" class="w-full rounded bg-black" muted playsinline></video>
        </div>

        <div v-else class="text-center">
            <p class="mb-2" :class="rfidFocused ? 'text-green-600' : 'text-red-600'">
                {{ rfidFocused ? 'Siap membaca RFID' : 'Tidak fokus — klik di sini' }}
            </p>
            <input
                ref="rfidInput"
                v-model="rfidValue"
                type="text"
                autocomplete="off"
                class="opacity-0 absolute -left-full"
                @keypress="onRfidKeypress"
                @focus="rfidFocused = true"
                @blur="rfidFocused = false"
            />
        </div>

        <div v-if="lastResult" class="mt-4 p-4 rounded" :class="lastResult.status ? 'bg-green-100' : 'bg-red-100'">
            <p class="font-semibold">{{ lastResult.message }}</p>
            <p v-if="lastResult.nama">{{ lastResult.nama }} ({{ lastResult.nomor }})</p>
        </div>
    </div>
</template>
