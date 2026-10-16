import { defineConfig } from 'vite';
import path from 'path';

export default defineConfig({
    base: './',
    publicDir: false,
    build: {
        outDir: 'assets',
        emptyOutDir: true,
        rollupOptions: {
            input: {
                app: path.resolve(__dirname, 'resources/js/app.js'),
                style: path.resolve(__dirname, 'resources/css/app.css'),
            },
            output: {
                entryFileNames: 'js/[name].js',
                chunkFileNames: 'js/[name].js',
                assetFileNames: (assetInfo) => {
                    const name = assetInfo.name || '';
                    if (name.endsWith('.css')) {
                        return 'css/app.css';
                    }
                    if (/\.(woff2?|ttf|eot|svg)$/.test(name)) {
                        return 'webfonts/[name][extname]';
                    }
                    return 'media/[name][extname]';
                },
            },
        },
    },
});
