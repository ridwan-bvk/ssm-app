import { createRouter, createWebHistory } from 'vue-router';
import ScanPage from './pages/ScanPage.vue';
import IzinPage from './pages/IzinPage.vue';
import CekKehadiranPage from './pages/CekKehadiranPage.vue';

export default createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/scan', name: 'scan', component: ScanPage },
        { path: '/izin', name: 'izin', component: IzinPage },
        { path: '/cek-kehadiran', name: 'cek-kehadiran', component: CekKehadiranPage },
    ],
});
