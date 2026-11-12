import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './index.php',
        './app/**/*.php',
        './core/**/*.php',
        './themes/**/*.php',
        './plugins/**/*.php',
        './resources/**/*.{js,css}',
    ],
    theme: {
        extend: {
            fontFamily: {
                // Inter yuklenmiyordu; yalnizca kurulu oldugu cihazlarda gorunuyor ve
                // sayfalar cihazdan cihaza farkli gorunuyordu. Sistem yazi tipleri kullanilir.
                sans: [
                    'system-ui',
                    '-apple-system',
                    'BlinkMacSystemFont',
                    '"Segoe UI"',
                    'Roboto',
                    '"Helvetica Neue"',
                    'Arial',
                    'sans-serif',
                ],
                mono: [
                    'ui-monospace',
                    'SFMono-Regular',
                    'Menlo',
                    'Monaco',
                    'Consolas',
                    'monospace',
                ],
            },
        },
    },
    plugins: [typography],
};
