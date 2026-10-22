<?php
/**
 * @var array $collection
 * @var array $posts
 * @var int $total_parts
 * @var int $current_part
 * @var array|null $prev_post
 * @var array|null $next_post
 * @var array $current_post (mevcut blog yazısı)
 */

$color = $collection['color'] ?: '#3b82f6';
$icon = $collection['icon'] ?: 'fa-solid fa-layer-group';
$hasImage = !empty($collection['cover_image']);
$collUrl = url('/collections/' . $collection['slug']);
$threshold = 4; // 4'ten fazla bölüm varsa açılır/kapanır akordiyon yap
$isLong = count($posts) > $threshold;
?>

<div class="not-prose my-8 rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/90 shadow-sm overflow-hidden transition-all <?= !$hasImage ? 'border-l-[5px]' : '' ?>"
     style="<?= !$hasImage ? 'border-left-color: ' . e($color) . ';' : '' ?>"
     x-data="{ expanded: false }">

    <!-- Seri Üst Kartı -->
    <div class="p-5 sm:p-6 <?= $hasImage ? 'border-b border-zinc-100 dark:border-zinc-800/80' : '' ?>">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            
            <div class="flex items-start sm:items-center gap-3.5 flex-1 min-w-0">
                <?php if ($hasImage): ?>
                    <!-- Kapak Görselli Varyasyon: Şık Kompakt Küçük Görsel -->
                    <a href="<?= e($collUrl) ?>" class="relative shrink-0 w-16 h-16 sm:w-20 sm:h-20 rounded-xl overflow-hidden border border-zinc-200 dark:border-zinc-800 group shadow-sm">
                        <img src="<?= e(url($collection['cover_image'])) ?>" 
                             alt="<?= e($collection['title']) ?>" 
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                        <span class="absolute inset-0 bg-black/10 group-hover:bg-transparent transition-colors"></span>
                    </a>
                <?php else: ?>
                    <!-- Görselsiz Varyasyon: Abartısız Şık İkon Rozeti -->
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 border"
                         style="background-color: <?= e($color) ?>14; border-color: <?= e($color) ?>30; color: <?= e($color) ?>;">
                        <i class="<?= e($icon) ?> text-lg"></i>
                    </div>
                <?php endif; ?>

                <div class="space-y-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-[11px] font-bold tracking-wider uppercase"
                              style="background-color: <?= e($color) ?>18; color: <?= e($color) ?>;">
                            <i class="<?= e($icon) ?> text-[10px]"></i>
                            <span>Yazı Serisi</span>
                        </span>
                        <span class="text-xs text-zinc-400 font-medium">
                            &middot; Bölüm <?= (int) $current_part ?> / <?= (int) $total_parts ?>
                        </span>
                    </div>

                    <h2 class="text-base sm:text-lg font-bold text-zinc-900 dark:text-zinc-100 tracking-tight leading-snug">
                        <a href="<?= e($collUrl) ?>" class="hover:underline flex items-center gap-1.5">
                            <span><?= e($collection['title']) ?></span>
                            <i class="fa-solid fa-arrow-up-right-from-square text-xs text-zinc-400"></i>
                        </a>
                    </h2>

                    <?php if (!empty($collection['description'])): ?>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 line-clamp-1">
                            <?= e($collection['description']) ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sağ İlerleme Göstergesi -->
            <div class="hidden md:flex flex-col items-end shrink-0 pl-2">
                <a href="<?= e($collUrl) ?>" 
                   class="text-xs font-semibold px-3 py-1.5 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors text-zinc-700 dark:text-zinc-300">
                    Tüm Seriyi Gör &rarr;
                </a>
            </div>
        </div>
    </div>

    <!-- Bölüm Listesi -->
    <div class="bg-zinc-50/60 dark:bg-zinc-950/40 border-t border-zinc-100 dark:border-zinc-800/80">
        <ul class="divide-y divide-zinc-100 dark:divide-zinc-800/60 text-xs sm:text-sm">
            <?php foreach ($posts as $idx => $p): ?>
                <?php 
                    $partNum = $idx + 1;
                    $isCurrent = ((int) $p['id'] === (int) $current_post['id']);
                    // 4'ten uzun serilerde, ilk 3 bölüm ve mevcut bölüm haricindekiler daraltılmışken gizlenir
                    $hideInitially = $isLong && ($idx >= 3) && !$isCurrent;
                ?>
                <li class="p-3 sm:px-5 sm:py-3.5 transition-colors <?= $isCurrent ? 'bg-zinc-100/80 dark:bg-zinc-800/60 font-semibold' : 'hover:bg-zinc-100/50 dark:hover:bg-zinc-900/60' ?>"
                    <?= $hideInitially ? 'x-show="expanded" x-cloak' : '' ?>>
                    
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <!-- Sıra Rozeti -->
                            <span class="w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-bold shrink-0 border"
                                  style="<?= $isCurrent 
                                      ? 'background-color: ' . e($color) . '; color: #ffffff; border-color: ' . e($color) . ';' 
                                      : 'background-color: transparent; border-color: currentColor; opacity: 0.6;' ?>">
                                <?= $partNum ?>
                            </span>

                            <!-- Başlık -->
                            <?php if ($isCurrent): ?>
                                <span class="truncate text-zinc-900 dark:text-zinc-100 font-bold">
                                    <?= e($p['title']) ?>
                                </span>
                            <?php else: ?>
                                <a href="<?= e(post_url($p)) ?>" class="truncate text-zinc-700 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors">
                                    <?= e($p['title']) ?>
                                </a>
                            <?php endif; ?>
                        </div>

                        <!-- Durum Rozeti -->
                        <div class="shrink-0">
                            <?php if ($isCurrent): ?>
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold"
                                      style="background-color: <?= e($color) ?>22; color: <?= e($color) ?>;">
                                    <span class="w-1.5 h-1.5 rounded-full animate-pulse" style="background-color: <?= e($color) ?>;"></span>
                                    <span>Şu an okunan</span>
                                </span>
                            <?php else: ?>
                                <span class="text-zinc-400 text-xs hidden sm:inline">
                                    <i class="fa-regular fa-clock mr-1"></i><?= reading_time($p['content'] ?? '') ?> dk
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>

        <!-- Dinamik Daraltma / Genişletme Düğmesi (4+ bölüm için) -->
        <?php if ($isLong): ?>
            <button type="button" 
                    @click="expanded = !expanded" 
                    class="w-full text-center py-2.5 px-4 text-xs font-semibold text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-zinc-100/60 dark:hover:bg-zinc-900/60 transition-colors flex items-center justify-center gap-1.5 border-t border-zinc-100 dark:border-zinc-800/80">
                <span x-show="!expanded" class="flex items-center gap-1.5">
                    <i class="fa-solid fa-chevron-down text-[10px]"></i>
                    <span>Tüm bölümleri göster (<?= (int) $total_parts ?> bölüm)</span>
                </span>
                <span x-show="expanded" style="display: none;" class="flex items-center gap-1.5">
                    <i class="fa-solid fa-chevron-up text-[10px]"></i>
                    <span>Listeyi daralt</span>
                </span>
            </button>
        <?php endif; ?>
    </div>

    <!-- Önceki / Sonraki Bölüm Hızlı Gezinme Çubuğu -->
    <?php if ($prev_post || $next_post): ?>
        <div class="p-3 sm:px-5 border-t border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 flex items-center justify-between gap-3 text-xs">
            <div>
                <?php if ($prev_post): ?>
                    <a href="<?= e(post_url($prev_post)) ?>" 
                       class="inline-flex items-center gap-1.5 font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors">
                        <i class="fa-solid fa-arrow-left"></i>
                        <span class="hidden sm:inline text-zinc-400">Önceki:</span>
                        <span class="truncate max-w-[160px] sm:max-w-[220px] font-semibold"><?= e($prev_post['title']) ?></span>
                    </a>
                <?php else: ?>
                    <span class="text-zinc-400 italic">Serinin ilk bölümü</span>
                <?php endif; ?>
            </div>

            <div>
                <?php if ($next_post): ?>
                    <a href="<?= e(post_url($next_post)) ?>" 
                       class="inline-flex items-center gap-1.5 font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors">
                        <span class="hidden sm:inline text-zinc-400">Sonraki:</span>
                        <span class="truncate max-w-[160px] sm:max-w-[220px] font-semibold"><?= e($next_post['title']) ?></span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                <?php else: ?>
                    <span class="text-zinc-400 italic">Serinin son bölümü</span>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
