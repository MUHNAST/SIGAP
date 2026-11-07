import { defineConfig } from 'vitest/config';

export default defineConfig({
    test: {
        environment: 'jsdom',
        include: ['tests/javascript/**/*.test.js'],
        clearMocks: true,
        restoreMocks: true,
        coverage: {
            provider: 'v8',
            reportsDirectory: 'node_modules/.cache/vitest-coverage',
            include: [
                'resources/js/app.js',
                'resources/js/bootstrap.js',
                'resources/js/components/core/**/*.js',
                'resources/js/components/form/**/*.js',
                'resources/js/components/kgb/**/*.js',
                'resources/js/components/modules/**/*.js',
            ],
            exclude: ['**/*.test.js'],
            reporter: ['text', 'json-summary', 'json'],
        },
    },
});
