import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.js', 'resources/js/public.js', 'resources/js/meetings.js'],
            refresh: true,
        }),
    ],
});
