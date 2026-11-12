import { defineConfig } from 'vite';
import path from 'path';

// postcss.config.js FontAwesome .ttf yedeklerini CSS'ten cikarir; Vite dosyalari
// yine de uretir. Hicbir yerden referans verilmeyen .ttf ciktilari atilir.
const dropUnusedTtf = () => ({
    name: 'bvlog-drop-unused-ttf',
    generateBundle(_options, bundle) {
        const css = Object.values(bundle)
            .filter((item) => item.type === 'asset' && item.fileName.endsWith('.css'))
            .map((item) => String(item.source))
            .join('\n');
        for (const [fileName, item] of Object.entries(bundle)) {
            const base = fileName.split('/').pop();
            if (item.type === 'asset' && fileName.endsWith('.ttf') && !css.includes(base)) {
                delete bundle[fileName];
            }
        }
    },
});

export default defineConfig({
    base: './',
    plugins: [dropUnusedTtf()],
    publicDir: false,
    build: {
        outDir: 'assets',
        emptyOutDir: true,
        rollupOptions: {
            input: {
                app: path.resolve(__dirname, 'resources/js/app.js'),
                admin: path.resolve(__dirname, 'resources/js/admin.js'),
                style: path.resolve(__dirname, 'resources/css/app.css'),
            },
            output: {
                entryFileNames: 'js/[name].js',
                // Giris dosyalari asset() ile ?v= alir; ic import edilen parcalar ve yazi
                // tipleri sorgu dizesi tasimadigi icin adlarinda icerik ozeti olur.
                chunkFileNames: 'js/[name]-[hash].js',
                assetFileNames: (assetInfo) => {
                    const name = assetInfo.name || '';
                    if (name.endsWith('.css')) {
                        return 'css/app.css';
                    }
                    if (/\.(woff2?|ttf|eot|svg)$/.test(name)) {
                        return 'webfonts/[name]-[hash][extname]';
                    }
                    return 'media/[name][extname]';
                },
            },
        },
    },
});
