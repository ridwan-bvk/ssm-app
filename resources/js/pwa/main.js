import { createApp } from 'vue';
import axios from 'axios';
import App from './App.vue';
import router from './router';
import './pwa.css';

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch((err) => {
            console.error('Service worker registration failed', err);
        });
    });
}

// Prime the XSRF-TOKEN cookie for Sanctum's stateful SPA auth before any
// authenticated API call (the scan endpoints need this; izin/cek-kehadiran
// are public and don't strictly need it, but it's harmless there too).
axios.get('/sanctum/csrf-cookie', { withCredentials: true }).catch(() => {});

createApp(App).use(router).mount('#pwa-app');
