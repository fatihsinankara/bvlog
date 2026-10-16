<?php
use Core\View;
?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-xs text-zinc-500">Toplam <?= count($categories) ?> kategori kayıtlı.</p>
        </div>
        <a href="<?= e(url('/admin/categories/create')) ?>"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90">
            <i class="fa-solid fa-plus"></i>
            <span>Yeni Kategori Ekle</span>
        </a>
    </div>

    <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950/50 text-zinc-500 font-semibold">
                        <th class="py-3 px-4">Görsel / İkon</th>
                        <th class="py-3 px-4">Kategori Rozeti & Slug</th>
                        <th class="py-3 px-4">Açıklama</th>
                        <th class="py-3 px-4">Renk Kodu</th>
                        <th class="py-3 px-4">Yazı Sayısı</th>
                        <th class="py-3 px-4 text-right">İşlem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="6" class="py-12 text-center text-zinc-500">Henüz kategori bulunmuyor.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categories as $c): ?>
                            <tr class="hover:bg-zinc-50/60 dark:hover:bg-zinc-800/30 transition-colors">
                                <td class="py-3.5 px-4 w-16">
                                    <?php if (!empty($c['image'])): ?>
                                        <img src="<?= e(url($c['image'])) ?>" alt="" class="w-12 h-9 rounded-lg object-cover border border-zinc-200 dark:border-zinc-700">
                                    <?php else: ?>
                                        <div class="w-10 h-10 rounded-lg border flex items-center justify-center"
                                             style="color: <?= e($c['color']) ?>; border-color: <?= e($c['color']) ?>;">
                                            <i class="<?= e($c['icon'] ?: 'fa-solid fa-folder') ?>"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="space-y-1">
                                        <div>
                                            <?= View::renderPartial('components/category-badge', [
                                                'name'   => $c['name'],
                                                'slug'   => $c['slug'],
                                                'icon'   => $c['icon'],
                                                'color'  => $c['color'],
                                                'linked' => false,
                                            ]) ?>
                                        </div>
                                        <div class="text-[11px] font-mono text-zinc-400">/category/<?= e($c['slug']) ?></div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-zinc-600 dark:text-zinc-400 max-w-xs truncate">
                                    <?= e($c['description'] ?: '—') ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center gap-1.5 font-mono text-[11px] px-2 py-0.5 rounded border border-zinc-200 dark:border-zinc-800">
                                        <span class="w-2.5 h-2.5 rounded-full" style="background-color: <?= e($c['color']) ?>;"></span>
                                        <?= e($c['color']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 font-semibold">
                                    <?= (int) ($c['post_count'] ?? 0) ?> yazı
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <a href="<?= e(url('/category/' . $c['slug'])) ?>" target="_blank"
                                           class="p-1.5 rounded border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800" title="Görüntüle">
                                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        </a>
                                        <a href="<?= e(url('/admin/categories/' . $c['id'] . '/edit')) ?>"
                                           class="p-1.5 rounded border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800" title="Düzenle">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <form action="<?= e(url('/admin/categories/' . $c['id'] . '/delete')) ?>"
                                              method="POST"
                                              data-confirm="“<?= e($c['name']) ?>” kategorisini silmek istediğinize emin misiniz? Bu kategorideki yazılar silinmez, kategorisiz kalır."
                                              data-confirm-title="Kategoriyi Sil"
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
