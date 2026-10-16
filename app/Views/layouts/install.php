<?php
use Core\Session;
$currentStep = $step ?? 1;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bvlog Kurulum Sihirbazı — Adım <?= (int) $currentStep ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="min-h-screen bg-zinc-50 text-zinc-900 antialiased flex flex-col justify-center items-center p-4 sm:p-8">

    <div class="w-full max-w-2xl">
        <!-- Üst Marka -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-2.5">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded bg-zinc-900 text-white text-sm font-extrabold">B</span>
                <div>
                    <h1 class="text-lg font-bold leading-none">Bvlog Kurulum Sihirbazı</h1>
                    <p class="text-xs text-zinc-500 mt-0.5">Kişisel Blog, Portfolyo ve CV Sistemi</p>
                </div>
            </div>
            <span class="text-xs font-semibold px-2.5 py-1 rounded border border-zinc-200 bg-white">Adım <?= (int) $currentStep ?> / 4</span>
        </div>

        <!-- Adım Göstergesi -->
        <div class="grid grid-cols-4 gap-2 mb-6">
            <?php
            $labels = [1 => '1. Sistem Kontrolü', 2 => '2. Veritabanı', 3 => '3. Site & Yönetici', 4 => '4. Tamamlandı'];
            foreach ($labels as $num => $lbl):
                $active = $num <= $currentStep;
            ?>
                <div class="p-2.5 rounded-lg border text-xs font-medium <?= $active ? 'border-zinc-900 bg-zinc-900 text-white' : 'border-zinc-200 bg-white text-zinc-400' ?>">
                    <?= e($lbl) ?>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (Session::hasFlash('error')): ?>
            <div class="mb-4 p-4 rounded-lg border border-red-300 bg-red-50 text-red-900 text-sm">
                <i class="fa-solid fa-circle-exclamation mr-2"></i><?= e(Session::flash('error')) ?>
            </div>
        <?php endif; ?>

        <!-- Ana Kart -->
        <div class="rounded-xl border border-zinc-200 bg-white p-6 sm:p-8 shadow-sm">
            <?= $content ?? '' ?>
        </div>

        <p class="text-center text-xs text-zinc-400 mt-6">Bvlog v1.0 — Paylaşımlı Hosting Uyumlu & Güvenlik Odaklı Altyapı</p>
    </div>

</body>
</html>
