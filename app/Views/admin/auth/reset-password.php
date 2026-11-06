<?php
use Core\Session;
$siteTitle = setting('site_title', 'Bvlog');
$valid = !empty($valid) && !empty($token);
?>
<!DOCTYPE html>
<html lang="tr"
      x-data="{ darkMode: localStorage.getItem('bvlog_theme') === 'dark' || (!localStorage.getItem('bvlog_theme') && window.matchMedia('(prefers-color-scheme: dark)').matches) }"
      x-init="$watch('darkMode', val => { localStorage.setItem('bvlog_theme', val ? 'dark' : 'light'); document.documentElement.classList.toggle('dark', val); }); document.documentElement.classList.toggle('dark', darkMode);"
      :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Parola — <?= e($siteTitle) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="min-h-screen bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 flex items-center justify-center p-4">
    <div class="w-full max-w-sm space-y-6">
        <div class="text-center space-y-2">
            <h1 class="text-xl font-bold tracking-tight">Yeni parola belirle</h1>
            <p class="text-xs text-zinc-500">En az 8 karakter. Bu bağlantı tek kullanımlıktır.</p>
        </div>

        <?php if (Session::hasFlash('error')): ?>
            <div class="p-3.5 rounded-lg border border-red-300 bg-red-50 text-red-900 text-xs"><?= e(Session::flash('error')) ?></div>
        <?php endif; ?>

        <?php if (!$valid): ?>
            <div class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-xs text-zinc-600 dark:text-zinc-300">
                Bağlantı geçersiz veya süresi dolmuş.
                <a href="<?= e(url('/admin/forgot-password')) ?>" class="block mt-3 font-semibold text-zinc-900 dark:text-zinc-100">Yeni bağlantı iste</a>
            </div>
        <?php else: ?>
            <form action="<?= e(url('/admin/reset-password')) ?>" method="POST" class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="token" value="<?= e($token) ?>">
                <div>
                    <label class="block text-xs font-semibold mb-1.5">Yeni parola</label>
                    <input type="password" name="password" required minlength="8" autofocus class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-950">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1.5">Yeni parola (tekrar)</label>
                    <input type="password" name="password_confirm" required minlength="8" class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-950">
                </div>
                <button type="submit" class="w-full py-2.5 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold">Parolayı kaydet</button>
            </form>
        <?php endif; ?>
    </div>
    <script type="module" src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
