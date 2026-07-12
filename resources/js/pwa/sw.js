import { precacheAndRoute } from 'workbox-precaching';
import { registerRoute } from 'workbox-routing';
import { NetworkFirst } from 'workbox-strategies';

// Injected at build time by vite-plugin-pwa with the hashed list of built
// assets (JS/CSS/manifest icons) for the PWA shell.
precacheAndRoute(self.__WB_MANIFEST);

// The three app pages themselves: try the network first (so logged-in users
// always get a fresh shell when online), fall back to the cached shell when
// offline. Data (roster, pending scans) lives in IndexedDB via db.js, not
// this cache — this only keeps the app shell itself loadable offline.
registerRoute(
    ({ url }) => ['/scan', '/izin', '/cek-kehadiran'].includes(url.pathname),
    new NetworkFirst({ cacheName: 'pwa-shell' }),
);

self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});
