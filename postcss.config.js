/**
 * FontAwesome @font-face kurallarini sadelestirir:
 * - .ttf yedekleri cikarilir; desteklenen tum tarayicilar woff2 okur.
 * - font-display: block yerine swap; simgeler yuklenene kadar gorunmez kalmaz.
 */
const fontAwesomeFontFace = () => ({
    postcssPlugin: 'bvlog-fontawesome-font-face',
    AtRule: {
        'font-face': (rule) => {
            const family = rule.nodes?.find((n) => n.prop === 'font-family');
            if (!family || !/Font Awesome/i.test(family.value)) {
                return;
            }
            rule.walkDecls('src', (decl) => {
                decl.value = decl.value
                    .split(/,(?=\s*url\()/)
                    .filter((part) => !/format\(\s*["']?truetype/i.test(part))
                    .join(',');
            });
            rule.walkDecls('font-display', (decl) => {
                decl.value = 'swap';
            });
        },
    },
});
fontAwesomeFontFace.postcss = true;

export default {
    plugins: [
        fontAwesomeFontFace(),
        (await import('tailwindcss')).default(),
        (await import('autoprefixer')).default(),
    ],
};
