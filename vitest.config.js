import { defineConfig } from 'vitest/config';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [vue()],
    test: {
        environment: 'jsdom',
        globals: true,
        root: '.',
        include: ['resources/js/**/*.{test,spec}.{js,ts}'],
        // The first test of each page spec includes the cold import of heavy page modules
        // (tiptap, radix-vue); with the spec files running in parallel it can exceed 5s.
        testTimeout: 20000,
    },
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
});
