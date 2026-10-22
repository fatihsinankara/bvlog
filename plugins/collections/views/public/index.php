<?php
/**
 * @var array $collections
 * @var array $meta
 */
?>

<div class="space-y-10">
    <!-- Başlık & Giriş -->
    <header class="space-y-3 pb-8 border-b border-zinc-200 dark:border-zinc-800">
        <div class="flex items-center gap-2 text-xs font-semibold text-zinc-500 uppercase tracking-wider">
            <a href="<?= e(url('/blog')) ?>" class="hover:underline">Blog</a>
            <span>&middot;</span>
            <span class="text-zinc-900 dark:text-zinc-100">Koleksiyonlar & Seriler</span>
        </div>

        <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-zinc-900 dark:text-zinc-100">
            Yazı Koleksiyonları & Seriler
        </h1>

        <p class="text-base text-zinc-600 dark:text-zinc-400 max-w-2xl leading-relaxed">
            Aynı konuyu derinlemesine ele alan adım adım rehberler, kurulum notları ve çok parçalı makale serileri.
        </p>
    </header>

    <!-- Koleksiyon Kartları Grid'i -->
    <?php if (empty($collections)): ?>
        <div class="py-16 text-center rounded-2xl border border-dashed border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/30">
            <div class="w-12 h-12 mx-auto rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-zinc-400 mb-3">
                <i class="fa-solid fa-layer-group text-lg"></i>
            </div>
            <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Henüz yayınlanmış bir seri bulunmuyor.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php foreach ($collections as $c): ?>
                <?php 
                    $color = $c['color'] ?: '#3b82f6';
                    $icon = $c['icon'] ?: 'fa-solid fa-layer-group';
                    $hasImg = !empty($c['cover_image']);
                    $url = url('/collections/' . $c['slug']);
                ?>
                <article class="group rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 overflow-hidden shadow-sm hover:shadow-md hover:border-zinc-300 dark:hover:border-zinc-700 transition-all flex flex-col justify-between">
                    
                    <div>
                        <?php if ($hasImg): ?>
                            <!-- Görselli Kart Başlığı -->
                            <a href="<?= e($url) ?>" class="block relative aspect-[16/9] overflow-hidden bg-zinc-100 dark:bg-zinc-950 border-b border-zinc-100 dark:border-zinc-800">
                                <img src="<?= e(url($c['cover_image'])) ?>" 
                                     alt="<?= e($c['title']) ?>" 
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                
                                <span class="absolute top-3 left-3 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-bold text-white shadow-sm backdrop-blur-md"
                                      style="background-color: <?= e($color) ?>dd;">
                                    <i class="<?= e($icon) ?> text-[11px]"></i>
                                    <span><?= (int) ($c['published_posts'] ?? 0) ?> Bölüm</span>
                                </span>
                            </a>
                        <?php else: ?>
                            <!-- Görselsiz Minimalist Kart Başlığı (Abartısız Renk Çizgisi ve İkon) -->
                            <div class="h-2.5 w-full" style="background-color: <?= e($color) ?>;"></div>
                        <?php endif; ?>

                        <div class="p-6 space-y-3">
                            <?php if (!$hasImg): ?>
                                <div class="flex items-center justify-between gap-2">
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 border"
                                         style="background-color: <?= e($color) ?>14; border-color: <?= e($color) ?>30; color: <?= e($color) ?>;">
                                        <i class="<?= e($icon) ?> text-base"></i>
                                    </div>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400">
                                        <?= (int) ($c['published_posts'] ?? 0) ?> Bölüm
                                    </span>
                                </div>
                            <?php endif; ?>

                            <h2 class="text-lg sm:text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                <a href="<?= e($url) ?>">
                                    <?= e($c['title']) ?>
                                </a>
                            </h2>

                            <?php if (!empty($c['description'])): ?>
                                <p class="text-xs sm:text-sm text-zinc-600 dark:text-zinc-400 line-clamp-2 leading-relaxed">
                                    <?= e($c['description']) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Kart Alt Bilgi & Buton -->
                    <div class="px-6 pb-6 pt-2 border-t border-zinc-100 dark:border-zinc-800/80 flex items-center justify-between text-xs">
                        <span class="text-zinc-400 font-medium">
                            <i class="fa-solid fa-list-ol mr-1"></i>Sıralı Seri
                        </span>
                        <a href="<?= e($url) ?>" 
                           class="inline-flex items-center gap-1.5 font-bold hover:underline"
                           style="color: <?= e($color) ?>;">
                            <span>Seriyi Oku</span>
                            <i class="fa-solid fa-arrow-right text-[10px]"></i>
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
