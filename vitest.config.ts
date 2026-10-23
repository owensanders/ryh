import vue from '@vitejs/plugin-vue';
import path from 'path';
import { defineConfig } from 'vitest/config';

/**
 * Unit tests for the frontend's composables and helpers: `*.test.ts` beside
 * the file they test. Separate from vite.config.ts, whose Laravel plugin
 * expects a dev server or a build.
 */
export default defineConfig({
    plugins: [vue()],
    resolve: {
        alias: { '@': path.resolve(__dirname, './resources/js') },
    },
    test: {
        environment: 'jsdom',
        include: ['resources/js/**/*.test.ts'],
        setupFiles: ['resources/js/test/setup.ts'],
        restoreMocks: true,
    },
});
