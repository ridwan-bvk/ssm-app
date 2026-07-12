<script setup>
import { ref } from 'vue';
import api from '../api';

const type = ref('siswa');
const identifier = ref('');
const target = ref(null); // { id, nama }
const lookupError = ref(null);
const lookingUp = ref(false);

const form = ref({
    tanggal_mulai: '',
    tanggal_selesai: '',
    tipe_izin: 'Sakit',
    alasan: '',
});
const buktiFile = ref(null);
const submitting = ref(false);
const submitResult = ref(null);

async function lookup() {
    lookupError.value = null;
    target.value = null;
    lookingUp.value = true;

    try {
        const { data } = await api.post('/izin/lookup', { type: type.value, identifier: identifier.value });
        target.value = data.data;
    } catch (e) {
        lookupError.value = e.response?.data?.message ?? 'Terjadi kesalahan, silakan coba lagi.';
    } finally {
        lookingUp.value = false;
    }
}

function onFileChange(e) {
    buktiFile.value = e.target.files[0] ?? null;
}

async function submit() {
    if (!target.value) return;

    submitting.value = true;
    submitResult.value = null;

    const payload = new FormData();
    payload.append('type', type.value);
    payload.append('id_target', target.value.id);
    payload.append('tanggal_mulai', form.value.tanggal_mulai);
    payload.append('tanggal_selesai', form.value.tanggal_selesai);
    payload.append('tipe_izin', form.value.tipe_izin);
    payload.append('alasan', form.value.alasan);
    if (buktiFile.value) payload.append('bukti', buktiFile.value);

    try {
        const { data } = await api.post('/izin/submit', payload, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        submitResult.value = { success: true, message: data.message };
        target.value = null;
        identifier.value = '';
        form.value = { tanggal_mulai: '', tanggal_selesai: '', tipe_izin: 'Sakit', alasan: '' };
        buktiFile.value = null;
    } catch (e) {
        const errors = e.response?.data?.errors;
        const message = errors ? Object.values(errors).flat().join(' ') : (e.response?.data?.message ?? 'Gagal mengirim pengajuan.');
        submitResult.value = { success: false, message };
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <div class="mx-auto max-w-md p-4">
        <h1 class="text-xl font-bold text-center mb-4">Pengajuan Izin/Sakit Digital</h1>

        <div v-if="!target">
            <div class="flex gap-2 mb-3 justify-center">
                <label><input type="radio" value="siswa" v-model="type" /> Siswa</label>
                <label><input type="radio" value="guru" v-model="type" /> Guru</label>
            </div>

            <div class="flex gap-2 mb-2">
                <input
                    v-model="identifier"
                    type="text"
                    :placeholder="type === 'siswa' ? 'NIS' : 'NUPTK'"
                    class="flex-1 border rounded p-2"
                />
                <button class="bg-purple-600 text-white px-4 rounded" :disabled="lookingUp" @click="lookup">
                    Cari
                </button>
            </div>
            <p v-if="lookupError" class="text-red-600 text-sm">{{ lookupError }}</p>
        </div>

        <form v-else @submit.prevent="submit">
            <p class="mb-3 p-2 bg-gray-100 rounded">
                Mengajukan untuk: <strong>{{ target.nama }}</strong>
                <button type="button" class="ml-2 text-sm text-red-600" @click="target = null">Ganti</button>
            </p>

            <label class="block mb-2 text-sm">Tanggal Mulai
                <input v-model="form.tanggal_mulai" type="date" required class="w-full border rounded p-2" />
            </label>
            <label class="block mb-2 text-sm">Tanggal Selesai
                <input v-model="form.tanggal_selesai" type="date" required class="w-full border rounded p-2" />
            </label>
            <label class="block mb-2 text-sm">Tipe
                <select v-model="form.tipe_izin" class="w-full border rounded p-2">
                    <option value="Sakit">Sakit</option>
                    <option value="Izin">Izin</option>
                </select>
            </label>
            <label class="block mb-2 text-sm">Alasan
                <textarea v-model="form.alasan" required class="w-full border rounded p-2"></textarea>
            </label>
            <label class="block mb-4 text-sm">Bukti (foto, maks 2MB)
                <input type="file" accept="image/png,image/jpeg" required @change="onFileChange" class="w-full" />
            </label>

            <button type="submit" class="w-full bg-purple-600 text-white py-2 rounded" :disabled="submitting">
                {{ submitting ? 'Mengirim...' : 'Kirim Pengajuan' }}
            </button>
        </form>

        <div v-if="submitResult" class="mt-4 p-3 rounded" :class="submitResult.success ? 'bg-green-100' : 'bg-red-100'">
            {{ submitResult.message }}
        </div>
    </div>
</template>
