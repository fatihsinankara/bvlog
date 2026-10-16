<article class="space-y-8">
    <header class="space-y-4 pb-6 border-b border-zinc-200 dark:border-zinc-800">
        <div class="text-xs text-zinc-500">
            <a href="<?= e(url('/portfolio')) ?>" class="hover:text-zinc-900 dark:hover:text-zinc-100 font-medium">&larr; Portfolyo</a>
        </div>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <h1 class="text-2xl sm:text-4xl font-bold tracking-tight"><?= e($project['title']) ?></h1>
            <div class="flex items-center gap-2 shrink-0">
                <?php if (!empty($project['demo_url'])): ?>
                    <a href="<?= e($project['demo_url']) ?>" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                        <span>Canlı Demo</span>
                    </a>
                <?php endif; ?>
                <?php if (!empty($project['github_url'])): ?>
                    <a href="<?= e($project['github_url']) ?>" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-700 text-xs font-semibold hover:bg-zinc-100 dark:hover:bg-zinc-900">
                        <i class="fa-brands fa-github"></i>
                        <span>GitHub</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($project['summary'])): ?>
            <p class="text-base text-zinc-600 dark:text-zinc-400 leading-relaxed"><?= e($project['summary']) ?></p>
        <?php endif; ?>

        <?php if (!empty($project['tech_stack'])): ?>
            <div class="flex flex-wrap gap-1.5 pt-1">
                <?php foreach (array_filter(array_map('trim', explode(',', $project['tech_stack']))) as $tech): ?>
                    <span class="px-2.5 py-0.5 text-xs font-medium rounded border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900">
                        <?= e($tech) ?>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </header>

    <?php if (!empty($project['thumbnail'])): ?>
        <figure class="rounded-xl overflow-hidden border border-zinc-200 dark:border-zinc-800 bg-zinc-100 dark:bg-zinc-900">
            <img src="<?= e(url($project['thumbnail'])) ?>"
                 alt="<?= e($project['title']) ?>"
                 fetchpriority="high"
                 width="1080"
                 class="w-full h-auto object-cover">
        </figure>
    <?php endif; ?>

    <div class="bvlog-article-content prose prose-zinc dark:prose-invert max-w-none">
        <?= $project['description'] ?>
    </div>
</article>
