import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';

// Build "puro" (sin laravel-vite-plugin) para el despliegue estático en
// GitHub Pages. El desarrollo local sigue usando vite.config.js + Laragon.
export default defineConfig({
    base: '/registros-diario-call-center/',
    plugins: [vue()],
    build: {
        outDir: 'dist',
    },
});
