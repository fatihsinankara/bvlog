<?php
$allPassed = true;
foreach ($checks as $c) {
    if (!$c['passed']) {
        $allPassed = false;
    }
}
?>
<div>
    <h2 class="text-base font-bold mb-1">Sunucu & Sistem Gereksinimleri Kontrolü</h2>
    <p class="text-xs text-zinc-500 mb-6">Bvlog'un paylaşımlı hosting veya sunucunuzda sorunsuz çalışması için gereken bileşenlerin durumu:</p>

    <div class="divide-y divide-zinc-100 border border-zinc-200 rounded-lg mb-6">
        <?php foreach ($checks as $item): ?>
            <div class="px-4 py-3 flex items-center justify-between text-sm">
                <div>
                    <div class="font-medium text-zinc-900"><?= e($item['label']) ?></div>
                    <div class="text-xs text-zinc-500"><?= e($item['detail']) ?></div>
                </div>
                <?php if ($item['passed']): ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <i class="fa-solid fa-check"></i> Hazır
                    </span>
                <?php else: ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded text-xs font-semibold bg-red-50 text-red-700 border border-red-200">
                        <i class="fa-solid fa-xmark"></i> Eksik
                    </span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="flex items-center justify-end">
        <?php if ($allPassed): ?>
            <a href="<?= e(url('/install?step=2')) ?>"
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-zinc-900 text-white text-sm font-semibold hover:opacity-90 transition-opacity">
                <span>Veritabanı Ayarlarına Geç</span>
                <i class="fa-solid fa-arrow-right text-xs"></i>
            </a>
        <?php else: ?>
            <a href="<?= e(url('/install?step=1')) ?>"
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg border border-zinc-300 text-sm font-semibold hover:bg-zinc-50">
                <i class="fa-solid fa-rotate-right"></i>
                <span>Yeniden Kontrol Et</span>
            </a>
        <?php endif; ?>
    </div>
</div>
