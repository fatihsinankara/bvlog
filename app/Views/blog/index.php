<?php
use Core\View;
?>
<div class="space-y-8">
    <!-- Kategori Arşivi Başlığı veya Genel Blog Başlığı -->
    <?php if (!empty($activeCategory)): ?>
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 overflow-hidden bg-zinc-50/50 dark:bg-zinc-900/40">
            <?php if (!empty($activeCategory['image'])): ?>
                <div class="h-44 sm:h-56 w-full overflow-hidden border-b border-zinc-200 dark:border-zinc-800 bg-zinc-100 dark:bg-zinc-900">
                    <img src="<?= e(url($activeCategory['image'])) ?>"
                         alt="<?= e($activeCategory['name']) ?>"
                         fetchpriority="high"
                         class="w-full h-full object-cover">
                </div>
            <?php endif; ?>
            <div class="p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl border flex items-center justify-center text-lg shrink-0 bg-white dark:bg-zinc-900"
                         style="color: <?= e($activeCategory['color'] ?? '#18181b') ?>; border-color: <?= e($activeCategory['color'] ?? '#18181b') ?>;">
                        <i class="<?= e($activeCategory['icon'] ?: 'fa-solid fa-folder') ?>"></i>
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Kategori Arşivi</div>
                        <h1 class="text-2xl font-bold tracking-tight"><?= e($activeCategory['name']) ?></h1>
                        <?php if (!empty($activeCategory['description'])): ?>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1"><?= e($activeCategory['description']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <span class="text-xs font-semibold px-3 py-1 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shrink-0">
                    <?= (int) $total ?> yazı
                </span>
            </div>
        </div>
    <?php else: ?>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-zinc-200 dark:border-zinc-800">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight">Blog Yazıları</h1>
                <p class="text-sm text-zinc-500 mt-1">Tüm makaleler, teknik analizler ve kişisel notlar.</p>
            </div>

            <!-- Arama Kutusu -->
            <form action="<?= e(url('/blog')) ?>" method="GET" class="relative w-full sm:w-64">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 text-xs"></i>
                <input type="text" name="q" value="<?= e($search ?? '') ?>"
                       placeholder="Yazılarda ara..."
                       class="w-full pl-8 pr-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900 focus:outline-none focus:border-zinc-500">
            </form>
        </div>
    <?php endif; ?>

    <!-- Kategori Filtre Çubuğu -->
    <?php if (!empty($categories)): ?>
        <div class="flex flex-wrap items-center gap-2">
            <a href="<?= e(url('/blog')) ?>"
               class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded border transition-colors <?= empty($activeCategory) ? 'bg-zinc-900 text-white border-zinc-900 dark:bg-zinc-100 dark:text-zinc-900 dark:border-zinc-100' : 'border-zinc-200 dark:border-zinc-800 text-zinc-600 dark:text-zinc-400 hover:border-zinc-400' ?>">
                <span>Tümü</span>
            </a>
            <?php foreach ($categories as $cat): ?>
                <?php $isCurrent = !empty($activeCategory) && (int) $activeCategory['id'] === (int) $cat['id']; ?>
                <a href="<?= e(url('/category/' . $cat['slug'])) ?>"
                   class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium rounded border transition-opacity hover:opacity-85 <?= $isCurrent ? 'ring-2 ring-offset-1 ring-zinc-900 dark:ring-zinc-100 font-bold' : '' ?>"
                   style="color: <?= e($cat['color'] ?? '#18181b') ?>; border-color: <?= e($cat['color'] ?? '#18181b') ?>;">
                    <i class="<?= e($cat['icon'] ?: 'fa-solid fa-folder') ?> text-[11px]"></i>
                    <span><?= e($cat['name']) ?></span>
                    <span class="text-[10px] opacity-60">(<?= (int) ($cat['post_count'] ?? 0) ?>)</span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($search)): ?>
        <div class="flex items-center justify-between p-3.5 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900 text-xs">
            <span><strong>"<?= e($search) ?>"</strong> araması için <?= (int) $total ?> sonuç bulundu.</span>
            <a href="<?= e(url('/blog')) ?>" class="font-semibold underline">Aramayı Temizle</a>
        </div>
    <?php endif; ?>

    <!-- Yazı Listesi -->
    <?php if (empty($posts)): ?>
        <div class="py-16 text-center rounded-xl border border-zinc-200 dark:border-zinc-800">
            <i class="fa-regular fa-file-lines text-2xl text-zinc-400 mb-2"></i>
            <p class="text-sm text-zinc-500">Bu kriterlere uygun yayınlanmış yazı bulunamadı.</p>
        </div>
    <?php else: ?>
        <div class="divide-y divide-zinc-200 dark:divide-zinc-800 border-t border-b border-zinc-200 dark:border-zinc-800">
            <?php foreach ($posts as $post): ?>
                <article class="py-6 flex flex-col sm:flex-row sm:items-start justify-between gap-5">
                    <div class="space-y-2 flex-1">
                        <div class="flex flex-wrap items-center gap-2.5 text-xs text-zinc-500">
                            <time><?= e(format_date($post['published_at'] ?: $post['created_at'])) ?></time>
                            <span>&middot;</span>
                            <span><?= reading_time($post['content']) ?> dk okuma</span>
                            <?php if (!empty($post['category_name'])): ?>
                                <span>&middot;</span>
                                <?= View::renderPartial('components/category-badge', [
                                    'name'  => $post['category_name'],
                                    'slug'  => $post['category_slug'],
                                    'icon'  => $post['category_icon'] ?? '',
                                    'color' => $post['category_color'] ?? '#18181b',
                                ]) ?>
                            <?php endif; ?>
                        </div>
                        <h2 class="text-lg sm:text-xl font-bold tracking-tight">
                            <a href="<?= e(post_url($post)) ?>" class="hover:underline">
                                <?= e($post['title']) ?>
                            </a>
                        </h2>
                        <?php if (!empty($post['summary'])): ?>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed">
                                <?= e($post['summary']) ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($post['cover_image'])): ?>
                        <a href="<?= e(post_url($post)) ?>" class="sm:w-40 sm:h-28 rounded-lg overflow-hidden border border-zinc-200 dark:border-zinc-800 shrink-0 block bg-zinc-100 dark:bg-zinc-900">
                            <img src="<?= e(url($post['cover_image'])) ?>"
                                 alt="<?= e($post['title']) ?>"
                                 loading="lazy"
                                 class="w-full h-full object-cover">
                        </a>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>

        <!-- Sayfalama -->
        <?php if (($totalPages ?? 1) > 1): ?>
            <div class="flex items-center justify-between pt-4 text-xs">
                <div>Sayfa <?= (int) $page ?> / <?= (int) $totalPages ?></div>
                <div class="flex items-center gap-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?><?= $search ? '&q=' . urlencode($search) : '' ?>"
                           class="px-3 py-1.5 rounded border border-zinc-200 dark:border-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-900 font-medium">
                            &larr; Önceki
                        </a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?><?= $search ? '&q=' . urlencode($search) : '' ?>"
                           class="px-3 py-1.5 rounded border border-zinc-200 dark:border-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-900 font-medium">
                            Sonraki &rarr;
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
