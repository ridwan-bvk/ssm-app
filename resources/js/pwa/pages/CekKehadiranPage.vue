<script setup>
import { ref } from 'vue';
import api from '../api';

const nis = ref('');
const noHp = ref('');
const loading = ref(false);
const error = ref(null);
const result = ref(null);

async function check() {
    loading.value = true;
    error.value = null;
    result.value = null;

    try {
        const { data } = await api.post('/cek-kehadiran', { nis: nis.value, no_hp: noHp.value });
        result.value = data;
    } catch (e) {
        error.value = e.response?.data?.message ?? 'Terjadi kesalahan, silakan coba lagi.';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="mx-auto max-w-md p-4">
        <h1 class="text-xl font-bold text-center mb-4">Portal Cek Kehadiran Mandiri</h1>

        <div v-if="!result">
            <label class="block mb-2 text-sm">NIS
                <input v-model="nis" type="text" required class="w-full border rounded p-2" />
            </label>
            <label class="block mb-4 text-sm">Nomor HP (sesuai data terdaftar)
                <input v-model="noHp" type="text" required class="w-full border rounded p-2" />
            </label>
            <button class="w-full bg-purple-600 text-white py-2 rounded" :disabled="loading" @click="check">
                {{ loading ? 'Mencari...' : 'Cek Kehadiran' }}
            </button>
            <p v-if="error" class="text-red-600 text-sm mt-2">{{ error }}</p>
        </div>

        <div v-else>
            <div class="flex justify-between items-center mb-3">
                <h2 class="font-semibold">{{ result.siswa.nama_siswa }} ({{ result.siswa.nis }})</h2>
                <button class="text-sm text-red-600" @click="result = null">Cek Lain</button>
            </div>

            <h3 class="font-medium mb-2">Ringkasan {{ result.month_name }}</h3>
            <div class="grid grid-cols-4 gap-2 text-center mb-4">
                <div class="bg-green-100 rounded p-2"><div class="font-bold">{{ result.stats.hadir }}</div><div class="text-xs">Hadir</div></div>
                <div class="bg-yellow-100 rounded p-2"><div class="font-bold">{{ result.stats.sakit }}</div><div class="text-xs">Sakit</div></div>
                <div class="bg-blue-100 rounded p-2"><div class="font-bold">{{ result.stats.izin }}</div><div class="text-xs">Izin</div></div>
                <div class="bg-red-100 rounded p-2"><div class="font-bold">{{ result.stats.alfa }}</div><div class="text-xs">Alfa</div></div>
            </div>

            <h3 class="font-medium mb-2">Riwayat Tahun Ini</h3>
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="border-b">
                        <th class="text-left p-1">Tanggal</th>
                        <th class="text-left p-1">Status</th>
                        <th class="text-left p-1">Masuk</th>
                        <th class="text-left p-1">Keluar</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="h in result.history" :key="h.tanggal" class="border-b">
                        <td class="p-1">{{ h.tanggal }}</td>
                        <td class="p-1">{{ h.kehadiran ?? 'Alfa' }}</td>
                        <td class="p-1">{{ h.jam_masuk ?? '-' }}</td>
                        <td class="p-1">{{ h.jam_keluar ?? '-' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
