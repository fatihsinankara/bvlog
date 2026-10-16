<div class="space-y-6">
    <?php if (!empty($isSafeMode)): ?>
        <div class="p-4 rounded-xl border border-amber-300 bg-amber-50 text-amber-900 text-xs flex items-center justify-between">
            <span><i class="fa-solid fa-shield-halved mr-1.5"></i><strong>Güvenli Mod (Safe Mode) Aktif:</strong> Tüm üçüncü parti eklentilerin çalıştırılması geçici olarak durduruldu.</span>
            <a href="<?= e(url('/admin/plugins')) ?>" class="font-semibold underline">Normal Moda Dön</a>
        </div>
    <?php endif; ?>

    <!-- Üst Bilgilendirme & Zip Yükleme -->
    <div class="p-5 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="space-y-1">
            <h2 class="text-sm font-bold"><i class="fa-solid fa-plug mr-1.5"></i>Karantina Korumalı Eklenti (Plugin) Sistemi</h2>
            <p class="text-xs text-zinc-500">
                Hatalı kod içeren bir eklenti yüklenirse sistem asla çökmez; eklenti otomatik olarak karantinaya alınır ve devre dışı bırakılır.
            </p>
        </div>

        <form action="<?= e(url('/admin/plugins/upload')) ?>" method="POST" enctype="multipart/form-data" class="flex items-center gap-2 shrink-0">
            <?= csrf_field() ?>
            <input type="file" name="plugin_zip" accept=".zip" required
                   class="text-xs file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-zinc-100 file:text-zinc-900 dark:file:bg-zinc-800 dark:file:text-zinc-100 cursor-pointer">
            <button type="submit" class="px-4 py-1.5 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90">
                <i class="fa-solid fa-upload mr-1"></i>Eklenti (.zip) Yükle
            </button>
        </form>
    </div>

    <!-- Eklenti Listesi -->
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 divide-y divide-zinc-100 dark:divide-zinc-800">
        <?php if (empty($plugins)): ?>
            <div class="p-12 text-center text-xs text-zinc-500">Henüz kurulu eklenti bulunmuyor.</div>
        <?php else: ?>
            <?php foreach ($plugins as $pl): ?>
                <div class="p-5 space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2.5">
                                <h3 class="text-sm font-bold"><?= e($pl['name']) ?></h3>
                                <span class="text-xs text-zinc-400">v<?= e($pl['version']) ?> · <?= e($pl['author']) ?></span>
                                <?php if (!empty($pl['quarantine'])): ?>
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-red-100 text-red-700 border border-red-200">
                                        <i class="fa-solid fa-triangle-exclamation mr-1"></i>Karantinada (Hatalı)
                                    </span>
                                <?php elseif ($pl['is_active']): ?>
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-emerald-50 text-emerald-700 border border-emerald-200">Aktif</span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 text-[10px] font-semibold rounded bg-zinc-100 dark:bg-zinc-800 text-zinc-500">Pasif</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-xs text-zinc-600 dark:text-zinc-400"><?= e($pl['description']) ?></p>
                        </div>

                        <div class="flex items-center gap-2 shrink-0 text-xs">
                            <form action="<?= e(url('/admin/plugins/toggle')) ?>" method="POST">
                                <?= csrf_field() ?>
                                <input type="hidden" name="plugin_id" value="<?= e($pl['id']) ?>">
                                <?php if ($pl['is_active']): ?>
                                    <input type="hidden" name="action" value="deactivate">
                                    <button type="submit" class="px-3.5 py-1.5 rounded-lg border border-zinc-300 dark:border-zinc-700 font-semibold hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                        Devre Dışı Bırak
                                    </button>
                                <?php else: ?>
                                    <input type="hidden" name="action" value="activate">
                                    <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 font-semibold hover:opacity-90">
                                        Etkinleştir
                                    </button>
                                <?php endif; ?>
                            </form>

                            <?php if (!$pl['is_active']): ?>
                                <form action="<?= e(url('/admin/plugins/delete')) ?>"
                                      method="POST"
                                      data-confirm="“<?= e($pl['name']) ?>” eklentisini ve dosyalarını sistemden silmek istediğinize emin misiniz?"
                                      data-confirm-title="Eklentiyi Sil">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="plugin_id" value="<?= e($pl['id']) ?>">
                                    <button type="submit" class="p-1.5 rounded-lg border border-zinc-200 dark:border-zinc-700 text-red-600 hover:bg-red-50" title="Sil">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!empty($pl['quarantine'])): ?>
                        <div class="p-3.5 rounded-lg border border-red-200 dark:border-red-900 bg-red-50/70 dark:bg-red-950/40 text-xs text-red-800 dark:text-red-300 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div>
                                <div class="font-bold">Yakalanan Çalışma Zamanı Hatası (<?= e($pl['quarantine']['time'] ?? '') ?>):</div>
                                <div class="font-mono text-[11px] mt-0.5"><?= e($pl['quarantine']['error'] ?? '') ?></div>
                            </div>
                            <form action="<?= e(url('/admin/plugins/clear-quarantine')) ?>" method="POST" class="shrink-0">
                                <?= csrf_field() ?>
                                <input type="hidden" name="plugin_id" value="<?= e($pl['id']) ?>">
                                <button type="submit" class="px-3 py-1 rounded border border-red-300 dark:border-red-800 font-semibold hover:bg-red-100">
                                    Uyarıyı Temizle
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
