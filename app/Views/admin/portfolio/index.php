<div class="space-y-6">
    <?php if (!is_module_active('portfolio')): ?>
        <div class="p-4 rounded-xl border border-amber-300 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/50 text-amber-900 dark:text-amber-200 text-xs flex items-center justify-between">
            <span><i class="fa-solid fa-circle-info mr-1.5"></i>Portfolyo modülü şu anda ziyaretçilere kapalıdır. Projelerinizi hazırlayabilir ve istediğiniz zaman Ayarlar'dan aktifleştirebilirsiniz.</span>
            <a href="<?= e(url('/admin/settings?tab=modules')) ?>" class="font-semibold underline">Modülü Aç</a>
        </div>
    <?php endif; ?>

    <div class="flex items-center justify-between">
        <p class="text-xs text-zinc-500">Toplam <?= count($projects) ?> proje kayıtlı.</p>
        <a href="<?= e(url('/admin/portfolio/create')) ?>"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90">
            <i class="fa-solid fa-plus"></i>
            <span>Yeni Proje Ekle</span>
        </a>
    </div>

    <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950/50 text-zinc-500 font-semibold">
                        <th class="py-3 px-4">Proje</th>
                        <th class="py-3 px-4">Teknolojiler</th>
                        <th class="py-3 px-4">Durum</th>
                        <th class="py-3 px-4">Sıra</th>
                        <th class="py-3 px-4 text-right">İşlem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    <?php if (empty($projects)): ?>
                        <tr>
                            <td colspan="5" class="py-12 text-center text-zinc-500">Henüz proje eklenmemiş.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($projects as $p): ?>
                            <tr>
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <?php if (!empty($p['thumbnail'])): ?>
                                            <img src="<?= e(url($p['thumbnail'])) ?>" alt="" class="w-11 h-8 rounded object-cover border border-zinc-200 dark:border-zinc-700 shrink-0">
                                        <?php endif; ?>
                                        <div>
                                            <a href="<?= e(url('/admin/portfolio/' . $p['id'] . '/edit')) ?>" class="font-bold text-sm hover:underline block">
                                                <?= e($p['title']) ?>
                                                <?php if (!empty($p['is_featured'])): ?>
                                                    <i class="fa-solid fa-star text-amber-500 ml-1 text-xs" title="Vitrin Projesi"></i>
                                                <?php endif; ?>
                                            </a>
                                            <span class="text-[11px] text-zinc-400">/portfolio/<?= e($p['slug']) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-zinc-500"><?= e($p['tech_stack'] ?: '—') ?></td>
                                <td class="py-3.5 px-4">
                                    <?php if ($p['status'] === 'published'): ?>
                                        <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 font-semibold">Yayında</span>
                                    <?php else: ?>
                                        <span class="px-2 py-0.5 rounded bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 font-semibold">Taslak</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4"><?= (int) $p['display_order'] ?></td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <a href="<?= e(url('/admin/portfolio/' . $p['id'] . '/edit')) ?>" class="p-1.5 rounded border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <form action="<?= e(url('/admin/portfolio/' . $p['id'] . '/delete')) ?>"
                                              method="POST"
                                              data-confirm="“<?= e($p['title']) ?>” projesini silmek istediğinize emin misiniz?"
                                              data-confirm-title="Projeyi Sil"
                                              class="inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="p-1.5 rounded border border-zinc-200 dark:border-zinc-700 text-red-600 hover:bg-red-50">
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
