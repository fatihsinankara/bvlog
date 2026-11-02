<div class="max-w-lg mx-auto py-12">
    <div class="p-6 sm:p-8 rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-center space-y-4 shadow-sm">
        <?php if (($status ?? '') === 'verified'): ?>
            <div class="w-12 h-12 rounded-2xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 inline-flex items-center justify-center text-xl mx-auto">
                <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
            </div>
        <?php elseif (($status ?? '') === 'unsubscribed'): ?>
            <div class="w-12 h-12 rounded-2xl bg-amber-100 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 inline-flex items-center justify-center text-xl mx-auto">
                <i class="fa-solid fa-envelope-open" aria-hidden="true"></i>
            </div>
        <?php else: ?>
            <div class="w-12 h-12 rounded-2xl bg-red-100 dark:bg-red-950/60 text-red-600 dark:text-red-400 inline-flex items-center justify-center text-xl mx-auto">
                <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
            </div>
        <?php endif; ?>

        <h1 class="text-lg sm:text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
            <?= e($heading ?? 'E-posta Bülteni') ?>
        </h1>

        <p class="text-xs sm:text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed">
            <?= $message ?? '' ?>
        </p>

        <div class="pt-3">
            <a href="<?= e(url('/')) ?>" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90 transition-opacity">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                <span>Anasayfaya Dön</span>
            </a>
        </div>
    </div>
</div>
