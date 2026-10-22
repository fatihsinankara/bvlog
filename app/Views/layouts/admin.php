<?php
use App\Models\Comment;
use App\Models\Message;
use Core\Csrf;
use Core\Hook;
use Core\Request;
use Core\Session;
use Core\Updater;
use Core\View;

$currentPath = Request::path();
$siteTitle = setting('site_title', 'Bvlog');
$pendingComments = Comment::count('status = "pending"');
$unreadMessages = Message::count('is_read = 0');
$updateInfo = Updater::check(false);
?>
<!DOCTYPE html>
<html lang="tr" x-data="{ darkMode: localStorage.getItem('bvlog_theme') === 'dark', sidebarOpen: false }"
      x-init="$watch('darkMode', val => { localStorage.setItem('bvlog_theme', val ? 'dark' : 'light'); document.documentElement.classList.toggle('dark', val); }); document.documentElement.classList.toggle('dark', darkMode);"
      :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(Csrf::token()) ?>">
    <title><?= e(($pageTitle ?? 'Yönetim Paneli') . ' — ' . $siteTitle . ' Admin') ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <script>
        if (localStorage.getItem('bvlog_theme') === 'dark') {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="h-screen overflow-hidden bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 antialiased flex">

    <!-- Sol Yan Menü (Sidebar) -->
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
           class="fixed inset-y-0 left-0 z-40 w-64 h-screen shrink-0 border-r border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 flex flex-col transition-transform duration-200 lg:static">
        <!-- Logo Başlık -->
        <div class="h-16 shrink-0 px-5 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
            <a href="<?= e(url('/admin')) ?>" class="flex items-center gap-2.5 font-bold text-base tracking-tight">
                <span class="inline-flex items-center justify-center w-7 h-7 rounded bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-extrabold">B</span>
                <span><?= e($siteTitle) ?> <span class="text-xs font-normal text-zinc-500">Yönetim</span></span>
            </a>
            <button type="button" @click="sidebarOpen = false" class="lg:hidden text-zinc-400 hover:text-zinc-700">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Menü Linkleri -->
        <nav class="flex-1 overflow-y-auto p-3 space-y-1 text-sm">
            <div class="px-3 py-1.5 text-[11px] font-bold uppercase tracking-wider text-zinc-400">İçerik & Medya</div>

            <a href="<?= e(url('/admin')) ?>"
               class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors <?= $currentPath === '/admin' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800' ?>">
                <i class="fa-solid fa-gauge-high w-4 text-center"></i>
                <span>Genel Bakış</span>
            </a>

            <a href="<?= e(url('/admin/posts')) ?>"
               class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors <?= str_starts_with($currentPath, '/admin/posts') ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800' ?>">
                <i class="fa-solid fa-pen-nib w-4 text-center"></i>
                <span>Blog Yazıları</span>
            </a>

            <a href="<?= e(url('/admin/categories')) ?>"
               class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors <?= str_starts_with($currentPath, '/admin/categories') ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800' ?>">
                <i class="fa-solid fa-tags w-4 text-center"></i>
                <span>Kategoriler</span>
            </a>

            <a href="<?= e(url('/admin/media')) ?>"
               class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors <?= str_starts_with($currentPath, '/admin/media') ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800' ?>">
                <i class="fa-regular fa-images w-4 text-center"></i>
                <span>Medya Galerisi</span>
            </a>

            <?php Hook::doAction('admin_sidebar_menu'); ?>

            <div class="pt-3 px-3 py-1.5 text-[11px] font-bold uppercase tracking-wider text-zinc-400">Modüller & Etkileşim</div>

            <a href="<?= e(url('/admin/portfolio')) ?>"
               class="flex items-center justify-between px-3 py-2 rounded-lg font-medium transition-colors <?= str_starts_with($currentPath, '/admin/portfolio') ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800' ?>">
                <span class="flex items-center gap-3">
                    <i class="fa-solid fa-briefcase w-4 text-center"></i>
                    <span>Portfolyo</span>
                </span>
                <?php if (!is_module_active('portfolio')): ?>
                    <span class="text-[10px] px-1.5 py-0.5 rounded border border-zinc-300 dark:border-zinc-700 text-zinc-500">Pasif</span>
                <?php endif; ?>
            </a>

            <a href="<?= e(url('/admin/cv')) ?>"
               class="flex items-center justify-between px-3 py-2 rounded-lg font-medium transition-colors <?= str_starts_with($currentPath, '/admin/cv') ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800' ?>">
                <span class="flex items-center gap-3">
                    <i class="fa-solid fa-id-card w-4 text-center"></i>
                    <span>CV & Özgeçmiş</span>
                </span>
                <?php if (!is_module_active('cv')): ?>
                    <span class="text-[10px] px-1.5 py-0.5 rounded border border-zinc-300 dark:border-zinc-700 text-zinc-500">Pasif</span>
                <?php endif; ?>
            </a>

            <a href="<?= e(url('/admin/comments')) ?>"
               class="flex items-center justify-between px-3 py-2 rounded-lg font-medium transition-colors <?= str_starts_with($currentPath, '/admin/comments') ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800' ?>">
                <span class="flex items-center gap-3">
                    <i class="fa-regular fa-comments w-4 text-center"></i>
                    <span>Yorumlar</span>
                </span>
                <?php if ($pendingComments > 0): ?>
                    <span class="text-[11px] font-bold px-2 py-0.5 rounded bg-amber-500 text-white"><?= $pendingComments ?></span>
                <?php endif; ?>
            </a>

            <a href="<?= e(url('/admin/messages')) ?>"
               class="flex items-center justify-between px-3 py-2 rounded-lg font-medium transition-colors <?= str_starts_with($currentPath, '/admin/messages') ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800' ?>">
                <span class="flex items-center gap-3">
                    <i class="fa-regular fa-envelope w-4 text-center"></i>
                    <span>İletişim Mesajları</span>
                </span>
                <?php if ($unreadMessages > 0): ?>
                    <span class="text-[11px] font-bold px-2 py-0.5 rounded bg-blue-600 text-white"><?= $unreadMessages ?></span>
                <?php endif; ?>
            </a>

            <div class="pt-3 px-3 py-1.5 text-[11px] font-bold uppercase tracking-wider text-zinc-400">Görünüm & Sistem</div>

            <a href="<?= e(url('/admin/themes')) ?>"
               class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors <?= str_starts_with($currentPath, '/admin/themes') ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800' ?>">
                <i class="fa-solid fa-palette w-4 text-center"></i>
                <span>Temalar</span>
            </a>

            <a href="<?= e(url('/admin/plugins')) ?>"
               class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors <?= str_starts_with($currentPath, '/admin/plugins') ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800' ?>">
                <i class="fa-solid fa-plug w-4 text-center"></i>
                <span>Eklentiler</span>
            </a>

            <?php Hook::doAction('admin_sidebar_system_menu'); ?>

            <a href="<?= e(url('/admin/settings')) ?>"
               class="flex items-center justify-between px-3 py-2 rounded-lg font-medium transition-colors <?= str_starts_with($currentPath, '/admin/settings') ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800' ?>">
                <span class="flex items-center gap-3">
                    <i class="fa-solid fa-sliders w-4 text-center"></i>
                    <span>Site & Sistem Ayarları</span>
                </span>
                <?php if (!empty($updateInfo['has_update'])): ?>
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse" title="Yeni güncelleme mevcut"></span>
                <?php endif; ?>
            </a>
        </nav>

        <!-- Alt Kullanıcı & Çıkış -->
        <div class="p-3 shrink-0 border-t border-zinc-200 dark:border-zinc-800 flex items-center justify-between text-xs">
            <div class="truncate font-medium">
                <i class="fa-regular fa-user mr-1.5 text-zinc-400"></i><?= e(Session::get('admin_user_name', 'Yönetici')) ?>
            </div>
            <form action="<?= e(url('/admin/logout')) ?>" method="POST">
                <?= csrf_field() ?>
                <button type="submit" class="px-2.5 py-1 rounded border border-zinc-200 dark:border-zinc-700 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/50 transition-colors">
                    <i class="fa-solid fa-right-from-bracket mr-1"></i>Çıkış
                </button>
            </form>
        </div>
    </aside>

    <!-- Sağ Ana Alan (Bağımsız Kaydırılabilir) -->
    <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
        <!-- Üst Çubuk -->
        <header class="h-16 shrink-0 px-6 border-b border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 flex items-center justify-between sticky top-0 z-30">
            <div class="flex items-center gap-3">
                <button type="button" @click="sidebarOpen = true" class="lg:hidden p-2 rounded border border-zinc-200 dark:border-zinc-800">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <h1 class="text-base sm:text-lg font-bold tracking-tight"><?= e($pageTitle ?? 'Yönetim Paneli') ?></h1>
            </div>

            <div class="flex items-center gap-2.5">
                <?php if (!empty($updateInfo['has_update'])): ?>
                    <a href="<?= e(url('/admin/settings?tab=updates')) ?>"
                       class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-bold rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 hover:bg-amber-500/20 transition-colors"
                       title="Yeni Bvlog sürümü mevcut: v<?= e($updateInfo['latest_version']) ?>">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                        <i class="fa-solid fa-cloud-arrow-down text-xs"></i>
                        <span>v<?= e($updateInfo['latest_version']) ?></span>
                    </a>
                <?php endif; ?>

                <a href="<?= e(url('/admin/posts/create')) ?>"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 hover:opacity-90 transition-opacity">
                    <i class="fa-solid fa-plus"></i>
                    <span class="hidden sm:inline">Yeni Yazı</span>
                </a>
                <a href="<?= e(url('/')) ?>" target="_blank"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-zinc-200 dark:border-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    <span>Siteyi Gör</span>
                </a>
                <button type="button"
                        @click="darkMode = !darkMode"
                        class="w-8 h-8 inline-flex items-center justify-center rounded-lg border border-zinc-200 dark:border-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800">
                    <i class="fa-regular fa-sun" x-show="darkMode" style="display: none;"></i>
                    <i class="fa-regular fa-moon" x-show="!darkMode"></i>
                </button>
            </div>
        </header>

        <!-- Bildirimler & İçerik -->
        <main class="flex-1 p-6 max-w-6xl w-full mx-auto">
            <?php if (Session::hasFlash('success')): ?>
                <div class="mb-6 p-4 rounded-lg border border-emerald-300 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-900 dark:text-emerald-200 text-sm flex items-center gap-2">
                    <i class="fa-solid fa-circle-check"></i>
                    <span><?= e(Session::flash('success')) ?></span>
                </div>
            <?php endif; ?>
            <?php if (Session::hasFlash('error')): ?>
                <div class="mb-6 p-4 rounded-lg border border-red-300 dark:border-red-800 bg-red-50 dark:bg-red-950/60 text-red-900 dark:text-red-200 text-sm flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?= e(Session::flash('error')) ?></span>
                </div>
            <?php endif; ?>

            <?= $content ?? '' ?>
        </main>
    </div>

    <!-- Evrensel Medya Seçici & Onay Modalları -->
    <?= View::renderPartial('components/media-picker') ?>
    <?= View::renderPartial('components/confirm-modal') ?>

    <script type="module" src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
