<div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 overflow-hidden">
    <div class="px-5 py-4 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
        <h2 class="text-sm font-bold">Ziyaretçi Yorumları (<?= count($comments) ?>)</h2>
    </div>
    <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
        <?php if (empty($comments)): ?>
            <div class="p-12 text-center text-xs text-zinc-500">Henüz yorum bulunmuyor.</div>
        <?php else: ?>
            <?php foreach ($comments as $c): ?>
                <div class="p-5 flex flex-col sm:flex-row sm:items-start justify-between gap-4 text-xs">
                    <div class="space-y-1.5 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <strong class="text-sm font-bold"><?= e($c['author_name']) ?></strong>
                            <span class="text-zinc-400">&lt;<?= e($c['author_email']) ?>&gt;</span>
                            <span>&middot;</span>
                            <span class="text-zinc-400"><?= e(format_date($c['created_at'], true)) ?></span>
                            <?php if ($c['status'] === 'pending'): ?>
                                <span class="px-2 py-0.5 rounded bg-amber-50 text-amber-700 border border-amber-200 font-semibold">Onay Bekliyor</span>
                            <?php elseif ($c['status'] === 'approved'): ?>
                                <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 font-semibold">Onaylı</span>
                            <?php else: ?>
                                <span class="px-2 py-0.5 rounded bg-red-50 text-red-700 border border-red-200 font-semibold">Spam</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-sm text-zinc-700 dark:text-zinc-300 leading-relaxed"><?= e($c['content']) ?></p>
                        <?php if (!empty($c['post_title'])): ?>
                            <div class="text-zinc-400">
                                Yazı: <a href="<?= e(url('/blog/' . $c['post_slug'])) ?>" target="_blank" class="underline hover:text-zinc-700"><?= e($c['post_title']) ?></a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="flex items-center gap-1.5 shrink-0">
                        <?php if ($c['status'] !== 'approved'): ?>
                            <form action="<?= e(url('/admin/comments/' . $c['id'] . '/status')) ?>" method="POST">
                                <?= csrf_field() ?>
                                <input type="hidden" name="status" value="approved">
                                <button type="submit" class="px-3 py-1.5 rounded bg-emerald-600 text-white font-semibold hover:opacity-90">
                                    <i class="fa-solid fa-check mr-1"></i>Onayla
                                </button>
                            </form>
                        <?php endif; ?>
                        <?php if ($c['status'] !== 'pending'): ?>
                            <form action="<?= e(url('/admin/comments/' . $c['id'] . '/status')) ?>" method="POST">
                                <?= csrf_field() ?>
                                <input type="hidden" name="status" value="pending">
                                <button type="submit" class="px-2.5 py-1.5 rounded border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-100">
                                    Beklemeye Al
                                </button>
                            </form>
                        <?php endif; ?>
                        <form action="<?= e(url('/admin/comments/' . $c['id'] . '/delete')) ?>"
                              method="POST"
                              data-confirm="“<?= e($c['author_name']) ?>” tarafından yazılan bu yorumu silmek istediğinize emin misiniz?"
                              data-confirm-title="Yorumu Sil">
                            <?= csrf_field() ?>
                            <button type="submit" class="p-1.5 rounded border border-zinc-200 dark:border-zinc-700 text-red-600 hover:bg-red-50">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
