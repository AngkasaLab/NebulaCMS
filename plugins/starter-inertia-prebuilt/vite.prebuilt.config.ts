import react from '@vitejs/plugin-react';
import { resolve } from 'node:path';
import { defineConfig } from 'vite';

const projectRoot = resolve(__dirname, '../..');

export default defineConfig({
    root: __dirname,
    base: './',
    plugins: [react()],
    resolve: {
        alias: {
            '@': resolve(projectRoot, 'resources/js'),
            'react/jsx-runtime': resolve(projectRoot, 'resources/js/plugin-runtime/react-jsx-runtime-shim.ts'),
            react: resolve(projectRoot, 'resources/js/plugin-runtime/react-shim.ts'),
            '@inertiajs/react': resolve(projectRoot, 'resources/js/plugin-runtime/inertia-react-shim.ts'),
            'ziggy-js': resolve(projectRoot, 'vendor/tightenco/ziggy'),
        },
    },
    build: {
        outDir: resolve(__dirname, 'dist'),
        emptyOutDir: true,
        manifest: 'manifest.json',
        rollupOptions: {
            input: resolve(__dirname, 'resources/js/plugin-app.tsx'),
        },
    },
});
