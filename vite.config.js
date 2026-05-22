import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
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
            '@': path.resolve(__dirname, 'resources/js'),
        },
    },
    build: {
        // Lighter build to reduce memory pressure on constrained build environments
        minify: 'esbuild',          // esbuild uses far less memory than terser
        sourcemap: false,           // skip sourcemaps (saves memory + time)
        chunkSizeWarningLimit: 2000,
        rollupOptions: {
            output: {
                // Avoid aggressive code-splitting that increases peak memory
                manualChunks: undefined,
            },
        },
    },
});
