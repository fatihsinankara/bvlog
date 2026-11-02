<?php
$enabled       = !empty($diagnostics['enabled']);
$ttl           = (int) ($diagnostics['ttl'] ?? 3600);
$ttlLabel      = (string) ($diagnostics['ttl_label'] ?? '1 Saat');
$feedsEnabled  = !empty($diagnostics['feeds_enabled']);
$autoFlush     = !empty($diagnostics['auto_flush']);
$lastClearedAt = $diagnostics['last_cleared_at'] ?? null;
$writable      = !empty($diagnostics['writable']);
$totalFiles    = (int) ($diagnostics['total_files'] ?? 0);
$activeFiles   = (int) ($diagnostics['active_files'] ?? 0);
$expiredFiles  = (int) ($diagnostics['expired_files'] ?? 0);
$totalBytes    = (int) ($diagnostics['total_bytes'] ?? 0);
$groups        = $diagnostics['groups'] ?? [];
$items         = $diagnostics['items'] ?? [];
$systemFiles   = $diagnostics['system_files'] ?? [];
$opcache       = $diagnostics['opcache'] ?? [];
?>

<div class="space-y-6"
     x-data="{
         selectedGroup: 'all',
         searchQuery: '',
         showSettingsModal: false
     }">

    <!-- Üst Durum Banner & Hızlı İşlem Çubuğu -->
    <div class="p-5 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div class="flex items-start sm:items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 <?= $enabled ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-500 border border-zinc-200 dark:border-zinc-700' ?>">
                <i class="fa-solid fa-bolt text-lg"></i>
            </div>
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-sm sm:text-base font-bold tracking-tight">Dahili Önbellek Motoru (Core\Cache)</h2>
                    <?php if ($enabled): ?>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            Aktif
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-zinc-200 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400">
                            Pasif
                        </span>
                    <?php endif; ?>

                    <?php if (!$writable): ?>
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-red-500/10 text-red-600 border border-red-500/20">
                            <i class="fa-solid fa-triangle-exclamation"></i> Yazma İzni Yok
                        </span>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                    Veritabanı sorgularını, işlenmiş makale içeriklerini, kategori sayaçlarını ve SEO/RSS beslemelerini dosya tabanlı L1/L2 mimarisiyle hızlandırır.
                    <?php if ($lastClearedAt): ?>
                        <span class="inline-block ml-1 text-zinc-400 dark:text-zinc-500">• Son temizlik: <strong class="text-zinc-600 dark:text-zinc-300"><?= e(format_date($lastClearedAt, true)) ?></strong></span>
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <!-- Hızlı Aksiyon Butonları -->
        <div class="flex flex-wrap items-center gap-2 shrink-0">
            <form action="<?= e(url('/admin/cache/warmup')) ?>" method="POST" class="inline">
                <?= csrf_field() ?>
                <button type="submit"
                        <?= !$enabled ? 'disabled title="Önce önbellek sistemini etkinleştirin"' : '' ?>
                        class="px-3.5 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/70 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-xs font-semibold inline-flex items-center gap-1.5 transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                    <i class="fa-solid fa-fire-flame-curved text-amber-500"></i>
                    <span>Önbelleği Isıt (Preload)</span>
                </button>
            </form>

            <?php if ($expiredFiles > 0): ?>
                <form action="<?= e(url('/admin/cache/clear')) ?>" method="POST" class="inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="mode" value="expired">
                    <button type="submit"
                            class="px-3.5 py-2 rounded-lg border border-amber-300 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/50 text-amber-800 dark:text-amber-300 hover:bg-amber-100 text-xs font-semibold inline-flex items-center gap-1.5 transition-colors">
                        <i class="fa-solid fa-broom"></i>
                        <span>Süresi Dolmuşları Sil (<?= $expiredFiles ?>)</span>
                    </button>
                </form>
            <?php endif; ?>

            <form action="<?= e(url('/admin/cache/clear')) ?>" method="POST" class="inline">
                <?= csrf_field() ?>
                <input type="hidden" name="mode" value="all">
                <button type="submit"
                        class="px-4 py-2 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 hover:opacity-90 text-xs font-semibold inline-flex items-center gap-1.5 transition-opacity">
                    <i class="fa-solid fa-trash-can"></i>
                    <span>Tüm Önbelleği Temizle</span>
                </button>
            </form>
        </div>
    </div>

    <!-- 4'lü Özet Metrik Kartları -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Toplam Boyut -->
        <div class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
            <div class="flex items-center justify-between text-xs text-zinc-500 mb-1.5">
                <span>Toplam Önbellek Boyutu</span>
                <i class="fa-solid fa-hard-drive text-zinc-400"></i>
            </div>
            <div class="text-xl font-extrabold tracking-tight"><?= e(format_bytes($totalBytes)) ?></div>
            <div class="mt-1 text-[11px] text-zinc-400 truncate" title="storage/cache/">
                Dizin: <code>storage/cache/</code>
            </div>
        </div>

        <!-- Kayıt Sayısı -->
        <div class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
            <div class="flex items-center justify-between text-xs text-zinc-500 mb-1.5">
                <span>Önbellek Kayıt Sayısı</span>
                <i class="fa-solid fa-layer-group text-zinc-400"></i>
            </div>
            <div class="text-xl font-extrabold tracking-tight">
                <?= $activeFiles ?> <span class="text-xs font-normal text-zinc-500">aktif kayıt</span>
            </div>
            <div class="mt-1 text-[11px] text-zinc-400">
                Toplam: <?= $totalFiles ?> dosya <?= $expiredFiles > 0 ? "({$expiredFiles} süresi dolmuş)" : '(Tümü güncel)' ?>
            </div>
        </div>

        <!-- Yaşam Süresi & Otomatik Temizleme -->
        <div class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
            <div class="flex items-center justify-between text-xs text-zinc-500 mb-1.5">
                <span>Varsayılan Süre (TTL)</span>
                <i class="fa-regular fa-clock text-zinc-400"></i>
            </div>
            <div class="text-xl font-extrabold tracking-tight"><?= e($ttlLabel) ?></div>
            <div class="mt-1 text-[11px] flex items-center gap-1.5">
                <?php if ($autoFlush): ?>
                    <span class="text-emerald-600 dark:text-emerald-400 font-medium"><i class="fa-solid fa-check mr-0.5"></i>Akıllı Otomatik Temizleme Açık</span>
                <?php else: ?>
                    <span class="text-amber-600 dark:text-amber-400 font-medium">Otomatik Temizleme Kapalı</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- PHP OPcache Durumu -->
        <div class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
            <div class="flex items-center justify-between text-xs text-zinc-500 mb-1.5">
                <span>PHP OPcache (Bytecode)</span>
                <i class="fa-solid fa-microchip text-zinc-400"></i>
            </div>
            <?php if (!empty($opcache['enabled'])): ?>
                <div class="flex items-baseline justify-between">
                    <div class="text-xl font-extrabold tracking-tight text-emerald-600 dark:text-emerald-400">
                        %<?= e((string) $opcache['hit_rate']) ?> <span class="text-xs font-normal text-zinc-500">isabet</span>
                    </div>
                    <form action="<?= e(url('/admin/cache/clear')) ?>" method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="mode" value="opcache">
                        <button type="submit" class="text-[11px] font-semibold text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100 underline">
                            Sıfırla
                        </button>
                    </form>
                </div>
                <div class="mt-1 text-[11px] text-zinc-400">
                    <?= (int) $opcache['cached_scripts'] ?> betik • <?= e(format_bytes((int) $opcache['memory_used'])) ?> bellek
                </div>
            <?php else: ?>
                <div class="text-base font-bold text-zinc-500">Pasif / Sunucu Kısıtlı</div>
                <div class="mt-1 text-[11px] text-zinc-400">
                    Uygulama dosya önbelleği bağımsız çalışır
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Orta Bölüm: Sol (Grup Bazlı Dağılım & Kontroller) / Sağ (Yapılandırma Ayarları) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

        <!-- Sol 2 Sütun: Grup Bazlı Önbellek Dağılımı -->
        <div class="lg:col-span-2 p-5 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold tracking-tight">Önbellek Grupları & Modül Dağılımı</h3>
                    <p class="text-xs text-zinc-500 mt-0.5">Her içerik modülü kendi grubunda izole tutulur; değişiklik olduğunda yalnızca ilgili grup yenilenir.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <?php foreach ($groups as $gKey => $grp): ?>
                    <div class="p-3.5 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/60 dark:bg-zinc-950/50 flex flex-col justify-between gap-3">
                        <div class="flex items-start justify-between gap-2.5">
                            <div class="flex items-start gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 flex items-center justify-center text-zinc-700 dark:text-zinc-300 shrink-0 mt-0.5">
                                    <i class="<?= e($grp['icon']) ?> text-xs"></i>
                                </div>
                                <div>
                                    <button type="button"
                                            @click="selectedGroup = '<?= e($gKey) ?>'; document.getElementById('cache-records-section')?.scrollIntoView({ behavior: 'smooth' })"
                                            class="text-xs font-bold text-left hover:underline">
                                        <?= e($grp['title']) ?>
                                    </button>
                                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400 leading-snug mt-0.5">
                                        <?= e($grp['description']) ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="pt-2 border-t border-zinc-200/70 dark:border-zinc-800/80 flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-zinc-800 dark:text-zinc-200"><?= (int) $grp['active_count'] ?> kayıt</span>
                                <span class="text-zinc-400">•</span>
                                <span class="text-zinc-500"><?= e(format_bytes((int) $grp['bytes'])) ?></span>
                            </div>

                            <form action="<?= e(url('/admin/cache/clear-group')) ?>" method="POST">
                                <?= csrf_field() ?>
                                <input type="hidden" name="group" value="<?= e($gKey) ?>">
                                <button type="submit"
                                        <?= (int) $grp['count'] === 0 ? 'disabled' : '' ?>
                                        class="px-2.5 py-1 rounded-md border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 hover:bg-red-50 hover:text-red-600 hover:border-red-200 dark:hover:bg-red-950/40 text-[11px] font-semibold transition-colors disabled:opacity-40 disabled:pointer-events-none">
                                    <i class="fa-solid fa-rotate-right mr-1"></i>Temizle
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Sağ 1 Sütun: Önbellek Yapılandırma Ayarları -->
        <form action="<?= e(url('/admin/cache/settings')) ?>" method="POST"
              class="p-5 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-4">
            <?= csrf_field() ?>

            <div>
                <h3 class="text-sm font-bold tracking-tight">Önbellek Ayarları</h3>
                <p class="text-xs text-zinc-500 mt-0.5">Sistem performans ve önbellek davranışını yapılandırın.</p>
            </div>

            <div class="space-y-3 pt-1">
                <!-- Önbellek Aktif/Pasif -->
                <label class="p-3 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-950/40 flex items-center justify-between gap-3 cursor-pointer">
                    <div>
                        <span class="block text-xs font-bold">Dahili Önbellek Sistemi</span>
                        <span class="block text-[11px] text-zinc-500">Sorgu, sayfa verisi ve ayar önbelleklemesini etkinleştirir.</span>
                    </div>
                    <input type="checkbox" name="cache_enabled" value="1" role="switch" class="ios-switch shrink-0" <?= $enabled ? 'checked' : '' ?>>
                </label>

                <!-- Varsayılan TTL Süresi -->
                <div>
                    <label class="block text-xs font-semibold mb-1.5">Varsayılan Önbellek Süresi (TTL)</label>
                    <select name="cache_ttl"
                            class="w-full px-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
                        <?php
                        $ttlOptions = [
                            300    => '5 Dakika (Çok Sık Güncellenen Site)',
                            900    => '15 Dakika',
                            1800   => '30 Dakika',
                            3600   => '1 Saat (Önerilen)',
                            10800  => '3 Saat',
                            21600  => '6 Saat',
                            43200  => '12 Saat',
                            86400  => '24 Saat (1 Gün)',
                            604800 => '7 Gün (1 Hafta)',
                        ];
                        foreach ($ttlOptions as $sec => $label):
                        ?>
                            <option value="<?= $sec ?>" <?= $ttl === $sec ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Sitemap & RSS Önbelleği -->
                <label class="p-3 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-950/40 flex items-center justify-between gap-3 cursor-pointer">
                    <div>
                        <span class="block text-xs font-bold">Sitemap & RSS Önbelleği</span>
                        <span class="block text-[11px] text-zinc-500"><code>/sitemap.xml</code> ve <code>/rss.xml</code> çıktılarını önbellekten sunar.</span>
                    </div>
                    <input type="checkbox" name="cache_feeds" value="1" role="switch" class="ios-switch shrink-0" <?= $feedsEnabled ? 'checked' : '' ?>>
                </label>

                <!-- Akıllı Otomatik Temizleme -->
                <label class="p-3 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-950/40 flex items-center justify-between gap-3 cursor-pointer">
                    <div>
                        <span class="block text-xs font-bold">Akıllı Otomatik Temizleme</span>
                        <span class="block text-[11px] text-zinc-500">Yazı, kategori, yorum veya ayar değiştiğinde ilgili önbelleği anında yeniler.</span>
                    </div>
                    <input type="checkbox" name="cache_auto_flush" value="1" role="switch" class="ios-switch shrink-0" <?= $autoFlush ? 'checked' : '' ?>>
                </label>
            </div>

            <button type="submit"
                    class="w-full py-2.5 px-4 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90 transition-opacity">
                Ayarları Kaydet
            </button>
        </form>
    </div>

    <!-- Alt Bölüm: Canlı Önbellek Kayıtları ve Detaylı İnceleme Tablosu -->
    <div id="cache-records-section" class="p-5 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h3 id="cache-items-table-caption" class="text-sm font-bold tracking-tight">Canlı Önbellek Kayıtları & Detaylar</h3>
                <p class="text-xs text-zinc-500 mt-0.5">Şu anda diskte tutulan önbellek anahtarlarını, boyutlarını ve kalan yaşam sürelerini inceleyebilirsiniz.</p>
            </div>

            <!-- Arama Kutusu -->
            <div class="relative w-full sm:w-64">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 text-xs"></i>
                <input type="search"
                       x-model="searchQuery"
                       placeholder="Anahtar veya açıklama ara..."
                       class="w-full pl-8 pr-3 py-1.5 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
            </div>
        </div>

        <!-- Grup Filtre Sekmeleri -->
        <div class="flex flex-wrap items-center gap-1.5 pb-1 text-xs">
            <button type="button"
                    @click="selectedGroup = 'all'"
                    :class="selectedGroup === 'all' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 font-semibold' : 'border border-zinc-200 dark:border-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800'"
                    class="px-3 py-1.5 rounded-lg transition-colors">
                Tümü (<?= $totalFiles ?>)
            </button>
            <?php foreach ($groups as $gKey => $grp): ?>
                <button type="button"
                        @click="selectedGroup = '<?= e($gKey) ?>'"
                        :class="selectedGroup === '<?= e($gKey) ?>' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 font-semibold' : 'border border-zinc-200 dark:border-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800'"
                        class="px-3 py-1.5 rounded-lg transition-colors inline-flex items-center gap-1.5">
                    <i class="<?= e($grp['icon']) ?> text-[11px]"></i>
                    <span><?= e($grp['title']) ?> (<?= (int) $grp['count'] ?>)</span>
                </button>
            <?php endforeach; ?>
        </div>

        <?php if (empty($items)): ?>
            <div class="py-12 px-4 text-center rounded-xl border border-dashed border-zinc-200 dark:border-zinc-800 bg-zinc-50/40 dark:bg-zinc-950/30">
                <i class="fa-solid fa-box-open text-2xl text-zinc-400 mb-2"></i>
                <p class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">Şu anda oluşturulmuş bir önbellek kaydı bulunmuyor.</p>
                <p class="text-[11px] text-zinc-500 mt-1">Ziyaretçiler sayfaları gezdikçe veya üstteki <strong>"Önbelleği Isıt (Preload)"</strong> butonuna tıkladığınızda kayıtlar burada listelenir.</p>
            </div>
        <?php else: ?>
            <div tabindex="0"
                 role="region"
                 aria-labelledby="cache-items-table-caption"
                 class="overflow-auto max-w-full max-h-[460px] rounded-xl border border-zinc-200 dark:border-zinc-800">
                <table class="w-full text-left border-separate border-spacing-0 text-xs">
                    <thead class="sticky top-0 z-10 bg-zinc-100 dark:bg-zinc-800/95 text-zinc-600 dark:text-zinc-300 text-[11px] uppercase tracking-wider">
                        <tr>
                            <th scope="col" class="py-2.5 px-3.5 border-b border-zinc-200 dark:border-zinc-700 font-bold">Önbellek Kaydı / Anahtar</th>
                            <th scope="col" class="py-2.5 px-3.5 border-b border-zinc-200 dark:border-zinc-700 font-bold">Grup</th>
                            <th scope="col" class="py-2.5 px-3.5 border-b border-zinc-200 dark:border-zinc-700 font-bold">Boyut</th>
                            <th scope="col" class="py-2.5 px-3.5 border-b border-zinc-200 dark:border-zinc-700 font-bold">Oluşturulma</th>
                            <th scope="col" class="py-2.5 px-3.5 border-b border-zinc-200 dark:border-zinc-700 font-bold">Durum / Kalan Süre</th>
                            <th scope="col" class="py-2.5 px-3.5 border-b border-zinc-200 dark:border-zinc-700 font-bold text-right">İşlem</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/80">
                        <?php foreach ($items as $item):
                            $searchHaystack = mb_strtolower($item['label'] . ' ' . $item['key'] . ' ' . $item['group_title'], 'UTF-8');
                        ?>
                            <tr x-show="(selectedGroup === 'all' || selectedGroup === <?= e(json_encode($item['group'])) ?>) && (searchQuery === '' || <?= e(json_encode($searchHaystack)) ?>.includes(searchQuery.toLowerCase()))"
                                class="hover:bg-zinc-50/80 dark:hover:bg-zinc-800/40 transition-colors">
                                <th scope="row" class="py-2.5 px-3.5 font-medium text-zinc-900 dark:text-zinc-100">
                                    <div class="font-semibold"><?= e($item['label']) ?></div>
                                    <div class="text-[11px] font-mono text-zinc-400 truncate max-w-xs sm:max-w-md" title="<?= e($item['key']) ?>">
                                        <?= e($item['key']) ?>
                                    </div>
                                </th>
                                <td class="py-2.5 px-3.5 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 text-[11px] font-medium">
                                        <i class="<?= e($item['group_icon']) ?> text-zinc-400"></i>
                                        <span><?= e($item['group_title']) ?></span>
                                    </span>
                                </td>
                                <td class="py-2.5 px-3.5 whitespace-nowrap font-mono text-[11px] text-zinc-600 dark:text-zinc-300">
                                    <?= e(format_bytes((int) $item['bytes'])) ?>
                                </td>
                                <td class="py-2.5 px-3.5 whitespace-nowrap text-zinc-500">
                                    <?= e(format_date($item['created_at'], true)) ?>
                                </td>
                                <td class="py-2.5 px-3.5 whitespace-nowrap">
                                    <?php if ($item['is_expired']): ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-500/10 text-amber-600 dark:text-amber-400">
                                            <i class="fa-regular fa-clock"></i> Süresi Doldu
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-500/10 text-emerald-700 dark:text-emerald-300"
                                              title="Bitiş: <?= e($item['expires_at']) ?>">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            <?= e($item['remaining_label']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-2.5 px-3.5 whitespace-nowrap text-right">
                                    <form action="<?= e(url('/admin/cache/delete-item')) ?>" method="POST" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="filename" value="<?= e($item['filename']) ?>">
                                        <button type="submit"
                                                class="px-2 py-1 rounded border border-zinc-200 dark:border-zinc-700 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/40 text-[11px] font-medium transition-colors"
                                                title="Bu önbellek kaydını sil">
                                            <i class="fa-regular fa-trash-can"></i> Sil
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Yardımcı Sistem Önbellek Dosyaları Bilgisi -->
        <?php if (!empty($systemFiles)): ?>
            <div class="pt-3 border-t border-zinc-100 dark:border-zinc-800 flex flex-wrap items-center justify-between gap-2 text-[11px] text-zinc-500">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="font-semibold text-zinc-400 uppercase tracking-wider">Sistem Durum Dosyaları:</span>
                    <?php foreach ($systemFiles as $sf): ?>
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800/80 text-zinc-600 dark:text-zinc-300"
                              title="<?= e($sf['label']) ?> (Son güncelleme: <?= e($sf['updated_at']) ?>)">
                            <i class="fa-regular fa-file-code text-zinc-400"></i>
                            <code><?= e($sf['name']) ?></code>
                            <span class="text-zinc-400">(<?= e(format_bytes((int) $sf['bytes'])) ?>)</span>
                        </span>
                    <?php endforeach; ?>
                </div>
                <span>Tarayıcı statik dosya önbelleği <code>.htaccess</code> (<code>mod_expires</code> & <code>mod_deflate</code>) üzerinden aktif.</span>
            </div>
        <?php endif; ?>
    </div>

</div>
