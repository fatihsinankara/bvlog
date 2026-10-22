<?php
use Core\Hook;
use Core\Request;
use Core\Seo;
use Core\Session;

$siteTitle = setting('site_title', 'Bvlog');
$siteLogo = setting('site_logo', '');
$siteFavicon = setting('site_favicon', '');
$siteTagline = setting('site_tagline', '');
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

    <!-- Üst Gezinme Çubuğu (Gölgesiz, Minimal, Çizgisel) -->
    <header class="border-b border-zinc-200 dark:border-zinc-800 bg-white/95 dark:bg-zinc-950/95 backdrop-blur-none sticky top-0 z-40 print:hidden">
        <div class="max-w-3xl mx-auto px-5 sm:px-8 h-16 flex items-center justify-between">
            <!-- Marka / Logo -->
            <a href="<?= e(url('/')) ?>" class="group flex items-center gap-2">
                <?php if (!empty($siteLogo)): ?>
                    <img src="<?= e(url($siteLogo)) ?>" alt="<?= e($siteTitle) ?>" class="h-6 w-auto object-contain">
                <?php endif; ?>
                <span class="text-base sm:text-lg font-black tracking-tight text-zinc-900 dark:text-zinc-100 group-hover:text-zinc-600 dark:group-hover:text-zinc-300 transition-colors">
                    <?= e($siteTitle) ?>
                </span>
                <?php if (!empty($siteTagline)): ?>
                    <span class="hidden md:inline-block text-xs text-zinc-400 font-normal">/ <?= e($siteTagline) ?></span>
                <?php endif; ?>
            </a>

            <!-- Masaüstü Sade Metin Menüsü -->
            <nav class="hidden sm:flex items-center gap-5 text-xs font-medium">
                <a href="<?= e(url('/blog')) ?>"
                   class="transition-colors <?= str_starts_with($currentPath, '/blog') || str_starts_with($currentPath, '/category') ? 'text-zinc-950 dark:text-zinc-50 font-bold underline underline-offset-4 decoration-2' : 'text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100' ?>">
                    Yazılar
                </a>
                <?php if (is_module_active('portfolio')): ?>
                    <a href="<?= e(url('/portfolio')) ?>"
                       class="transition-colors <?= str_starts_with($currentPath, '/portfolio') ? 'text-zinc-950 dark:text-zinc-50 font-bold underline underline-offset-4 decoration-2' : 'text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100' ?>">
                        Portfolyo
                    </a>
                <?php endif; ?>
                <?php if (is_module_active('cv')): ?>
                    <a href="<?= e(url('/cv')) ?>"
                       class="transition-colors <?= str_starts_with($currentPath, '/cv') ? 'text-zinc-950 dark:text-zinc-50 font-bold underline underline-offset-4 decoration-2' : 'text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100' ?>">
                        Özgeçmiş
                    </a>
                <?php endif; ?>
                <?php if (is_module_active('contact')): ?>
                    <a href="<?= e(url('/contact')) ?>"
                       class="transition-colors <?= str_starts_with($currentPath, '/contact') ? 'text-zinc-950 dark:text-zinc-50 font-bold underline underline-offset-4 decoration-2' : 'text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100' ?>">
                        İletişim
                    </a>
                <?php endif; ?>
                <?php Core\Hook::doAction('frontend_nav_items'); ?>

                <div class="h-3 w-px bg-zinc-200 dark:bg-zinc-800"></div>

                <!-- Tema Değiştirici -->
                <button type="button"
                        @click="darkMode = !darkMode"
                        class="text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors p-1"
                        title="Açık/Koyu Tema">
                    <i class="fa-regular fa-sun text-xs" x-show="darkMode" style="display: none;"></i>
                    <i class="fa-regular fa-moon text-xs" x-show="!darkMode"></i>
                </button>

                <?php if (Session::has('admin_user_id')): ?>
                    <a href="<?= e(url('/admin')) ?>"
                       class="px-2 py-1 rounded text-[11px] font-semibold border border-zinc-300 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-900 transition-colors">
                        Panel
                    </a>
                <?php endif; ?>
            </nav>

            <!-- Mobil Menü ve Tema Butonu -->
            <div class="sm:hidden flex items-center gap-3">
                <button type="button"
                        @click="darkMode = !darkMode"
                        class="text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 p-1">
                    <i class="fa-regular fa-sun text-xs" x-show="darkMode" style="display: none;"></i>
                    <i class="fa-regular fa-moon text-xs" x-show="!darkMode"></i>
                </button>
                <button type="button"
                        @click="mobileMenu = !mobileMenu"
                        class="text-zinc-600 dark:text-zinc-400 p-1">
                    <i class="fa-solid fa-bars text-sm" x-show="!mobileMenu"></i>
                    <i class="fa-solid fa-xmark text-sm" x-show="mobileMenu" style="display: none;"></i>
                </button>
            </div>
        </div>

        <!-- Mobil Menü Açılır Panel -->
        <div x-show="mobileMenu" x-transition class="sm:hidden border-t border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 px-5 py-3 space-y-2 text-xs font-medium" style="display: none;">
            <a href="<?= e(url('/blog')) ?>" class="block py-1.5 text-zinc-700 dark:text-zinc-300">Yazılar</a>
            <?php if (is_module_active('portfolio')): ?>
                <a href="<?= e(url('/portfolio')) ?>" class="block py-1.5 text-zinc-700 dark:text-zinc-300">Portfolyo</a>
            <?php endif; ?>
            <?php if (is_module_active('cv')): ?>
                <a href="<?= e(url('/cv')) ?>" class="block py-1.5 text-zinc-700 dark:text-zinc-300">Özgeçmiş</a>
            <?php endif; ?>
            <?php if (is_module_active('contact')): ?>
                <a href="<?= e(url('/contact')) ?>" class="block py-1.5 text-zinc-700 dark:text-zinc-300">İletişim</a>
            <?php endif; ?>
            <?php Core\Hook::doAction('frontend_mobile_nav_items'); ?>
            <?php if (Session::has('admin_user_id')): ?>
                <a href="<?= e(url('/admin')) ?>" class="block py-1.5 font-bold border-t border-zinc-100 dark:border-zinc-800 pt-2 text-zinc-900 dark:text-zinc-100">
                    <i class="fa-solid fa-sliders mr-1.5"></i>Yönetim Paneli
                </a>
            <?php endif; ?>
        </div>
    </header>

    <!-- Bildirim Kutuları (Gölgesiz, Düz Kenarlık) -->
    <div class="max-w-3xl w-full mx-auto px-5 sm:px-8">
        <?php if (Session::hasFlash('success')): ?>
            <div class="mt-6 p-3.5 rounded border border-emerald-300 dark:border-emerald-800/80 bg-emerald-50/70 dark:bg-emerald-950/40 text-emerald-900 dark:text-emerald-200 text-xs flex items-center justify-between">
                <span><i class="fa-solid fa-check mr-2"></i><?= e(Session::flash('success')) ?></span>
            </div>
        <?php endif; ?>
        <?php if (Session::hasFlash('error')): ?>
            <div class="mt-6 p-3.5 rounded border border-red-300 dark:border-red-800/80 bg-red-50/70 dark:bg-red-950/40 text-red-900 dark:text-red-200 text-xs flex items-center justify-between">
                <span><i class="fa-solid fa-circle-exclamation mr-2"></i><?= e(Session::flash('error')) ?></span>
            </div>
        <?php endif; ?>
    </div>

    <!-- Ana İçerik Alanı (Dar Editoryal Genişlik: max-w-3xl) -->
    <main class="flex-1 max-w-3xl w-full mx-auto px-5 sm:px-8 py-10 sm:py-14">
        <?= $content ?? '' ?>
    </main>

    <!-- Altbilgi (Footer - Sade, Çizgisel) -->
    <footer class="border-t border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 print:hidden text-xs text-zinc-500 dark:text-zinc-400">
        <div class="max-w-3xl mx-auto px-5 sm:px-8 py-8 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>
                © <?= date('Y') ?> <a href="https://github.com/fatihsinankara/bvlog" target="_blank" rel="noopener noreferrer" class="font-medium hover:underline hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors">Bvlog</a>. Tüm hakları saklıdır.
            </div>
            <div class="flex items-center gap-4 text-xs">
                <?php if ($gh = setting('social_github')): ?>
                    <a href="<?= e($gh) ?>" target="_blank" rel="noopener noreferrer" class="hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors" title="GitHub"><i class="fa-brands fa-github text-sm"></i></a>
                <?php endif; ?>
                <?php if ($li = setting('social_linkedin')): ?>
                    <a href="<?= e($li) ?>" target="_blank" rel="noopener noreferrer" class="hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors" title="LinkedIn"><i class="fa-brands fa-linkedin text-sm"></i></a>
                <?php endif; ?>
                <?php if ($tw = setting('social_twitter')): ?>
                    <a href="<?= e($tw) ?>" target="_blank" rel="noopener noreferrer" class="hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors" title="X / Twitter"><i class="fa-brands fa-x-twitter text-sm"></i></a>
                <?php endif; ?>
                <a href="<?= e(url('/rss.xml')) ?>" target="_blank" class="hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors" title="RSS Beslemesi"><i class="fa-solid fa-rss text-sm"></i></a>
            </div>
        </div>
    </footer>

    <script type="module" src="<?= e(asset('js/app.js')) ?>"></script>
    <?php Hook::doAction('footer_scripts'); ?>
</body>
</html>
