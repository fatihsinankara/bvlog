<?php
use Core\View;
?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-xs text-zinc-500">Toplam <?= count($posts) ?> blog yazısı kayıtlı.</p>
        </div>
        <a href="<?= e(url('/admin/posts/create')) ?>"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90">
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
                        <th class="py-3 px-4">Tarih</th>
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
                            <tr class="hover:bg-zinc-50/60 dark:hover:bg-zinc-800/30 transition-colors">
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
                                            <span class="text-[11px] text-zinc-400">/blog/<?= e($p['slug']) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <?php if (!empty($p['category_name'])): ?>
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
                                    <?php else: ?>
                                        <span class="px-2 py-0.5 rounded bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 font-semibold">Taslak</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 font-medium"><?= (int) $p['views_count'] ?></td>
                                <td class="py-3.5 px-4 text-zinc-500"><?= e(format_date($p['published_at'] ?: $p['created_at'])) ?></td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <a href="<?= e(url('/blog/' . $p['slug'])) ?>" target="_blank" class="p-1.5 rounded border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800" title="Görüntüle">
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
