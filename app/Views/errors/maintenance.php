<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bakım Çalışması — <?= e(setting('site_title', 'Bvlog')) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="min-h-screen bg-zinc-50 text-zinc-900 flex items-center justify-center p-6">
    <div class="max-w-md w-full p-8 rounded-xl border border-zinc-200 bg-white text-center space-y-4">
        <div class="w-12 h-12 rounded-xl border border-zinc-200 bg-zinc-50 inline-flex items-center justify-center text-lg">
            <i class="fa-solid fa-wrench"></i>
        </div>
        <h1 class="text-xl font-bold">Kısa Süreli Bakımdayız</h1>
        <p class="text-sm text-zinc-600 leading-relaxed">
            <?= e(setting('maintenance_message', 'Sitemizde planlı bakım çalışması yapılmaktadır. Lütfen kısa süre sonra tekrar ziyaret edin.')) ?>
        </p>
    </div>
</body>
</html>
