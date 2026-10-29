import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    server: {
        port: 5174,
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/spa.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
    build: {
        chunkSizeWarningLimit: 1000,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('vite/preload-helper')) {
                        return 'vite-preload';
                    }
                    if (id.includes('node_modules/xlsx')) {
                        return 'vendor-xlsx';
                    }
                    if (id.includes('node_modules/jspdf') || id.includes('node_modules/html2canvas')) {
                        return 'vendor-pdf';
                    }
                },
            },
        },
    },
});
