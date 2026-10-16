<?php
use Core\View;
?>
<div class="space-y-8">
    <?php if (!empty($quarantinedPlugins)): ?>
        <div class="p-4 rounded-xl border border-amber-300 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/50 text-amber-900 dark:text-amber-200 text-xs flex items-center justify-between gap-4">
            <div>
                <strong class="font-bold block mb-0.5"><i class="fa-solid fa-triangle-exclamation mr-1.5"></i>Karantinaya Alınmış Eklenti Uyarısı</strong>
                <span>Sistem kararlılığını korumak için hata üreten <?= count($quarantinedPlugins) ?> eklenti otomatik olarak devre dışı bırakıldı.</span>
            </div>
            <a href="<?= e(url('/admin/plugins')) ?>" class="px-3 py-1.5 rounded border border-amber-400 font-semibold shrink-0 hover:bg-amber-100 dark:hover:bg-amber-900">İncele</a>
        </div>
    <?php endif; ?>

    <!-- İstatistik Kartları -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="p-5 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
            <div class="flex items-center justify-between text-xs text-zinc-500 mb-2">
                <span>Yayınlanan Yazılar</span>
                <i class="fa-solid fa-pen-nib"></i>
            </div>
            <div class="text-2xl font-bold"><?= (int) $stats['posts_published'] ?> <span class="text-xs font-normal text-zinc-400">/ <?= (int) $stats['posts_total'] ?></span></div>
        </div>

        <div class="p-5 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
            <div class="flex items-center justify-between text-xs text-zinc-500 mb-2">
                <span>Toplam Okunma</span>
                <i class="fa-regular fa-eye"></i>
            </div>
            <div class="text-2xl font-bold"><?= number_format((int) $stats['views_total']) ?></div>
        </div>

        <div class="p-5 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
            <div class="flex items-center justify-between text-xs text-zinc-500 mb-2">
                <span>Medya Galerisi</span>
                <i class="fa-regular fa-images"></i>
            </div>
            <div class="text-2xl font-bold"><?= (int) $stats['media_count'] ?> <span class="text-xs font-normal text-zinc-400">görsel</span></div>
        </div>

        <div class="p-5 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
            <div class="flex items-center justify-between text-xs text-zinc-500 mb-2">
                <span>Bekleyen Etkileşim</span>
                <i class="fa-regular fa-bell"></i>
            </div>
            <div class="text-2xl font-bold"><?= (int) ($stats['pending_comments'] + $stats['unread_messages']) ?></div>
        </div>
    </div>

    <!-- Hızlı Aksiyonlar -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <a href="<?= e(url('/admin/posts/create')) ?>" class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 hover:border-zinc-400 transition-colors flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 flex items-center justify-center shrink-0">
                <i class="fa-solid fa-plus"></i>
            </div>
            <div>
                <div class="text-sm font-bold">Yeni Blog Yazısı Yaz</div>
                <div class="text-xs text-zinc-500">Yeni bir içerik oluştur ve yayınla</div>
            </div>
        </a>

        <a href="<?= e(url('/admin/media')) ?>" class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 hover:border-zinc-400 transition-colors flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-lg border border-zinc-200 dark:border-zinc-700 flex items-center justify-center shrink-0">
                <i class="fa-regular fa-images"></i>
            </div>
            <div>
                <div class="text-sm font-bold">Medya Galerisini Yönet</div>
                <div class="text-xs text-zinc-500">Görsel yükle ve alt metinleri düzenle</div>
            </div>
        </a>

        <a href="<?= e(url('/admin/settings?tab=homepage')) ?>" class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 hover:border-zinc-400 transition-colors flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-lg border border-zinc-200 dark:border-zinc-700 flex items-center justify-center shrink-0">
                <i class="fa-solid fa-table-columns"></i>
            </div>
            <div>
                <div class="text-sm font-bold">Anasayfa Yerleşimi</div>
                <div class="text-xs text-zinc-500">Hero stili ve blok sıralamasını özelleştir</div>
            </div>
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Son Yazılar -->
        <div class="lg:col-span-7 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 overflow-hidden">
            <div class="px-5 py-4 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                <h2 class="text-sm font-bold">Son Eklenen Yazılar</h2>
                <a href="<?= e(url('/admin/posts')) ?>" class="text-xs font-semibold hover:underline">Tümü &rarr;</a>
            </div>
            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                <?php if (empty($recentPosts)): ?>
                    <div class="p-6 text-center text-xs text-zinc-500">Henüz yazı eklenmemiş.</div>
                <?php else: ?>
                    <?php foreach ($recentPosts as $p): ?>
                        <div class="px-5 py-3.5 flex items-center justify-between gap-4 text-xs">
                            <div class="min-w-0 flex-1">
                                <a href="<?= e(url('/admin/posts/' . $p['id'] . '/edit')) ?>" class="font-semibold text-sm text-zinc-900 dark:text-zinc-100 hover:underline truncate block">
                                    <?= e($p['title']) ?>
                                </a>
                                <div class="flex items-center gap-2 text-zinc-500 mt-0.5">
                                    <span><?= e(format_date($p['created_at'])) ?></span>
                                    <span>&middot;</span>
                                    <span><?= (int) $p['views_count'] ?> okunma</span>
                                    <?php if (!empty($p['category_name'])): ?>
                                        <span>&middot;</span>
                                        <span><?= e($p['category_name']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <?php if ($p['status'] === 'published'): ?>
                                    <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 font-medium">Yayında</span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 rounded bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 font-medium">Taslak</span>
                                <?php endif; ?>
                                <a href="<?= e(url('/admin/posts/' . $p['id'] . '/edit')) ?>" class="p-1.5 rounded border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Son Yorumlar -->
        <div class="lg:col-span-5 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 overflow-hidden">
            <div class="px-5 py-4 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                <h2 class="text-sm font-bold">Son Yorumlar</h2>
                <a href="<?= e(url('/admin/comments')) ?>" class="text-xs font-semibold hover:underline">Yönet &rarr;</a>
            </div>
            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                <?php if (empty($recentComments)): ?>
                    <div class="p-6 text-center text-xs text-zinc-500">Henüz yorum bulunmuyor.</div>
                <?php else: ?>
                    <?php foreach ($recentComments as $c): ?>
                        <div class="px-5 py-3.5 space-y-1 text-xs">
                            <div class="flex items-center justify-between">
                                <strong class="font-semibold"><?= e($c['author_name']) ?></strong>
                                <span class="text-[11px] <?= $c['status'] === 'pending' ? 'text-amber-600 font-semibold' : 'text-zinc-400' ?>">
                                    <?= $c['status'] === 'pending' ? 'Onay Bekliyor' : 'Onaylı' ?>
                                </span>
                            </div>
                            <p class="text-zinc-600 dark:text-zinc-400 line-clamp-2"><?= e($c['content']) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
