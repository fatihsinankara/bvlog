<div class="py-20 text-center space-y-4">
    <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900 text-xl font-extrabold">
        <?= (int) ($code ?? 404) ?>
    </div>
    <h1 class="text-2xl font-bold tracking-tight">Sayfa Bulunamadı</h1>
    <p class="text-sm text-zinc-500 max-w-md mx-auto">
        <?= e($message ?? 'Aradığınız içerik silinmiş, taşınmış veya geçici olarak devre dışı bırakılmış olabilir.') ?>
    </p>
    <div class="pt-4">
        <a href="<?= e(url('/')) ?>" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Anasayfaya Dön</span>
        </a>
    </div>
</div>
