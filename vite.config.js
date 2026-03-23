import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/paymonitor.css',
                'resources/css/paymonitor-landing.css',
                'resources/js/app.js',
                'resources/js/paymonitor-landing.js',
                'resources/js/paymonitor-dashboard.js',
            ],
            refresh: true,
        }),
    ],
});
