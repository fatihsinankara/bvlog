<?php
use Core\Session;
$siteTitle = setting('site_title', 'Bvlog');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yönetici Girişi — <?= e($siteTitle) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="min-h-screen bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 flex items-center justify-center p-4">

    <div class="w-full max-w-sm space-y-6">
        <div class="text-center space-y-2">
            <a href="<?= e(url('/')) ?>" class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-base font-extrabold">B</a>
            <h1 class="text-xl font-bold tracking-tight"><?= e($siteTitle) ?> Yönetim</h1>
            <p class="text-xs text-zinc-500">Devam etmek için yönetici hesabınızla giriş yapın.</p>
        </div>

        <?php if (Session::hasFlash('error')): ?>
            <div class="p-3.5 rounded-lg border border-red-300 bg-red-50 text-red-800 text-xs">
                <i class="fa-solid fa-circle-exclamation mr-1.5"></i><?= e(Session::flash('error')) ?>
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
                <label class="block text-xs font-semibold mb-1.5">Parola</label>
                <input type="password" name="password" required
                       class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-950 focus:outline-none focus:border-zinc-900">
            </div>

            <button type="submit" class="w-full py-2.5 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90 transition-opacity">
                Giriş Yap
            </button>
        </form>

        <div class="text-center">
            <a href="<?= e(url('/')) ?>" class="text-xs text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-200">&larr; Siteye Dön</a>
        </div>
    </div>

</body>
</html>
