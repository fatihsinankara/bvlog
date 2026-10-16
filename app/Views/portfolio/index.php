<div class="space-y-8">
    <div class="pb-6 border-b border-zinc-200 dark:border-zinc-800">
        <h1 class="text-2xl sm:text-3xl font-bold tracking-tight">Portfolyo & Projeler</h1>
        <p class="text-sm text-zinc-500 mt-1">Geliştirdiğim yazılım sistemleri, açık kaynak çalışmalar ve ürünler.</p>
    </div>

    <?php if (empty($projects)): ?>
        <div class="py-16 text-center rounded-xl border border-zinc-200 dark:border-zinc-800">
            <i class="fa-solid fa-briefcase text-2xl text-zinc-400 mb-2"></i>
            <p class="text-sm text-zinc-500">Henüz eklenmiş bir proje bulunmuyor.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php foreach ($projects as $proj): ?>
                <article class="rounded-xl border border-zinc-200 dark:border-zinc-800 overflow-hidden flex flex-col justify-between bg-white dark:bg-zinc-900/40 hover:border-zinc-400 dark:hover:border-zinc-700 transition-colors">
                    <div>
                        <?php if (!empty($proj['thumbnail'])): ?>
                            <a href="<?= e(url('/portfolio/' . $proj['slug'])) ?>" class="block aspect-[16/9] overflow-hidden border-b border-zinc-100 dark:border-zinc-800 bg-zinc-100 dark:bg-zinc-900">
                                <img src="<?= e(url($proj['thumbnail'])) ?>"
                                     alt="<?= e($proj['title']) ?>"
                                     loading="lazy"
                                     class="w-full h-full object-cover">
                            </a>
                        <?php endif; ?>
                        <div class="p-6">
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <h2 class="text-lg font-bold tracking-tight">
                                    <a href="<?= e(url('/portfolio/' . $proj['slug'])) ?>" class="hover:underline">
                                        <?= e($proj['title']) ?>
                                    </a>
                                </h2>
                                <?php if (!empty($proj['is_featured'])): ?>
                                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900">Seçkin</span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($proj['summary'])): ?>
                                <p class="text-xs sm:text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed mb-4">
                                    <?= e($proj['summary']) ?>
                                </p>
                            <?php endif; ?>
                            <?php if (!empty($proj['tech_stack'])): ?>
                                <div class="flex flex-wrap gap-1.5">
                                    <?php foreach (array_filter(array_map('trim', explode(',', $proj['tech_stack']))) as $tech): ?>
                                        <span class="px-2 py-0.5 text-[11px] font-medium rounded border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400">
                                            <?= e($tech) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="px-6 py-3.5 border-t border-zinc-100 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/60 flex items-center justify-between text-xs">
                        <a href="<?= e(url('/portfolio/' . $proj['slug'])) ?>" class="font-semibold hover:underline">Proje Detayları &rarr;</a>
                        <div class="flex items-center gap-3">
                            <?php if (!empty($proj['github_url'])): ?>
                                <a href="<?= e($proj['github_url']) ?>" target="_blank" rel="noopener noreferrer" class="text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100"><i class="fa-brands fa-github mr-1"></i>Kaynak Kod</a>
                            <?php endif; ?>
                            <?php if (!empty($proj['demo_url'])): ?>
                                <a href="<?= e($proj['demo_url']) ?>" target="_blank" rel="noopener noreferrer" class="text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100"><i class="fa-solid fa-arrow-up-right-from-square mr-1"></i>Canlı Demo</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
