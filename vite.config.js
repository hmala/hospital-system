import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import fs from 'fs';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
        {
            name: 'sync-build-to-dist',
            closeBundle() {
                const src = path.resolve(__dirname, 'public/build');
                const dest = path.resolve(__dirname, 'dist');
                if (fs.existsSync(src)) {
                    fs.cpSync(src, dest, { recursive: true });
                    const indexFile = path.join(dest, 'index.html');
                    if (!fs.existsSync(indexFile)) {
                        fs.writeFileSync(indexFile, '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><title>Hospital System</title></head><body><div id="app"></div></body></html>');
                    }
                }
            },
        },
    ],
});
