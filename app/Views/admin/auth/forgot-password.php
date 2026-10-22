<?php
use Core\Session;
$siteTitle = setting('site_title', 'Bvlog');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parolamı Unuttum — <?= e($siteTitle) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="min-h-screen bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 flex items-center justify-center p-4">

    <div class="w-full max-w-sm space-y-6">
        <div class="text-center space-y-2">
            <a href="<?= e(url('/')) ?>" class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-base font-extrabold">B</a>
            <h1 class="text-xl font-bold tracking-tight">Parolamı Unuttum</h1>
            <p class="text-xs text-zinc-500 max-w-xs mx-auto leading-relaxed">
                Kayıtlı kullanıcı adınızı veya e-posta adresinizi girin. Size e-posta ile yeni bir parola ileteceğiz.
            </p>
        </div>

        <?php if (Session::hasFlash('error')): ?>
            <div class="p-3.5 rounded-lg border border-red-300 dark:border-red-800 bg-red-50 dark:bg-red-950/60 text-red-900 dark:text-red-200 text-xs flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation shrink-0"></i>
                <span><?= e(Session::flash('error')) ?></span>
            </div>
        <?php endif; ?>

        <?php if (Session::hasFlash('success')): ?>
            <div class="p-3.5 rounded-lg border border-emerald-300 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-900 dark:text-emerald-200 text-xs flex items-center gap-2">
                <i class="fa-solid fa-circle-check shrink-0"></i>
                <span><?= e(Session::flash('success')) ?></span>
            </div>
        <?php endif; ?>

        <form action="<?= e(url('/admin/forgot-password')) ?>" method="POST" class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-4 shadow-sm">
            <?= csrf_field() ?>

            <div>
                <label class="block text-xs font-semibold mb-1.5">Kullanıcı Adı veya E-posta Adresi</label>
                <input type="text" name="identity" required autofocus placeholder="admin veya admin@site.com"
                       class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-950 focus:outline-none focus:border-zinc-900">
            </div>

            <?php \Core\Hook::doAction('render_captcha', 'forgot_password'); ?>

            <button type="submit" class="w-full py-2.5 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90 transition-opacity flex items-center justify-center gap-2">
                <i class="fa-regular fa-paper-plane"></i>
                <span>Yeni Parolamı Gönder</span>
            </button>
        </form>

        <div class="flex items-center justify-between text-xs text-zinc-500">
            <a href="<?= e(url('/admin/login')) ?>" class="hover:text-zinc-900 dark:hover:text-zinc-200 inline-flex items-center gap-1 font-medium">
                <i class="fa-solid fa-arrow-left text-[10px]"></i>
                <span>Giriş Ekranına Dön</span>
            </a>
            <a href="<?= e(url('/')) ?>" class="hover:text-zinc-900 dark:hover:text-zinc-200">
                Siteye Git &rarr;
            </a>
        </div>
    </div>

</body>
</html>
