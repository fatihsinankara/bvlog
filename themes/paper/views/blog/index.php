<?php
use Core\View;
?>
<div class="space-y-10">
    <!-- Başlık ve Arama Alanı -->
    <?php if (!empty($activeCategory)): ?>
        <div class="pb-6 border-b border-zinc-200 dark:border-zinc-800 space-y-2">
            <div class="text-[11px] font-bold uppercase tracking-wider text-zinc-400">
                <a href="<?= e(url('/blog')) ?>" class="hover:underline">&larr; Tüm Yazılar</a> / Kategori
            </div>
            <div class="flex flex-col sm:flex-row sm:items-baseline justify-between gap-2">
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-zinc-900 dark:text-zinc-100">
                    #<?= e($activeCategory['name']) ?>
                </h1>
                <span class="text-xs text-zinc-400 font-mono"><?= (int) $total ?> yazı</span>
            </div>
            <?php if (!empty($activeCategory['description'])): ?>
                <p class="text-xs sm:text-sm text-zinc-600 dark:text-zinc-400 max-w-xl">
                    <?= e($activeCategory['description']) ?>
                </p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 pb-6 border-b border-zinc-200 dark:border-zinc-800">
            <div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-zinc-900 dark:text-zinc-100">
                    Yazılar
                </h1>
                <p class="text-xs sm:text-sm text-zinc-500 mt-1">Teknik analizler, denemeler ve çalışma notları.</p>
            </div>

            <!-- Minimal Gölgesiz Arama -->
            <form action="<?= e(url('/blog')) ?>" method="GET" class="relative w-full sm:w-60">
                <input type="text" name="q" value="<?= e($search ?? '') ?>"
                       placeholder="Yazılarda ara..."
                       class="w-full px-3 py-1.5 text-xs rounded border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 focus:outline-none focus:border-zinc-500 transition-colors">
                <i class="fa-solid fa-magnifying-glass absolute right-3 top-1/2 -translate-y-1/2 text-zinc-400 text-[10px]"></i>
            </form>
        </div>
    <?php endif; ?>

    <!-- Sade Kategori Filtre Çubuğu -->
    <?php if (!empty($categories)): ?>
        <div class="flex flex-wrap items-center gap-2 text-xs">
            <a href="<?= e(url('/blog')) ?>"
               class="px-2.5 py-1 rounded transition-colors <?= empty($activeCategory) ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 font-semibold' : 'text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100 border border-zinc-200 dark:border-zinc-800' ?>">
                Tümü
            </a>
            <?php foreach ($categories as $cat): ?>
                <?php $isCurrent = !empty($activeCategory) && (int) $activeCategory['id'] === (int) $cat['id']; ?>
                <a href="<?= e(url('/category/' . $cat['slug'])) ?>"
                   class="px-2.5 py-1 rounded transition-colors <?= $isCurrent ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 font-semibold' : 'text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100 border border-zinc-200 dark:border-zinc-800' ?>">
                    #<?= e($cat['name']) ?> <span class="text-[10px] opacity-60">(<?= (int) ($cat['posts_count'] ?? 0) ?>)</span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Yazı Listesi (Editoryal Akış) -->
    <?php if (!empty($posts)): ?>
        <div class="divide-y divide-zinc-100 dark:divide-zinc-900">
            <?php foreach ($posts as $post): ?>
                <article class="py-6 first:pt-0 group space-y-1.5">
                    <div class="flex flex-col sm:flex-row sm:items-baseline justify-between gap-1">
                        <h2 class="text-base sm:text-xl font-bold text-zinc-900 dark:text-zinc-100 group-hover:text-zinc-600 dark:group-hover:text-zinc-300 transition-colors">
                            <a href="<?= e(post_url($post)) ?>">
                                <?= e($post['title']) ?>
                            </a>
                        </h2>
                        <time class="text-xs font-mono text-zinc-400 shrink-0 sm:ml-4" datetime="<?= e(date('Y-m-d', strtotime($post['published_at'] ?: $post['created_at']))) ?>">
                            <?= e(format_date($post['published_at'] ?: $post['created_at'])) ?>
                        </time>
                    </div>

                    <?php if (!empty($post['summary'])): ?>
                        <p class="text-xs sm:text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed line-clamp-2">
                            <?= e($post['summary']) ?>
                        </p>
                    <?php endif; ?>

                    <div class="flex items-center gap-3 text-[11px] text-zinc-400 pt-1">
                        <?php if (!empty($post['category_name'])): ?>
                            <a href="<?= e(url('/category/' . $post['category_slug'])) ?>" class="hover:underline font-medium text-zinc-600 dark:text-zinc-400">
                                #<?= e($post['category_name']) ?>
                            </a>
                            <span>&middot;</span>
                        <?php endif; ?>
                        <span><?= reading_time($post['content']) ?> dk okuma</span>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <!-- Sade Sayfalama (Gölgesiz, Düz Linkler) -->
        <?php if ($totalPages > 1): ?>
            <div class="pt-8 border-t border-zinc-200 dark:border-zinc-800 flex items-center justify-between text-xs font-medium">
                <?php if ($page > 1): ?>
                    <a href="<?= e(url(Request::path(), array_merge($_GET, ['page' => $page - 1]))) ?>"
                       class="text-zinc-700 dark:text-zinc-300 hover:underline">
                        &larr; Önceki Yazılar
                    </a>
                <?php else: ?>
                    <span class="text-zinc-300 dark:text-zinc-700 cursor-not-allowed">&larr; Önceki Yazılar</span>
                <?php endif; ?>

                <span class="text-zinc-400 font-mono">
                    Sayfa <?= (int) $page ?> / <?= (int) $totalPages ?>
                </span>

                <?php if ($page < $totalPages): ?>
                    <a href="<?= e(url(Request::path(), array_merge($_GET, ['page' => $page + 1]))) ?>"
                       class="text-zinc-700 dark:text-zinc-300 hover:underline">
                        Sonraki Yazılar &rarr;
                    </a>
                <?php else: ?>
                    <span class="text-zinc-300 dark:text-zinc-700 cursor-not-allowed">Sonraki Yazılar &rarr;</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="py-16 text-center text-xs text-zinc-400 space-y-2">
            <div><i class="fa-regular fa-folder-open text-2xl mb-2 block text-zinc-300 dark:text-zinc-700"></i></div>
            <p>Aradığınız kriterlere uygun yazı bulunamadı.</p>
            <?php if (!empty($search)): ?>
                <a href="<?= e(url('/blog')) ?>" class="inline-block text-zinc-900 dark:text-zinc-100 font-semibold underline mt-2">
                    Tüm yazılara geri dön
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
