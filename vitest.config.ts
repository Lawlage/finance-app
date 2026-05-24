import { defineConfig } from 'vitest/config'
import react from '@vitejs/plugin-react'
import path from 'path'

export default defineConfig({
    plugins: [react()],
    test: {
        globals: true,
        environment: 'jsdom',
        setupFiles: ['./resources/js/test/setup.ts'],
        include: ['resources/js/**/*.{test,spec}.{ts,tsx}'],
        coverage: {
            provider: 'v8',
            include: ['resources/js/**/*.{ts,tsx}'],
            exclude: [
                'resources/js/test/**',
                'resources/js/**/*.d.ts',
                'resources/js/app.tsx',
            ],
            // TODO: re-enable once component test coverage reaches 90%
            // thresholds: {
            //     statements: 90,
            //     branches: 90,
            //     functions: 90,
            //     lines: 90,
            // },
        },
    },
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
        },
    },
})
