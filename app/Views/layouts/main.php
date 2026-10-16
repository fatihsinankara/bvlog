<?php
use Core\Hook;
use Core\Request;
use Core\Seo;
use Core\Session;

$siteTitle = setting('site_title', 'Bvlog');
$siteLogo = setting('site_logo', '');
$siteFavicon = setting('site_favicon', '');
$currentPath = Request::path();
?>
<!DOCTYPE html>
<html lang="tr" x-data="{ darkMode: localStorage.getItem('bvlog_theme') === 'dark' || (!localStorage.getItem('bvlog_theme') && window.matchMedia('(prefers-color-scheme: dark)').matches), mobileMenu: false }"
      x-init="$watch('darkMode', val => { localStorage.setItem('bvlog_theme', val ? 'dark' : 'light'); document.documentElement.classList.toggle('dark', val); }); document.documentElement.classList.toggle('dark', darkMode);"
      :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= Seo::renderHead($meta ?? []) ?>
    <?php if (!empty($siteFavicon)): ?>
        <link rel="icon" href="<?= e(url($siteFavicon)) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <script>
        if (localStorage.getItem('bvlog_theme') === 'dark' || (!localStorage.getItem('bvlog_theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
    <?php Hook::doAction('head_tags'); ?>
</head>
<body class="min-h-screen flex flex-col bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 antialiased selection:bg-zinc-900 selection:text-white dark:selection:bg-zinc-100 dark:selection:text-zinc-900">

    <!-- Üst Gezinme Çubuğu -->
    <header class="sticky top-0 z-40 border-b border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 print:hidden">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            <!-- Marka / Logo -->
            <a href="<?= e(url('/')) ?>" class="flex items-center gap-2.5 text-lg font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                <?php if (!empty($siteLogo)): ?>
                    <img src="<?= e(url($siteLogo)) ?>" alt="<?= e($siteTitle) ?>" class="h-7 w-auto object-contain">
                <?php else: ?>
                    <span class="inline-flex items-center justify-center w-7 h-7 rounded bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-sm font-extrabold">B</span>
                <?php endif; ?>
                <span><?= e($siteTitle) ?></span>
            </a>

            <!-- Masaüstü Menü -->
            <nav class="hidden md:flex items-center gap-1 text-sm font-medium">
                <a href="<?= e(url('/')) ?>"
                   class="px-3 py-1.5 rounded-lg transition-colors <?= $currentPath === '/' ? 'bg-zinc-100 dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 font-semibold' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100' ?>">
                    Anasayfa
                </a>
                <a href="<?= e(url('/blog')) ?>"
                   class="px-3 py-1.5 rounded-lg transition-colors <?= str_starts_with($currentPath, '/blog') || str_starts_with($currentPath, '/category') ? 'bg-zinc-100 dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 font-semibold' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100' ?>">
                    Blog
                </a>
                <?php if (is_module_active('portfolio')): ?>
                    <a href="<?= e(url('/portfolio')) ?>"
                       class="px-3 py-1.5 rounded-lg transition-colors <?= str_starts_with($currentPath, '/portfolio') ? 'bg-zinc-100 dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 font-semibold' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100' ?>">
                        Portfolyo
                    </a>
                <?php endif; ?>
                <?php if (is_module_active('cv')): ?>
                    <a href="<?= e(url('/cv')) ?>"
                       class="px-3 py-1.5 rounded-lg transition-colors <?= str_starts_with($currentPath, '/cv') ? 'bg-zinc-100 dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 font-semibold' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100' ?>">
                        CV / Özgeçmiş
                    </a>
                <?php endif; ?>
                <?php if (is_module_active('contact')): ?>
                    <a href="<?= e(url('/contact')) ?>"
                       class="px-3 py-1.5 rounded-lg transition-colors <?= str_starts_with($currentPath, '/contact') ? 'bg-zinc-100 dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 font-semibold' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100' ?>">
                        İletişim
                    </a>
                <?php endif; ?>
            </nav>

            <!-- Sağ Aksiyonlar -->
            <div class="flex items-center gap-2">
                <!-- Tema Değiştirici -->
                <button type="button"
                        @click="darkMode = !darkMode"
                        class="w-9 h-9 inline-flex items-center justify-center rounded-lg border border-zinc-200 dark:border-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-900 transition-colors"
                        title="Görünüm Modunu Değiştir">
                    <i class="fa-regular fa-sun" x-show="darkMode" style="display: none;"></i>
                    <i class="fa-regular fa-moon" x-show="!darkMode"></i>
                </button>

                <?php if (Session::has('admin_user_id')): ?>
                    <a href="<?= e(url('/admin')) ?>"
                       class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg border border-zinc-300 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-900 transition-colors">
                        <i class="fa-solid fa-sliders"></i>
                        <span>Panel</span>
                    </a>
                <?php endif; ?>

                <!-- Mobil Menü Butonu -->
                <button type="button"
                        @click="mobileMenu = !mobileMenu"
                        class="md:hidden w-9 h-9 inline-flex items-center justify-center rounded-lg border border-zinc-200 dark:border-zinc-800 text-zinc-600 dark:text-zinc-400">
                    <i class="fa-solid fa-bars" x-show="!mobileMenu"></i>
                    <i class="fa-solid fa-xmark" x-show="mobileMenu" style="display: none;"></i>
                </button>
            </div>
        </div>

        <!-- Mobil Menü Çekmecesi -->
        <div x-show="mobileMenu" x-transition class="md:hidden border-t border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 px-4 py-3 space-y-1" style="display: none;">
            <a href="<?= e(url('/')) ?>" class="block px-3 py-2 rounded-lg text-sm font-medium hover:bg-zinc-100 dark:hover:bg-zinc-900">Anasayfa</a>
            <a href="<?= e(url('/blog')) ?>" class="block px-3 py-2 rounded-lg text-sm font-medium hover:bg-zinc-100 dark:hover:bg-zinc-900">Blog</a>
            <?php if (is_module_active('portfolio')): ?>
                <a href="<?= e(url('/portfolio')) ?>" class="block px-3 py-2 rounded-lg text-sm font-medium hover:bg-zinc-100 dark:hover:bg-zinc-900">Portfolyo</a>
            <?php endif; ?>
            <?php if (is_module_active('cv')): ?>
                <a href="<?= e(url('/cv')) ?>" class="block px-3 py-2 rounded-lg text-sm font-medium hover:bg-zinc-100 dark:hover:bg-zinc-900">CV / Özgeçmiş</a>
            <?php endif; ?>
            <?php if (is_module_active('contact')): ?>
                <a href="<?= e(url('/contact')) ?>" class="block px-3 py-2 rounded-lg text-sm font-medium hover:bg-zinc-100 dark:hover:bg-zinc-900">İletişim</a>
            <?php endif; ?>
            <?php if (Session::has('admin_user_id')): ?>
                <a href="<?= e(url('/admin')) ?>" class="block px-3 py-2 rounded-lg text-sm font-semibold text-zinc-900 dark:text-zinc-100 border-t border-zinc-100 dark:border-zinc-800 mt-2 pt-2">
                    <i class="fa-solid fa-sliders mr-1.5"></i>Yönetim Paneli
                </a>
            <?php endif; ?>
        </div>
    </header>

    <!-- Bildirimler -->
    <div class="max-w-4xl w-full mx-auto px-4 sm:px-6">
        <?php if (Session::hasFlash('success')): ?>
            <div class="mt-6 p-4 rounded-lg border border-emerald-300 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-900 dark:text-emerald-200 text-sm flex items-center justify-between">
                <span><i class="fa-solid fa-circle-check mr-2"></i><?= e(Session::flash('success')) ?></span>
            </div>
        <?php endif; ?>
        <?php if (Session::hasFlash('error')): ?>
            <div class="mt-6 p-4 rounded-lg border border-red-300 dark:border-red-800 bg-red-50 dark:bg-red-950/60 text-red-900 dark:text-red-200 text-sm flex items-center justify-between">
                <span><i class="fa-solid fa-circle-exclamation mr-2"></i><?= e(Session::flash('error')) ?></span>
            </div>
        <?php endif; ?>
    </div>

    <!-- Ana İçerik -->
    <main class="flex-1 max-w-4xl w-full mx-auto px-4 sm:px-6 py-10 sm:py-14">
        <?= $content ?? '' ?>
    </main>

    <!-- Altbilgi (Footer) -->
    <footer class="border-t border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 print:hidden">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 py-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-zinc-500 dark:text-zinc-400">
            <div>
                © <?= date('Y') ?> <a href="https://github.com/fatihsinankara/bvlog" target="_blank" rel="noopener noreferrer" class="font-medium hover:underline hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors">Bvlog</a>. Tüm hakları saklıdır.
            </div>
            <div class="flex items-center gap-4 text-sm">
                <?php if ($gh = setting('social_github')): ?>
                    <a href="<?= e($gh) ?>" target="_blank" rel="noopener noreferrer" class="hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors" title="GitHub"><i class="fa-brands fa-github"></i></a>
                <?php endif; ?>
                <?php if ($li = setting('social_linkedin')): ?>
                    <a href="<?= e($li) ?>" target="_blank" rel="noopener noreferrer" class="hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors" title="LinkedIn"><i class="fa-brands fa-linkedin"></i></a>
                <?php endif; ?>
                <?php if ($tw = setting('social_twitter')): ?>
                    <a href="<?= e($tw) ?>" target="_blank" rel="noopener noreferrer" class="hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors" title="X / Twitter"><i class="fa-brands fa-x-twitter"></i></a>
                <?php endif; ?>
                <?php if ($ig = setting('social_instagram')): ?>
                    <a href="<?= e($ig) ?>" target="_blank" rel="noopener noreferrer" class="hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
                <?php endif; ?>
                <a href="<?= e(url('/rss.xml')) ?>" target="_blank" class="hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors" title="RSS Beslemesi"><i class="fa-solid fa-rss"></i></a>
            </div>
        </div>
    </footer>

    <script type="module" src="<?= e(asset('js/app.js')) ?>"></script>
    <?php Hook::doAction('footer_scripts'); ?>
</body>
</html>
