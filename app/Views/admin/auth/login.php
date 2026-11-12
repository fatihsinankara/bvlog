<?php
use Core\Session;
$siteTitle = setting('site_title', 'Bvlog');
?>
<!DOCTYPE html>
<html lang="tr"
      x-data="{ darkMode: localStorage.getItem('bvlog_theme') === 'dark' || (!localStorage.getItem('bvlog_theme') && window.matchMedia('(prefers-color-scheme: dark)').matches) }"
      x-init="$watch('darkMode', val => { localStorage.setItem('bvlog_theme', val ? 'dark' : 'light'); document.documentElement.classList.toggle('dark', val); }); document.documentElement.classList.toggle('dark', darkMode);"
      :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <title>Yönetici Girişi — <?= e($siteTitle) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <script>
        if (localStorage.getItem('bvlog_theme') === 'dark' || (!localStorage.getItem('bvlog_theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="min-h-screen bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 flex items-center justify-center p-4 relative transition-colors duration-200">

    <!-- Sağ Üst Tema Değiştirici -->
    <div class="fixed top-4 right-4 z-10">
        <button type="button" @click="darkMode = !darkMode"
                class="w-9 h-9 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 inline-flex items-center justify-center text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 shadow-sm transition-colors"
                title="Temayı Değiştir">
            <i x-show="!darkMode" class="fa-solid fa-moon text-xs"></i>
            <i x-show="darkMode" class="fa-solid fa-sun text-xs text-amber-400" style="display: none;"></i>
        </button>
    </div>

    <div class="w-full max-w-sm space-y-6">
        <div class="text-center space-y-2">
            <a href="<?= e(url('/')) ?>" class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-base font-extrabold">B</a>
            <h1 class="text-xl font-bold tracking-tight"><?= e($siteTitle) ?> Yönetim</h1>
            <p class="text-xs text-zinc-500">Devam etmek için yönetici hesabınızla giriş yapın.</p>
        </div>

        <?php if (Session::hasFlash('success')): ?>
            <div class="p-3.5 rounded-lg border border-emerald-300 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-900 dark:text-emerald-200 text-xs flex items-center gap-2">
                <i class="fa-solid fa-circle-check shrink-0"></i>
                <span><?= e(Session::flash('success')) ?></span>
            </div>
        <?php endif; ?>

        <?php if (Session::hasFlash('error')): ?>
            <div class="p-3.5 rounded-lg border border-red-300 dark:border-red-800 bg-red-50 dark:bg-red-950/60 text-red-900 dark:text-red-200 text-xs flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation shrink-0"></i>
                <span><?= e(Session::flash('error')) ?></span>
            </div>
        <?php endif; ?>

        <form action="<?= e(url('/admin/login')) ?>" method="POST" class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-4 shadow-sm">
            <?= csrf_field() ?>

            <div>
                <label class="block text-xs font-semibold mb-1.5">Kullanıcı Adı veya E-posta</label>
                <input type="text" name="identity" required autofocus
                       class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-950 focus:outline-none focus:border-zinc-900">
            </div>

            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-xs font-semibold">Parola</label>
                    <a href="<?= e(url('/admin/forgot-password')) ?>" tabindex="-1" class="text-[11px] text-blue-600 dark:text-blue-400 hover:underline font-medium">
                        Parolamı unuttum?
                    </a>
                </div>
                <input type="password" name="password" required
                       class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-950 focus:outline-none focus:border-zinc-900">
            </div>

            <?php \Core\Hook::doAction('render_captcha', 'login'); ?>

            <button type="submit" class="w-full py-2.5 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90 transition-opacity">
                Giriş Yap
            </button>
        </form>

        <div class="flex items-center justify-between text-xs text-zinc-500">
            <a href="<?= e(url('/')) ?>" class="hover:text-zinc-900 dark:hover:text-zinc-200">&larr; Siteye Dön</a>
            <a href="<?= e(url('/admin/forgot-password')) ?>" class="hover:text-zinc-900 dark:hover:text-zinc-200 font-medium">Parolamı unuttum?</a>
        </div>
    </div>

    <script type="module" src="<?= e(asset('js/admin.js')) ?>"></script>
</body>
</html>
