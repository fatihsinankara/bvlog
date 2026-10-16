<div class="space-y-6">
    <!-- Üst Bilgilendirme & Zip Yükleme -->
    <div class="p-5 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="space-y-1">
            <h2 class="text-sm font-bold"><i class="fa-solid fa-palette mr-1.5"></i>Hata Korumalı Tema Sistemi</h2>
            <p class="text-xs text-zinc-500">
                Yüklediğiniz bir temada eksik şablon veya çalışma hatası oluşursa sistem çökmez; otomatik olarak çekirdek varsayılan temaya geri döner.
            </p>
        </div>

        <form action="<?= e(url('/admin/themes/upload')) ?>" method="POST" enctype="multipart/form-data" class="flex items-center gap-2 shrink-0">
            <?= csrf_field() ?>
            <input type="file" name="theme_zip" accept=".zip" required
                   class="text-xs file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-zinc-100 file:text-zinc-900 dark:file:bg-zinc-800 dark:file:text-zinc-100 cursor-pointer">
            <button type="submit" class="px-4 py-1.5 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90">
                <i class="fa-solid fa-upload mr-1"></i>Tema (.zip) Yükle
            </button>
        </form>
    </div>

    <!-- Tema Kartları -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($themes as $t): ?>
            <div class="rounded-xl border <?= $t['is_active'] ? 'border-zinc-900 dark:border-zinc-100 ring-1 ring-zinc-900 dark:ring-zinc-100' : 'border-zinc-200 dark:border-zinc-800' ?> bg-white dark:bg-zinc-900 p-6 flex flex-col justify-between space-y-5">
                <div class="space-y-2">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="text-base font-bold"><?= e($t['name']) ?></h3>
                        <?php if ($t['is_active']): ?>
                            <span class="px-2.5 py-0.5 text-[11px] font-bold rounded bg-emerald-50 text-emerald-700 border border-emerald-200">Aktif Tema</span>
                        <?php endif; ?>
                    </div>
                    <div class="text-xs text-zinc-400">
                        Sürüm <?= e($t['version']) ?> · Yazar: <?= e($t['author']) ?>
                    </div>
                    <p class="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed pt-1">
                        <?= e($t['description']) ?>
                    </p>
                </div>

                <div class="pt-4 border-t border-zinc-100 dark:border-zinc-800 flex items-center justify-between gap-2">
                    <?php if (!$t['is_active']): ?>
                        <form action="<?= e(url('/admin/themes/activate')) ?>" method="POST">
                            <?= csrf_field() ?>
                            <input type="hidden" name="theme_id" value="<?= e($t['id']) ?>">
                            <button type="submit" class="px-4 py-1.5 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90">
                                Etkinleştir
                            </button>
                        </form>
                    <?php else: ?>
                        <span class="text-xs font-semibold text-zinc-500"><i class="fa-solid fa-check mr-1"></i>Kullanımda</span>
                    <?php endif; ?>

                    <?php if (empty($t['is_default']) && !$t['is_active']): ?>
                        <form action="<?= e(url('/admin/themes/delete')) ?>"
                              method="POST"
                              data-confirm="“<?= e($t['name']) ?>” temasını ve şablon dosyalarını silmek istediğinize emin misiniz?"
                              data-confirm-title="Temayı Sil">
                            <?= csrf_field() ?>
                            <input type="hidden" name="theme_id" value="<?= e($t['id']) ?>">
                            <button type="submit" class="px-2.5 py-1.5 rounded-lg border border-red-200 text-red-600 text-xs font-semibold hover:bg-red-50">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
