<div class="space-y-6 w-full">

    <div class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-zinc-100 dark:border-zinc-800 pb-5">
            <div>
                <h2 class="text-base font-bold tracking-tight">Sistem & Sürüm Güncellemeleri</h2>
                <p class="text-xs text-zinc-500 mt-0.5">GitHub Releases üzerinden yayınlanan en son Bvlog sürümleri denetlenir.</p>
            </div>
            <form action="<?= e(url('/admin/updates/check')) ?>" method="POST">
                <?= csrf_field() ?>
                <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90 transition-opacity">
                    <i class="fa-solid fa-arrows-rotate"></i>
                    <span>Güncellemeleri Şimdi Denetle</span>
                </button>
            </form>
        </div>

        <!-- Sürüm Kartları -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-950/40 space-y-1">
                <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400">Yüklü Sürüm</span>
                <div class="text-2xl font-black font-mono text-zinc-900 dark:text-zinc-100">
                    v<?= e($updateInfo['current_version'] ?? '1.3.1') ?>
                </div>
                <span class="inline-block text-[11px] text-zinc-500">Mevcut sistem sürümünüz</span>
            </div>

            <div class="p-4 rounded-xl border <?= !empty($updateInfo['has_update']) ? 'border-amber-300 dark:border-amber-800 bg-amber-50/40 dark:bg-amber-950/20' : 'border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-950/40' ?> space-y-1">
                <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400">En Son GitHub Sürümü</span>
                <div class="text-2xl font-black font-mono <?= !empty($updateInfo['has_update']) ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-900 dark:text-zinc-100' ?>">
                    v<?= e($updateInfo['latest_version'] ?? '1.3.1') ?>
                </div>
                <span class="inline-block text-[11px] text-zinc-500">
                    Son kontrol: <?= e(!empty($updateInfo['checked_at']) ? format_date($updateInfo['checked_at'], true) : 'Henüz yapılmadı') ?>
                </span>
            </div>
        </div>

        <!-- Sunucu Ortam Uyumluluğu -->
        <?php if (isset($updateEnv)): ?>
            <div class="p-3.5 rounded-xl border <?= !empty($updateEnv['ready']) ? 'border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-950/40' : 'border-rose-200 dark:border-rose-900/60 bg-rose-50/40 dark:bg-rose-950/20' ?> flex flex-wrap items-center justify-between gap-3 text-xs">
                <div class="flex items-center gap-2">
                    <i class="<?= !empty($updateEnv['ready']) ? 'fa-solid fa-circle-check text-emerald-500' : 'fa-solid fa-triangle-exclamation text-rose-500' ?>"></i>
                    <span class="font-medium text-zinc-700 dark:text-zinc-300">
                        <?= !empty($updateEnv['ready']) ? 'Sunucunuz GitHub OTA (Tek Tıkla) güncelleme için hazır.' : e($updateEnv['error'] ?? 'Sunucu uyumluluk sorunu.') ?>
                    </span>
                </div>
                <div class="flex items-center gap-3 text-[11px] text-zinc-500">
                    <span class="inline-flex items-center gap-1">
                        <i class="<?= !empty($updateEnv['zip_archive']) ? 'fa-solid fa-check text-emerald-500' : 'fa-solid fa-xmark text-rose-500' ?>"></i>
                        ZipArchive
                    </span>
                    <span class="inline-flex items-center gap-1">
                        <i class="<?= !empty($updateEnv['download_support']) ? 'fa-solid fa-check text-emerald-500' : 'fa-solid fa-xmark text-rose-500' ?>"></i>
                        İndirme (cURL)
                    </span>
                    <span class="inline-flex items-center gap-1">
                        <i class="<?= !empty($updateEnv['writable_all']) ? 'fa-solid fa-check text-emerald-500' : 'fa-solid fa-xmark text-rose-500' ?>"></i>
                        Dizin İzinleri
                    </span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Güncelleme Durumu -->
        <?php if (!empty($updateInfo['has_update'])): ?>
            <div class="p-5 rounded-xl border border-amber-300 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/40 space-y-4">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-full bg-amber-500 text-white flex items-center justify-center shrink-0 text-sm">
                        <i class="fa-solid fa-cloud-arrow-down"></i>
                    </div>
                    <div class="space-y-1 flex-1">
                        <h3 class="text-sm font-bold text-amber-900 dark:text-amber-200">
                            Yeni bir Bvlog sürümü mevcut: v<?= e($updateInfo['latest_version']) ?>
                        </h3>
                        <p class="text-xs text-amber-800 dark:text-amber-300">
                            <?= e($updateInfo['release_name'] ?: ('Bvlog v' . $updateInfo['latest_version'])) ?> yayınlandı.
                        </p>
                    </div>
                </div>

                <?php if (!empty($updateInfo['release_notes'])): ?>
                    <div class="p-3.5 rounded-lg bg-white dark:bg-zinc-900 border border-amber-200 dark:border-amber-900/60 text-xs text-zinc-700 dark:text-zinc-300 space-y-1.5">
                        <div class="font-bold text-[11px] uppercase tracking-wider text-zinc-400">Sürüm Notları (Changelog)</div>
                        <div class="whitespace-pre-line font-mono text-[11px] leading-relaxed max-h-48 overflow-y-auto"><?= e($updateInfo['release_notes']) ?></div>
                    </div>
                <?php endif; ?>

                <div class="flex flex-wrap items-center gap-2.5 pt-1">
                    <form action="<?= e(url('/admin/updates/apply')) ?>" method="POST"
                          x-data="{ isSubmitting: false }"
                          @submit="if (!confirm('Bvlog v<?= e($updateInfo['latest_version']) ?> sürümüne güncellenecektir. Paket GitHub Releases üzerinden otomatik indirilip kurulacaktır.\n\nDevam etmek istiyor musunuz?')) { $event.preventDefault(); return false; } isSubmitting = true;">
                        <?= csrf_field() ?>
                        <button type="submit"
                                :disabled="isSubmitting"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700 disabled:opacity-50 transition-colors shadow-sm">
                            <i :class="isSubmitting ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-bolt'"></i>
                            <span x-text="isSubmitting ? 'Güncelleme İndiriliyor & Kuruluyor...' : 'Şimdi Otomatik Güncelle (GitHub)'"></span>
                        </button>
                    </form>

                    <?php if (!empty($updateInfo['download_url'])): ?>
                        <a href="<?= e($updateInfo['download_url']) ?>" target="_blank"
                           class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-xs font-semibold text-zinc-800 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                            <i class="fa-solid fa-download"></i>
                            <span>Manuel ZIP İndir</span>
                        </a>
                    <?php endif; ?>

                    <a href="<?= e($updateInfo['html_url']) ?>" target="_blank"
                       class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg border border-amber-300 dark:border-amber-800 bg-white dark:bg-zinc-900 text-xs font-semibold text-zinc-800 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                        <i class="fa-brands fa-github"></i>
                        <span>GitHub Sürüm Sayfasını Aç</span>
                    </a>
                </div>
            </div>

            <div class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-950/40 space-y-2 text-xs">
                <div class="font-bold text-zinc-800 dark:text-zinc-200 flex items-center gap-1.5">
                    <i class="fa-solid fa-circle-question text-zinc-400"></i>
                    <span>Güncelleme Yöntemleri</span>
                </div>
                <ul class="list-disc list-inside space-y-1 text-zinc-600 dark:text-zinc-400 leading-relaxed">
                    <li><strong>Otomatik Güncelleme (Önerilen):</strong> Yeşil butona bastığınızda paket doğrudan GitHub Releases sunucularından indirilir, dosyalar yenilenir ve veritabanı şemanız (Migrator) veri kaybı olmadan otomatik yükseltilir.</li>
                    <li><strong>Manuel Güncelleme (Alternatif):</strong> Dilerseniz ZIP paketini indirip FTP veya cPanel Dosya Yöneticisi ile kök dizine yükleyebilirsiniz. Veritabanınız siteye ilk girişte yine otomatik güncellenir.</li>
                </ul>
            </div>
        <?php else: ?>
            <div class="p-5 rounded-xl border border-emerald-200 dark:border-emerald-800/60 bg-emerald-50/50 dark:bg-emerald-950/20 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="w-8 h-8 rounded-full bg-emerald-600 text-white flex items-center justify-center shrink-0 text-sm">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <div class="space-y-0.5 text-xs">
                        <div class="font-bold text-emerald-900 dark:text-emerald-200 text-sm">Sisteminiz Güncel!</div>
                        <div class="text-emerald-700 dark:text-emerald-400">
                            Bvlog'un en son kararlı sürümünü (v<?= e($updateInfo['current_version'] ?? '1.3.1') ?>) kullanıyorsunuz. Herhangi bir işlem yapmanıza gerek yoktur.
                        </div>
                    </div>
                </div>

                <form action="<?= e(url('/admin/updates/apply')) ?>" method="POST"
                      x-data="{ isSubmitting: false }"
                      @submit="if (!confirm('Mevcut v<?= e($updateInfo['current_version'] ?? '1.3.1') ?> sürümü GitHub Releases üzerinden yeniden indirilip kurulacaktır.\n\nBu işlem güncelleme motorunu test etmek veya sistem dosyalarını onarmak için kullanılır. Verileriniz korunur. Devam etmek istiyor musunuz?')) { $event.preventDefault(); return false; } isSubmitting = true;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="force" value="1">
                    <button type="submit"
                            :disabled="isSubmitting"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 text-[11px] font-semibold hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors">
                        <i :class="isSubmitting ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-arrows-rotate'"></i>
                        <span x-text="isSubmitting ? 'Yeniden Kuruluyor...' : 'Sürümü Yeniden Kur (Test Et)'"></span>
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <!-- GitHub Bilgisi -->
        <div class="pt-4 border-t border-zinc-100 dark:border-zinc-800 flex items-center justify-between text-xs text-zinc-500">
            <div class="flex items-center gap-2">
                <i class="fa-brands fa-github text-sm"></i>
                <span>Depo: <a href="https://github.com/fatihsinankara/bvlog" target="_blank" class="font-semibold underline hover:text-zinc-900 dark:hover:text-zinc-100">fatihsinankara/bvlog</a></span>
            </div>
            <span>Otomatik Önbellek: 12 Saat</span>
        </div>
    </div>

</div>
