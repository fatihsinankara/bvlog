<?php
use Core\View;

$countAll = count($posts);
$countPublished = 0;
$countScheduled = 0;
$countDraft = 0;
foreach ($posts as $item) {
    if (($item['status'] ?? '') === 'published') {
        $countPublished++;
    } elseif (($item['status'] ?? '') === 'scheduled') {
        $countScheduled++;
    } else {
        $countDraft++;
    }
}
?>
<div class="space-y-6" x-data="{ filterStatus: 'all' }">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <!-- Durum Filtre Sekmeleri -->
        <div class="flex flex-wrap items-center gap-1.5 text-xs font-semibold">
            <button type="button" @click="filterStatus = 'all'"
                    :class="filterStatus === 'all' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100'"
                    class="px-3 py-1.5 rounded-lg transition-colors">
                Tümü (<?= $countAll ?>)
            </button>
            <button type="button" @click="filterStatus = 'published'"
                    :class="filterStatus === 'published' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100'"
                    class="px-3 py-1.5 rounded-lg transition-colors">
                Yayında (<?= $countPublished ?>)
            </button>
            <button type="button" @click="filterStatus = 'scheduled'"
                    :class="filterStatus === 'scheduled' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100'"
                    class="px-3 py-1.5 rounded-lg transition-colors inline-flex items-center gap-1.5">
                <i class="fa-regular fa-clock text-[11px]"></i>
                <span>Zamanlanmış (<?= $countScheduled ?>)</span>
            </button>
            <button type="button" @click="filterStatus = 'draft'"
                    :class="filterStatus === 'draft' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100'"
                    class="px-3 py-1.5 rounded-lg transition-colors">
                Taslak (<?= $countDraft ?>)
            </button>
        </div>

        <a href="<?= e(url('/admin/posts/create')) ?>"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90 self-start sm:self-auto">
            <i class="fa-solid fa-plus"></i>
            <span>Yeni Yazı Ekle</span>
        </a>
    </div>

    <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950/50 text-zinc-500 font-semibold">
                        <th class="py-3 px-4">Başlık & Slug</th>
                        <th class="py-3 px-4">Kategori</th>
                        <th class="py-3 px-4">Durum</th>
                        <th class="py-3 px-4">Okunma</th>
                        <th class="py-3 px-4">Yayın / Zamanlama Tarihi</th>
                        <th class="py-3 px-4 text-right">İşlem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    <?php if (empty($posts)): ?>
                        <tr>
                            <td colspan="6" class="py-12 text-center text-zinc-500">Henüz blog yazısı bulunmuyor.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($posts as $p): ?>
                            <tr x-show="filterStatus === 'all' || filterStatus === <?= e(json_encode($p['status'])) ?>"
                                class="hover:bg-zinc-50/60 dark:hover:bg-zinc-800/30 transition-colors">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <?php if (!empty($p['cover_image'])): ?>
                                            <img src="<?= e(url($p['cover_image'])) ?>" alt="" loading="lazy" class="w-11 h-8 rounded object-cover border border-zinc-200 dark:border-zinc-700 shrink-0">
                                        <?php endif; ?>
                                        <div>
                                            <a href="<?= e(url('/admin/posts/' . $p['id'] . '/edit')) ?>" class="font-bold text-sm text-zinc-900 dark:text-zinc-100 hover:underline block">
                                                <?= e($p['title']) ?>
                                                <?php if (!empty($p['is_featured'])): ?>
                                                    <i class="fa-solid fa-star text-amber-500 ml-1 text-xs" title="Öne Çıkan Yazı"></i>
                                                <?php endif; ?>
                                            </a>
                                            <span class="text-[11px] text-zinc-400 font-mono"><?= e(parse_url(post_url($p), PHP_URL_PATH)) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <?php if (!empty($p['categories'])): ?>
                                        <div class="flex flex-wrap items-center gap-1">
                                            <?php foreach ($p['categories'] as $pCat): ?>
                                                <?= View::renderPartial('components/category-badge', [
                                                    'name'   => $pCat['name'],
                                                    'slug'   => '',
                                                    'icon'   => $pCat['icon'] ?? '',
                                                    'color'  => $pCat['color'] ?? '#18181b',
                                                    'linked' => false,
                                                ]) ?>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php elseif (!empty($p['category_name'])): ?>
                                        <?= View::renderPartial('components/category-badge', [
                                            'name'   => $p['category_name'],
                                            'slug'   => '',
                                            'icon'   => $p['category_icon'] ?? '',
                                            'color'  => $p['category_color'] ?? '#18181b',
                                            'linked' => false,
                                        ]) ?>
                                    <?php else: ?>
                                        <span class="text-zinc-400">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <?php if ($p['status'] === 'published'): ?>
                                        <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 font-semibold">Yayında</span>
                                    <?php elseif ($p['status'] === 'scheduled'): ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800 font-semibold" title="Seçilen tarihte otomatik yayınlanacak">
                                            <i class="fa-regular fa-clock text-[10px]"></i>
                                            <span>Zamanlanmış</span>
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 py-0.5 rounded bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 font-semibold">Taslak</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 font-medium"><?= (int) $p['views_count'] ?></td>
                                <td class="py-3.5 px-4 text-zinc-500">
                                    <?php if ($p['status'] === 'scheduled'): ?>
                                        <span class="text-amber-700 dark:text-amber-400 font-medium">
                                            <?= e(format_date($p['published_at'] ?: $p['created_at'], true)) ?>
                                        </span>
                                    <?php else: ?>
                                        <?= e(format_date($p['published_at'] ?: $p['created_at'])) ?>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <a href="<?= e(post_url($p)) ?>" target="_blank" class="p-1.5 rounded border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800" title="<?= $p['status'] === 'published' ? 'Görüntüle' : 'Önizle (Yönetici)' ?>">
                                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        </a>
                                        <a href="<?= e(url('/admin/posts/' . $p['id'] . '/edit')) ?>" class="p-1.5 rounded border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800" title="Düzenle">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <form action="<?= e(url('/admin/posts/' . $p['id'] . '/delete')) ?>"
                                              method="POST"
                                              data-confirm="“<?= e($p['title']) ?>” başlıklı yazıyı kalıcı olarak silmek istediğinize emin misiniz?"
                                              data-confirm-title="Yazıyı Sil"
                                              class="inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="p-1.5 rounded border border-zinc-200 dark:border-zinc-700 text-red-600 hover:bg-red-50 dark:hover:bg-red-950/40" title="Sil">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
