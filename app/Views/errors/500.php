<div class="py-20 text-center space-y-4">
    <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl border border-red-200 dark:border-red-900/60 bg-red-50 dark:bg-red-950/40 text-red-600 dark:text-red-400 text-xl font-extrabold">
        <?= (int) ($code ?? 500) ?>
    </div>
    <h1 class="text-2xl font-bold tracking-tight">Bir Sorun Oluştu</h1>
    <p class="text-sm text-zinc-500 max-w-md mx-auto">
        <?= e($message ?? 'İşleminiz gerçekleştirilirken beklenmedik bir sunucu hatası oluştu. Lütfen sayfayı yenileyin veya biraz sonra tekrar deneyin.') ?>
    </p>
    <div class="pt-4 flex items-center justify-center gap-3">
        <a href="<?= e(url('/')) ?>" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Anasayfaya Dön</span>
        </a>
    </div>
</div>
