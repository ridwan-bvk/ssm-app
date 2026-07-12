import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/pwa/main.js'],
            refresh: true,
        }),
        tailwindcss(),
        vue(),
        VitePWA({
            strategies: 'injectManifest',
            srcDir: 'resources/js/pwa',
            filename: 'sw.js',
            outDir: 'public',
            injectRegister: false,
            manifest: {
                name: 'Absensi Sekolah',
                short_name: 'Absensi',
                description: 'Sistem Absensi Sekolah berbasis QR Code & RFID',
                start_url: '/scan',
                display: 'standalone',
                background_color: '#ffffff',
                theme_color: '#9c27b0',
                icons: [
                    { src: '/assets/img/favicon.png', sizes: '192x192', type: 'image/png' },
                    { src: '/assets/img/favicon.png', sizes: '512x512', type: 'image/png' },
                ],
            },
            injectManifest: {
                globPatterns: [],
            },
            devOptions: {
                enabled: true,
                type: 'module',
            },
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
