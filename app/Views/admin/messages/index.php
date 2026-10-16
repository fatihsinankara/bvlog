<div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 overflow-hidden">
    <div class="px-5 py-4 border-b border-zinc-200 dark:border-zinc-800">
        <h2 class="text-sm font-bold">Gelen İletişim Mesajları (<?= count($messages) ?>)</h2>
    </div>
    <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
        <?php if (empty($messages)): ?>
            <div class="p-12 text-center text-xs text-zinc-500">Gelen kutunuzda henüz mesaj bulunmuyor.</div>
        <?php else: ?>
            <?php foreach ($messages as $m): ?>
                <div class="p-5 flex flex-col sm:flex-row sm:items-start justify-between gap-4 text-xs <?= empty($m['is_read']) ? 'bg-zinc-50/70 dark:bg-zinc-800/20' : '' ?>">
                    <div class="space-y-1.5 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <?php if (empty($m['is_read'])): ?>
                                <span class="px-2 py-0.5 rounded bg-blue-600 text-white font-bold text-[10px]">YENİ</span>
                            <?php endif; ?>
                            <strong class="text-sm font-bold"><?= e($m['subject']) ?></strong>
                            <span>&middot;</span>
                            <span class="font-medium"><?= e($m['name']) ?></span>
                            <a href="mailto:<?= e($m['email']) ?>" class="text-zinc-400 hover:underline">&lt;<?= e($m['email']) ?>&gt;</a>
                            <span>&middot;</span>
                            <span class="text-zinc-400"><?= e(format_date($m['created_at'], true)) ?></span>
                        </div>
                        <p class="text-sm text-zinc-700 dark:text-zinc-300 leading-relaxed whitespace-pre-line pt-1"><?= e($m['message']) ?></p>
                    </div>

                    <div class="flex items-center gap-1.5 shrink-0">
                        <a href="mailto:<?= e($m['email']) ?>?subject=Re: <?= e(rawurlencode($m['subject'])) ?>"
                           class="px-3 py-1.5 rounded border border-zinc-200 dark:border-zinc-700 font-semibold hover:bg-zinc-100 dark:hover:bg-zinc-800">
                            <i class="fa-solid fa-reply mr-1"></i>Yanıtla
                        </a>
                        <?php if (empty($m['is_read'])): ?>
                            <form action="<?= e(url('/admin/messages/' . $m['id'] . '/read')) ?>" method="POST">
                                <?= csrf_field() ?>
                                <button type="submit" class="px-2.5 py-1.5 rounded border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-100">
                                    Okundu İşaretle
                                </button>
                            </form>
                        <?php endif; ?>
                        <form action="<?= e(url('/admin/messages/' . $m['id'] . '/delete')) ?>"
                              method="POST"
                              data-confirm="“<?= e($m['subject']) ?>” konulu iletişim mesajını silmek istediğinize emin misiniz?"
                              data-confirm-title="Mesajı Sil">
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
