import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/teacher-redesign.css',
                'resources/css/student-redesign.css',
                'resources/css/dark-mode.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
});
