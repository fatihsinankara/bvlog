<?php
/**
 * @var array $collection
 * @var array $posts
 * @var int $totalReadingTime
 * @var array $meta
 */

$color = $collection['color'] ?: '#3b82f6';
$icon = $collection['icon'] ?: 'fa-solid fa-layer-group';
$hasImg = !empty($collection['cover_image']);
?>

<div class="space-y-10">
    <!-- Üst Gezinme / Breadcrumbs -->
    <nav class="flex items-center gap-2 text-xs text-zinc-500 font-medium">
        <a href="<?= e(url('/blog')) ?>" class="hover:underline">Blog</a>
        <span>&middot;</span>
        <a href="<?= e(url('/collections')) ?>" class="hover:underline">Koleksiyonlar</a>
        <span>&middot;</span>
        <span class="text-zinc-900 dark:text-zinc-100 font-semibold truncate max-w-xs"><?= e($collection['title']) ?></span>
    </nav>

    <!-- Koleksiyon Başlık Kartı / Hero -->
    <header class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 overflow-hidden shadow-sm <?= !$hasImg ? 'border-l-[6px]' : '' ?>"
            style="<?= !$hasImg ? 'border-left-color: ' . e($color) . ';' : '' ?>">
        
        <?php if ($hasImg): ?>
            <!-- Geniş Kapak Görseli -->
            <div class="relative w-full aspect-[21/9] max-h-72 overflow-hidden bg-zinc-100 dark:bg-zinc-950 border-b border-zinc-100 dark:border-zinc-800">
                <img src="<?= e(url($collection['cover_image'])) ?>" 
                     alt="<?= e($collection['title']) ?>" 
                     class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                <div class="absolute bottom-4 left-6 right-6">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-bold text-white backdrop-blur-md"
                          style="background-color: <?= e($color) ?>ee;">
                        <i class="<?= e($icon) ?>"></i>
                        <span>Yazı Serisi</span>
                    </span>
                </div>
            </div>
        <?php endif; ?>

        <div class="p-6 sm:p-8 space-y-4">
            <?php if (!$hasImg): ?>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-md text-xs font-bold"
                     style="background-color: <?= e($color) ?>18; color: <?= e($color) ?>;">
                    <i class="<?= e($icon) ?>"></i>
                    <span>Yazı Serisi</span>
                </div>
            <?php endif; ?>

            <h1 class="text-2xl sm:text-4xl font-extrabold tracking-tight text-zinc-900 dark:text-zinc-100 leading-tight">
                <?= e($collection['title']) ?>
            </h1>

            <?php if (!empty($collection['description'])): ?>
                <p class="text-sm sm:text-base text-zinc-600 dark:text-zinc-400 leading-relaxed max-w-3xl">
                    <?= e($collection['description']) ?>
                </p>
            <?php endif; ?>

            <!-- İstatistik Rozetleri -->
            <div class="pt-4 border-t border-zinc-100 dark:border-zinc-800/80 flex flex-wrap items-center gap-4 text-xs font-semibold text-zinc-600 dark:text-zinc-400">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-zinc-100 dark:bg-zinc-800">
                    <i class="fa-solid fa-layer-group text-zinc-400"></i>
                    <span>Toplam <?= count($posts) ?> Bölüm</span>
                </span>

                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-zinc-100 dark:bg-zinc-800">
                    <i class="fa-regular fa-clock text-zinc-400"></i>
                    <span>Toplam ~<?= (int) $totalReadingTime ?> dk okuma süresi</span>
                </span>
            </div>
        </div>
    </header>

    <!-- Bölümler Akışı (Kronolojik Sıralı Liste) -->
    <section class="space-y-4">
        <div class="flex items-center justify-between pb-2 border-b border-zinc-200 dark:border-zinc-800">
            <h2 class="text-sm font-bold uppercase tracking-wider text-zinc-400 flex items-center gap-2">
                <i class="fa-solid fa-list-check text-blue-500"></i>
                <span>Serinin Tüm Bölümleri</span>
            </h2>
            <span class="text-xs text-zinc-400">Sırasıyla Okumanız Önerilir</span>
        </div>

        <?php if (empty($posts)): ?>
            <p class="text-xs text-zinc-400 italic py-6 text-center">Bu koleksiyona henüz yayınlanmış yazı eklenmedi.</p>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($posts as $idx => $p): ?>
                    <?php 
                        $partNum = $idx + 1;
                        $pUrl = post_url($p);
                    ?>
                    <article class="p-5 sm:p-6 rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 hover:border-zinc-300 dark:hover:border-zinc-700 transition-all shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 group">
                        
                        <div class="flex items-start sm:items-center gap-4 flex-1 min-w-0">
                            <!-- Bölüm Numarası Rozeti -->
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-sm font-black shrink-0 border transition-transform group-hover:scale-105"
                                 style="background-color: <?= e($color) ?>14; border-color: <?= e($color) ?>35; color: <?= e($color) ?>;">
                                <?= $partNum ?>
                            </div>

                            <div class="space-y-1 min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2 text-xs text-zinc-400">
                                    <span class="font-bold uppercase tracking-wider text-[11px]" style="color: <?= e($color) ?>;">
                                        Bölüm <?= $partNum ?>
                                    </span>
                                    <span>&middot;</span>
                                    <time datetime="<?= e(date('Y-m-d', strtotime($p['published_at'] ?: $p['created_at']))) ?>">
                                        <?= format_date($p['published_at'] ?: $p['created_at']) ?>
                                    </time>
                                    <span>&middot;</span>
                                    <span><i class="fa-regular fa-clock mr-1"></i><?= reading_time($p['content'] ?? '') ?> dk</span>
                                </div>

                                <h3 class="text-base sm:text-lg font-bold text-zinc-900 dark:text-zinc-100 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                    <a href="<?= e($pUrl) ?>" class="hover:underline">
                                        <?= e($p['title']) ?>
                                    </a>
                                </h3>

                                <?php if (!empty($p['summary'])): ?>
                                    <p class="text-xs sm:text-sm text-zinc-600 dark:text-zinc-400 line-clamp-2 leading-relaxed">
                                        <?= e($p['summary']) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Oku Butonu -->
                        <div class="shrink-0 w-full sm:w-auto text-right">
                            <a href="<?= e($pUrl) ?>" 
                               class="inline-flex items-center justify-center gap-2 w-full sm:w-auto px-4 py-2 rounded-xl text-xs font-bold border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-800 dark:text-zinc-200 transition-colors">
                                <span>Bölümü Oku</span>
                                <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
