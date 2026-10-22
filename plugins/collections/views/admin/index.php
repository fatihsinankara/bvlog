<?php
/**
 * @var array $collections
 * @var string $pageTitle
 */
?>

<div class="space-y-6">
    <!-- Üst Başlık ve Aksiyonlar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                <i class="fa-solid fa-layer-group text-blue-500"></i>
                <span>Yazı Koleksiyonları & Seriler</span>
            </h2>
            <p class="text-xs text-zinc-500 mt-1">
                Çok parçalı ve aynı konudaki blog yazılarını bir araya toplayan serileri yönetin.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="<?= e(url('/collections')) ?>" target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium rounded-lg border border-zinc-200 dark:border-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                <span>Vitrine Git</span>
            </a>
            <a href="<?= e(url('/admin/collections/create')) ?>"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 hover:opacity-90 transition-opacity">
                <i class="fa-solid fa-plus"></i>
                <span>Yeni Koleksiyon</span>
            </a>
        </div>
    </div>

    <!-- Koleksiyonlar Listesi -->
    <?php if (empty($collections)): ?>
        <div class="p-12 text-center rounded-2xl border border-dashed border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/40 space-y-4">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-zinc-400 text-2xl">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <div class="space-y-1">
                <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">Henüz koleksiyon oluşturulmadı</h3>
                <p class="text-xs text-zinc-500 max-w-sm mx-auto">
                    Örneğin "Ubuntu 26.04 LAMP Kurulumu" gibi bir seri oluşturarak ilgili yazılarınızı bir araya toplayabilirsiniz.
                </p>
            </div>
            <a href="<?= e(url('/admin/collections/create')) ?>"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90">
                <i class="fa-solid fa-plus"></i>
                <span>İlk Koleksiyonu Oluştur</span>
            </a>
        </div>
    <?php else: ?>
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 font-semibold text-zinc-500 uppercase tracking-wider">
                        <tr>
                            <th class="py-3 px-4">Koleksiyon / Seri</th>
                            <th class="py-3 px-4">Kalıcı Bağlantı (Slug)</th>
                            <th class="py-3 px-4 text-center">Bölüm Sayısı</th>
                            <th class="py-3 px-4 text-center">Durum</th>
                            <th class="py-3 px-4 text-right">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        <?php foreach ($collections as $c): ?>
                            <?php 
                                $color = $c['color'] ?: '#3b82f6';
                                $icon = $c['icon'] ?: 'fa-solid fa-layer-group';
                                $hasImg = !empty($c['cover_image']);
                            ?>
                            <tr class="hover:bg-zinc-50/70 dark:hover:bg-zinc-800/40 transition-colors">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <?php if ($hasImg): ?>
                                            <div class="w-12 h-12 rounded-lg overflow-hidden border border-zinc-200 dark:border-zinc-800 shrink-0 bg-zinc-100 dark:bg-zinc-950">
                                                <img src="<?= e(url($c['cover_image'])) ?>" alt="" class="w-full h-full object-cover">
                                            </div>
                                        <?php else: ?>
                                            <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 border"
                                                 style="background-color: <?= e($color) ?>14; border-color: <?= e($color) ?>30; color: <?= e($color) ?>;">
                                                <i class="<?= e($icon) ?> text-base"></i>
                                            </div>
                                        <?php endif; ?>

                                        <div>
                                            <a href="<?= e(url('/admin/collections/' . $c['id'] . '/edit')) ?>" 
                                               class="font-bold text-zinc-900 dark:text-zinc-100 hover:underline text-sm block">
                                                <?= e($c['title']) ?>
                                            </a>
                                            <?php if (!empty($c['description'])): ?>
                                                <span class="text-[11px] text-zinc-400 line-clamp-1 max-w-md">
                                                    <?= e($c['description']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-3.5 px-4 font-mono text-zinc-500">
                                    /collections/<?= e($c['slug']) ?>
                                </td>

                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">
                                        <i class="fa-solid fa-file-lines text-[10px] text-zinc-400"></i>
                                        <span><?= (int) ($c['total_posts'] ?? 0) ?> Bölüm</span>
                                    </span>
                                </td>

                                <td class="py-3.5 px-4 text-center">
                                    <?php if ($c['status'] === 'published'): ?>
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            <span>Yayında</span>
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-semibold bg-zinc-500/10 text-zinc-500">
                                            <span class="w-1.5 h-1.5 rounded-full bg-zinc-400"></span>
                                            <span>Taslak</span>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td class="py-3.5 px-4 text-right">
                                    <div class="inline-flex items-center gap-1">
                                        <?php if ($c['status'] === 'published'): ?>
                                            <a href="<?= e(url('/collections/' . $c['slug'])) ?>" target="_blank"
                                               class="p-1.5 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors"
                                               title="Sitede İncele">
                                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                            </a>
                                        <?php endif; ?>

                                        <a href="<?= e(url('/admin/collections/' . $c['id'] . '/edit')) ?>"
                                           class="p-1.5 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors"
                                           title="Düzenle & Yazıları Sırala">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>

                                        <form action="<?= e(url('/admin/collections/' . $c['id'] . '/delete')) ?>" 
                                              method="POST" 
                                              class="inline"
                                              data-confirm="Bu koleksiyonu silmek istediğinize emin misiniz? (Bağlı yazılar silinmez, sadece koleksiyon ilişkisi kaldırılır.)">
                                            <?= csrf_field() ?>
                                            <button type="submit" 
                                                    class="p-1.5 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/60 text-zinc-400 transition-colors"
                                                    title="Sil">
                                                <i class="fa-regular fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
