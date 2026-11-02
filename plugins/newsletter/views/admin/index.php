<?php
$section = $section ?? 'compose';
$stats = $stats ?? ['total' => 0, 'active' => 0, 'pending' => 0, 'unsubscribed' => 0];
$smtpStatus = $smtpStatus ?? ['smtp_ready' => false, 'from_name' => 'Bvlog', 'from_email' => ''];
$navItems = [
    'compose'     => ['url' => url('/admin/newsletter'),             'icon' => 'fa-regular fa-paper-plane', 'label' => 'Bülten Gönder'],
    'subscribers' => ['url' => url('/admin/newsletter/subscribers'), 'icon' => 'fa-solid fa-users',         'label' => 'Aboneler & İzin Kayıtları', 'badge' => (int) ($stats['active'] ?? 0)],
    'campaigns'   => ['url' => url('/admin/newsletter/campaigns'),   'icon' => 'fa-solid fa-clock-rotate-left', 'label' => 'Gönderim Geçmişi'],
    'settings'    => ['url' => url('/admin/newsletter/settings'),    'icon' => 'fa-solid fa-scale-balanced', 'label' => 'Ayarlar & KVKK Metinleri'],
];
?>

<div class="space-y-6 w-full"
     x-data="{
         subject: '',
         contentHtml: '',
         sendMode: 'all_active',
         testEmail: <?= e(json_encode($smtpStatus['from_email'] ?: setting('social_email', ''))) ?>,
         previewMode: false,
         postPickerOpen: true,
         postSearch: '',
         activePeriodLabel: '',
         selectedPostIds: [],
         siteTitle: <?= e(json_encode(setting('site_title', 'Bvlog'))) ?>,
         recentPosts: <?= e(json_encode($recentPosts ?? [])) ?>,
         filteredPosts() {
             const q = this.postSearch.trim().toLowerCase();
             if (!q) return this.recentPosts;
             return this.recentPosts.filter(p =>
                 (p.title && p.title.toLowerCase().includes(q)) ||
                 (p.category && p.category.toLowerCase().includes(q)) ||
                 (p.date_label && p.date_label.toLowerCase().includes(q))
             );
         },
         isPostSelected(id) {
             return this.selectedPostIds.includes(id);
         },
         togglePost(id) {
             this.activePeriodLabel = '';
             if (this.selectedPostIds.includes(id)) {
                 this.selectedPostIds = this.selectedPostIds.filter(item => item !== id);
             } else {
                 this.selectedPostIds.push(id);
             }
         },
         selectByDays(days, label) {
             const nowSec = Math.floor(Date.now() / 1000);
             const cutoff = nowSec - (days * 86400);
             this.selectedPostIds = this.recentPosts
                 .filter(p => (p.published_ts || 0) >= cutoff)
                 .map(p => p.id);
             this.activePeriodLabel = label;
             this.postPickerOpen = true;
         },
         selectLatestCount(count, label) {
             this.selectedPostIds = this.recentPosts.slice(0, count).map(p => p.id);
             this.activePeriodLabel = label;
             this.postPickerOpen = true;
         },
         selectFilteredAll() {
             const ids = this.filteredPosts().map(p => p.id);
             this.selectedPostIds = Array.from(new Set([...this.selectedPostIds, ...ids]));
             this.activePeriodLabel = 'Seçili Yazılar';
         },
         clearSelectedPosts() {
             this.selectedPostIds = [];
             this.activePeriodLabel = '';
         },
         buildTemplateFromSelected(appendMode = false) {
             const chosen = this.recentPosts.filter(p => this.selectedPostIds.includes(p.id));
             if (!chosen.length) return;

             if (chosen.length === 1 && !appendMode) {
                 const p = chosen[0];
                 this.subject = 'Yeni Yazı: ' + p.title;
                 const meta = [p.category, p.date_label].filter(Boolean).join(' • ');
                 this.contentHtml = `<div style=&quot;margin-bottom: 8px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #71717a;&quot;>${meta}</div>\n<h2 style=&quot;margin: 0 0 12px; font-size: 20px; color: #18181b;&quot;>${p.title}</h2>\n<p style=&quot;margin: 0 0 18px; color: #3f3f46; line-height: 1.7;&quot;>${p.excerpt}</p>\n<div style=&quot;margin: 24px 0;&quot;>\n    <a href=&quot;${p.url}&quot; style=&quot;display: inline-block; padding: 11px 22px; background-color: #18181b; color: #ffffff; text-decoration: none; font-size: 13px; font-weight: 700; border-radius: 8px;&quot;>Yazının Devamını Oku &rarr;</a>\n</div>`;
                 return;
             }

             const periodTitle = this.activePeriodLabel || 'Öne Çıkan Yazılar';
             if (!appendMode || !this.subject.trim()) {
                 this.subject = `${periodTitle} (${chosen.length} Yazı) — ${this.siteTitle}`;
             }

             const cardsHtml = chosen.map((p, idx) => {
                 const meta = [p.category, p.date_label].filter(Boolean).join(' • ');
                 return `<div style=&quot;padding: 18px 20px; margin-bottom: 14px; border: 1px solid #e4e4e7; border-radius: 12px; background-color: #fafafa;&quot;>\n    <div style=&quot;font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #71717a; margin-bottom: 6px;&quot;>#${idx + 1} • ${meta}</div>\n    <h3 style=&quot;margin: 0 0 8px; font-size: 17px; line-height: 1.4;&quot;>\n        <a href=&quot;${p.url}&quot; style=&quot;color: #18181b; text-decoration: none;&quot;>${p.title}</a>\n    </h3>\n    <p style=&quot;margin: 0 0 14px; font-size: 14px; color: #52525b; line-height: 1.65;&quot;>${p.excerpt}</p>\n    <a href=&quot;${p.url}&quot; style=&quot;display: inline-block; padding: 8px 16px; background-color: #18181b; color: #ffffff; text-decoration: none; font-size: 12px; font-weight: 700; border-radius: 7px;&quot;>Yazıyı Oku &rarr;</a>\n</div>`;
             }).join('\n\n');

             const introHtml = `<p style=&quot;margin: 0 0 18px; font-size: 15px; color: #3f3f46; line-height: 1.7;&quot;>Merhaba, <strong>${this.siteTitle}</strong> üzerinde yayınlanan <strong>${chosen.length}</strong> yazıyı sizin için derledik:</p>\n\n`;

             if (appendMode && this.contentHtml.trim() !== '') {
                 this.contentHtml = this.contentHtml.trim() + '\n\n' + cardsHtml;
             } else {
                 this.contentHtml = introHtml + cardsHtml;
             }
         }
     }">

    <!-- Üst Özet Kartları -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
            <div class="flex items-center justify-between text-xs text-zinc-500 mb-1">
                <span>Aktif Aboneler</span>
                <i class="fa-solid fa-user-check text-emerald-500" aria-hidden="true"></i>
            </div>
            <div class="text-2xl font-black font-mono text-emerald-600 dark:text-emerald-400"><?= (int) ($stats['active'] ?? 0) ?></div>
            <span class="text-[11px] text-zinc-400">Açık rıza onaylı alıcılar</span>
        </div>

        <div class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
            <div class="flex items-center justify-between text-xs text-zinc-500 mb-1">
                <span>Doğrulama Bekleyen</span>
                <i class="fa-regular fa-clock text-amber-500" aria-hidden="true"></i>
            </div>
            <div class="text-2xl font-black font-mono text-amber-600 dark:text-amber-400"><?= (int) ($stats['pending'] ?? 0) ?></div>
            <span class="text-[11px] text-zinc-400">Double Opt-In bekleyen</span>
        </div>

        <div class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
            <div class="flex items-center justify-between text-xs text-zinc-500 mb-1">
                <span>İzni İptal Edenler</span>
                <i class="fa-solid fa-user-xmark text-zinc-400" aria-hidden="true"></i>
            </div>
            <div class="text-2xl font-black font-mono"><?= (int) ($stats['unsubscribed'] ?? 0) ?></div>
            <span class="text-[11px] text-zinc-400">Abonelikten ayrılanlar</span>
        </div>

        <div class="p-4 rounded-xl border <?= !empty($smtpStatus['smtp_ready']) ? 'border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900' : 'border-amber-300 dark:border-amber-800/80 bg-amber-50/40 dark:bg-amber-950/20' ?>">
            <div class="flex items-center justify-between text-xs text-zinc-500 mb-1">
                <span>SMTP Gönderim Kanalı</span>
                <i class="fa-solid <?= !empty($smtpStatus['smtp_ready']) ? 'fa-server text-emerald-500' : 'fa-triangle-exclamation text-amber-500' ?>" aria-hidden="true"></i>
            </div>
            <div class="text-sm font-bold truncate <?= empty($smtpStatus['smtp_ready']) ? 'text-amber-700 dark:text-amber-400' : '' ?>">
                <?php if (!empty($smtpStatus['smtp_ready'])): ?>
                    <?= e($smtpStatus['host'] . ':' . $smtpStatus['port']) ?>
                <?php elseif (empty($smtpStatus['plugin_active'])): ?>
                    SMTP Eklentisi Devre Dışı
                <?php else: ?>
                    SMTP Ayarları Eksik / Kapalı
                <?php endif; ?>
            </div>
            <?php if (empty($smtpStatus['plugin_active'])): ?>
                <a href="<?= e(url('/admin/plugins')) ?>" class="text-[11px] font-semibold text-amber-700 dark:text-amber-400 hover:underline">
                    Eklentiyi Etkinleştir &rarr;
                </a>
            <?php else: ?>
                <a href="<?= e(url('/admin/smtp')) ?>" class="text-[11px] text-blue-600 dark:text-blue-400 hover:underline">
                    SMTP Ayarlarını Gör &rarr;
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- SMTP Devre Dışı / Eksik Uyarı Kutusu -->
    <?php if (empty($smtpStatus['smtp_ready'])): ?>
        <div role="alert" class="p-4 rounded-xl border border-amber-300 dark:border-amber-800 bg-amber-50/80 dark:bg-amber-950/30 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-start gap-3">
                <i class="fa-solid fa-triangle-exclamation text-amber-600 dark:text-amber-400 text-base mt-0.5 shrink-0" aria-hidden="true"></i>
                <div class="text-xs space-y-0.5">
                    <div class="font-bold text-amber-950 dark:text-amber-200">
                        <?= empty($smtpStatus['plugin_active']) ? 'SMTP Eklentisi Devre Dışı!' : 'SMTP Gönderimi Hazır Değil!' ?>
                    </div>
                    <p class="text-amber-800 dark:text-amber-300/90 leading-relaxed">
                        <?= e($smtpStatus['reason'] ?? 'SMTP eklentisi etkin değil.') ?>
                        Bültenlerin ve abonelik doğrulama e-postalarının spama düşmeden iletilebilmesi için SMTP eklentisinin etkin ve yapılandırılmış olması önerilir (aksi halde yerel <code>PHP mail()</code> denenir).
                    </p>
                </div>
            </div>
            <?php if (empty($smtpStatus['plugin_active'])): ?>
                <a href="<?= e(url('/admin/plugins')) ?>"
                   class="px-3.5 py-2 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold shrink-0 inline-flex items-center gap-1.5 self-start sm:self-center transition-colors">
                    <i class="fa-solid fa-plug" aria-hidden="true"></i>
                    <span>Eklentileri Yönet</span>
                </a>
            <?php else: ?>
                <a href="<?= e(url('/admin/smtp')) ?>"
                   class="px-3.5 py-2 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold shrink-0 inline-flex items-center gap-1.5 self-start sm:self-center transition-colors">
                    <i class="fa-solid fa-sliders" aria-hidden="true"></i>
                    <span>SMTP Ayarlarını Aç</span>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Alt Sayfa Gezinti Çubuğu -->
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 dark:border-zinc-800 pb-3">
        <div class="flex flex-wrap items-center gap-1.5 text-xs font-semibold">
            <?php foreach ($navItems as $key => $item):
                $isActive = ($section === $key);
            ?>
                <a href="<?= e($item['url']) ?>"
                   class="px-3.5 py-2 rounded-lg transition-colors inline-flex items-center gap-2 <?= $isActive ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800' ?>">
                    <i class="<?= e($item['icon']) ?>" aria-hidden="true"></i>
                    <span><?= e($item['label']) ?></span>
                    <?php if (isset($item['badge'])): ?>
                        <span class="px-1.5 py-0.5 rounded text-[10px] <?= $isActive ? 'bg-white/20 text-white dark:bg-zinc-900/20 dark:text-zinc-900' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300' ?>">
                            <?= (int) $item['badge'] ?>
                        </span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <a href="<?= e(url('/admin/newsletter/subscribers/export')) ?>"
           class="px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-xs font-semibold hover:bg-zinc-50 dark:hover:bg-zinc-800 inline-flex items-center gap-1.5">
            <i class="fa-solid fa-file-csv text-emerald-600" aria-hidden="true"></i>
            <span>Aboneleri CSV İndir (KVKK Kayıtlı)</span>
        </a>
    </div>

    <?php if ($section === 'compose'): ?>
        <!-- 1. BÜLTEN GÖNDER (KAMPANYA OLUŞTUR) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-8">
                <form action="<?= e(url('/admin/newsletter/send')) ?>" method="POST"
                      class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-5">
                    <?= csrf_field() ?>

                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-zinc-100 dark:border-zinc-800 pb-4">
                        <div>
                            <h2 class="text-base font-bold tracking-tight">Abonelere E-posta Bülteni Gönder</h2>
                            <p class="text-xs text-zinc-500 mt-0.5">Her e-postanın altına aboneye özel KVKK/ETK izin bilgisi ve tek tıkla abonelikten çıkış bağlantısı otomatik eklenir.</p>
                        </div>
                        <button type="button"
                                @click="previewMode = !previewMode"
                                class="px-3 py-1.5 rounded-lg border border-zinc-200 dark:border-zinc-700 text-xs font-semibold hover:bg-zinc-100 dark:hover:bg-zinc-800 shrink-0 inline-flex items-center gap-1.5">
                            <i class="fa-regular fa-eye" aria-hidden="true"></i>
                            <span x-text="previewMode ? 'Editöre Dön' : 'Canlı Önizleme'">Canlı Önizleme</span>
                        </button>
                    </div>

                    <!-- Çoklu Blog Yazısı Seçici & Dönem Filtreleri -->
                    <?php if (!empty($recentPosts)): ?>
                        <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/70 dark:bg-zinc-950/50 overflow-hidden">
                            <!-- Üst Başlık ve Hızlı Filtre Butonları -->
                            <div class="p-3.5 flex flex-col gap-3">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex items-center gap-2 text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                                        <i class="fa-solid fa-wand-magic-sparkles text-amber-500" aria-hidden="true"></i>
                                        <span>Yazılardan Bülten Özeti Oluştur</span>
                                        <span x-show="selectedPostIds.length > 0"
                                              style="display: none;"
                                              class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400"
                                              x-text="selectedPostIds.length + ' yazı seçildi'"></span>
                                    </div>
                                    <button type="button"
                                            @click="postPickerOpen = !postPickerOpen"
                                            class="text-[11px] font-semibold text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100 inline-flex items-center gap-1">
                                        <span x-text="postPickerOpen ? 'Listeyi Gizle' : 'Yazı Listesini Göster'"></span>
                                        <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200" :class="{ 'rotate-180': postPickerOpen }" aria-hidden="true"></i>
                                    </button>
                                </div>

                                <!-- Dönem & Adet Hızlı Seçim Butonları -->
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="text-[11px] text-zinc-400 mr-1">Hızlı Seç:</span>
                                    <button type="button"
                                            @click="selectByDays(1, 'Günün Yeni Yazıları')"
                                            class="px-2.5 py-1 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-[11px] font-semibold hover:border-zinc-400 dark:hover:border-zinc-600 transition-colors">
                                        <i class="fa-regular fa-clock text-amber-500 mr-1" aria-hidden="true"></i>Son 24 Saat
                                    </button>
                                    <button type="button"
                                            @click="selectByDays(7, 'Haftalık Bülten Özeti')"
                                            class="px-2.5 py-1 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-[11px] font-semibold hover:border-zinc-400 dark:hover:border-zinc-600 transition-colors">
                                        <i class="fa-solid fa-calendar-week text-blue-500 mr-1" aria-hidden="true"></i>Son 1 Hafta
                                    </button>
                                    <button type="button"
                                            @click="selectByDays(30, 'Aylık Bülten Özeti')"
                                            class="px-2.5 py-1 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-[11px] font-semibold hover:border-zinc-400 dark:hover:border-zinc-600 transition-colors">
                                        <i class="fa-regular fa-calendar text-indigo-500 mr-1" aria-hidden="true"></i>Son 1 Ay
                                    </button>
                                    <button type="button"
                                            @click="selectLatestCount(3, 'Son 3 Yazı Özeti')"
                                            class="px-2.5 py-1 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-[11px] font-semibold hover:border-zinc-400 dark:hover:border-zinc-600 transition-colors">
                                        Son 3 Yazı
                                    </button>
                                    <button type="button"
                                            @click="selectLatestCount(5, 'Son 5 Yazı Özeti')"
                                            class="px-2.5 py-1 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-[11px] font-semibold hover:border-zinc-400 dark:hover:border-zinc-600 transition-colors">
                                        Son 5 Yazı
                                    </button>
                                    <button type="button"
                                            @click="selectFilteredAll()"
                                            class="px-2.5 py-1 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-[11px] font-semibold hover:border-zinc-400 dark:hover:border-zinc-600 transition-colors">
                                        Filtrelenenleri Seç
                                    </button>
                                    <button type="button"
                                            x-show="selectedPostIds.length > 0"
                                            style="display: none;"
                                            @click="clearSelectedPosts()"
                                            class="px-2.5 py-1 rounded-lg text-[11px] font-semibold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors">
                                        Seçimi Temizle
                                    </button>
                                </div>
                            </div>

                            <!-- Arama ve Çoklu Seçim Listesi -->
                            <div x-show="postPickerOpen" class="border-t border-zinc-200 dark:border-zinc-800 p-3.5 space-y-3 bg-white/60 dark:bg-zinc-900/40">
                                <div class="relative">
                                    <i class="fa-solid fa-magnifying-glass text-zinc-400 text-xs absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" aria-hidden="true"></i>
                                    <input type="search"
                                           x-model="postSearch"
                                           placeholder="Yazı başlığı, kategori veya tarih filtrele..."
                                           class="w-full pl-8 pr-3 py-1.5 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                                </div>

                                <div class="max-h-52 overflow-y-auto divide-y divide-zinc-100 dark:divide-zinc-800/70 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                                    <template x-for="post in filteredPosts()" :key="post.id">
                                        <label class="flex items-start gap-3 px-3 py-2.5 text-xs cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors"
                                               :class="{ 'bg-emerald-50/50 dark:bg-emerald-950/20': isPostSelected(post.id) }">
                                            <input type="checkbox"
                                                   :checked="isPostSelected(post.id)"
                                                   @change="togglePost(post.id)"
                                                   class="mt-0.5 rounded border-zinc-300 dark:border-zinc-700 text-zinc-900 focus:ring-0">
                                            <div class="flex-1 min-w-0">
                                                <div class="font-semibold text-zinc-800 dark:text-zinc-200 truncate" x-text="post.title"></div>
                                                <div class="flex flex-wrap items-center gap-2 text-[11px] text-zinc-400 mt-0.5">
                                                    <span x-show="post.category" class="px-1.5 py-0.2 rounded bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 font-medium" x-text="post.category"></span>
                                                    <span x-text="post.date_label"></span>
                                                </div>
                                            </div>
                                        </label>
                                    </template>
                                    <div x-show="filteredPosts().length === 0" style="display: none;" class="p-4 text-center text-xs text-zinc-400">
                                        Arama kriterine uygun yayınlanmış yazı bulunamadı.
                                    </div>
                                </div>

                                <!-- İçeriğe Aktarma Aksiyonları -->
                                <div class="flex flex-wrap items-center justify-between gap-2 pt-1">
                                    <span class="text-[11px] text-zinc-500">
                                        Tek yazı seçerseniz makale duyurusu, birden fazla seçerseniz numaralı bülten özeti oluşturulur.
                                    </span>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <button type="button"
                                                :disabled="selectedPostIds.length === 0"
                                                @click="buildTemplateFromSelected(true)"
                                                class="px-3 py-1.5 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-xs font-semibold hover:bg-zinc-100 dark:hover:bg-zinc-800 disabled:opacity-40 disabled:pointer-events-none transition-colors">
                                            <i class="fa-solid fa-plus mr-1" aria-hidden="true"></i>Mevcut İçeriğe Ekle
                                        </button>
                                        <button type="button"
                                                :disabled="selectedPostIds.length === 0"
                                                @click="buildTemplateFromSelected(false)"
                                                class="px-3.5 py-1.5 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90 disabled:opacity-40 disabled:pointer-events-none transition-opacity">
                                            <i class="fa-solid fa-file-import mr-1.5" aria-hidden="true"></i>Şablonu Oluştur (<span x-text="selectedPostIds.length">0</span> Yazı)
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div>
                        <label class="block text-xs font-semibold mb-1.5">E-posta Konu Başlığı (Subject)</label>
                        <input type="text" name="subject" x-model="subject" required
                               placeholder="Örn: Haftalık Teknoloji Notları & Yeni Makale"
                               class="w-full px-3.5 py-2 text-sm rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
                    </div>

                    <div x-show="!previewMode">
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-semibold">Bülten İçeriği (HTML veya Düz Metin)</label>
                            <span class="text-[11px] text-zinc-400">Düz metin yazarsanız paragraflar otomatik biçimlendirilir</span>
                        </div>
                        <textarea name="content_html" x-model="contentHtml" rows="11" required
                                  placeholder="Abonelerinize iletmek istediğiniz mesajı veya HTML şablonunu buraya yazın..."
                                  class="w-full px-3.5 py-2.5 text-sm font-mono rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 leading-relaxed"></textarea>
                    </div>

                    <!-- Canlı Önizleme Kutusu -->
                    <div x-show="previewMode" style="display: none;" class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-100 dark:bg-zinc-950 p-4">
                        <div class="max-w-xl mx-auto bg-white text-zinc-900 rounded-xl border border-zinc-200 overflow-hidden shadow-sm">
                            <div class="px-5 py-3.5 border-b border-zinc-100 bg-zinc-50 font-bold text-sm">
                                <?= e(setting('site_title', 'Bvlog')) ?>
                            </div>
                            <div class="p-6 text-sm leading-relaxed space-y-3">
                                <div class="font-bold text-base text-zinc-900" x-text="subject || '(Konu başlığı girilmedi)'"></div>
                                <div class="prose prose-sm max-w-none text-zinc-800 whitespace-pre-line" x-html="contentHtml || '<em>(İçerik girilmedi)</em>'"></div>
                            </div>
                            <div class="px-5 py-3.5 bg-zinc-50 border-t border-zinc-200 text-[11px] text-zinc-500 space-y-1">
                                <div><strong>Yasal Bilgilendirme:</strong> Bu e-posta, 6698 sayılı KVKK ve 6563 sayılı ETK kapsamında açık rızanızla gönderilmiştir.</div>
                                <div class="text-red-600 font-semibold underline">Tek tıkla abonelikten çık (İzni İptal Et)</div>
                            </div>
                        </div>
                    </div>

                    <!-- Gönderim Hedefi -->
                    <div class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-950/40 space-y-3">
                        <label class="block text-xs font-bold uppercase tracking-wider text-zinc-400">Gönderim Hedefi</label>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 text-xs">
                            <label class="p-3 rounded-lg border cursor-pointer flex items-center gap-2.5"
                                   :class="sendMode === 'all_active' ? 'border-zinc-900 dark:border-zinc-100 bg-white dark:bg-zinc-900 font-bold' : 'border-zinc-200 dark:border-zinc-800'">
                                <input type="radio" name="send_mode" value="all_active" x-model="sendMode">
                                <span>Tüm Aktif Aboneler (<?= (int) ($stats['active'] ?? 0) ?>)</span>
                            </label>

                            <label class="p-3 rounded-lg border cursor-pointer flex items-center gap-2.5"
                                   :class="sendMode === 'selected' ? 'border-zinc-900 dark:border-zinc-100 bg-white dark:bg-zinc-900 font-bold' : 'border-zinc-200 dark:border-zinc-800'">
                                <input type="radio" name="send_mode" value="selected" x-model="sendMode">
                                <span>Seçili Aboneler</span>
                            </label>

                            <label class="p-3 rounded-lg border cursor-pointer flex items-center gap-2.5"
                                   :class="sendMode === 'test' ? 'border-zinc-900 dark:border-zinc-100 bg-white dark:bg-zinc-900 font-bold' : 'border-zinc-200 dark:border-zinc-800'">
                                <input type="radio" name="send_mode" value="test" x-model="sendMode">
                                <span>Önce Test Gönder</span>
                            </label>
                        </div>

                        <!-- Test E-posta Girişi -->
                        <div x-show="sendMode === 'test'" style="display: none;" class="pt-2">
                            <label class="block text-xs font-semibold mb-1">Test Alıcı E-posta Adresi</label>
                            <input type="email" name="test_email" x-model="testEmail"
                                   placeholder="test@alanadiniz.com"
                                   class="w-full sm:w-80 px-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                        </div>

                        <!-- Seçili Aboneler Listesi -->
                        <div x-show="sendMode === 'selected'" style="display: none;" class="pt-2">
                            <div class="text-xs font-semibold mb-1.5">Gönderilecek Aktif Aboneleri Seçin:</div>
                            <div class="max-h-44 overflow-y-auto divide-y divide-zinc-100 dark:divide-zinc-800 border border-zinc-200 dark:border-zinc-800 rounded-lg bg-white dark:bg-zinc-900">
                                <?php foreach ($subscribers as $sub):
                                    if (($sub['status'] ?? '') !== 'active') continue;
                                ?>
                                    <label class="px-3 py-2 flex items-center justify-between text-xs cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                        <span class="flex items-center gap-2">
                                            <input type="checkbox" name="subscriber_ids[]" value="<?= (int) $sub['id'] ?>" checked>
                                            <span class="font-medium"><?= e($sub['email']) ?></span>
                                            <?php if (!empty($sub['name'])): ?>
                                                <span class="text-zinc-400">(<?= e($sub['name']) ?>)</span>
                                            <?php endif; ?>
                                        </span>
                                        <span class="text-[11px] text-zinc-400"><?= e(format_date($sub['created_at'])) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-1">
                        <button type="submit"
                                class="px-5 py-2.5 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90 inline-flex items-center gap-2">
                            <i class="fa-regular fa-paper-plane" aria-hidden="true"></i>
                            <span x-text="sendMode === 'test' ? 'Test E-postasını Gönder' : 'Bülteni Şimdi Gönder'">Bülteni Şimdi Gönder</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Sağ Sütun: SMTP Bilgi & Yasal Uyumluluk Kartı -->
            <div class="lg:col-span-4 space-y-5">
                <div class="p-5 rounded-xl border <?= !empty($smtpStatus['smtp_ready']) ? 'border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900' : 'border-amber-300 dark:border-amber-800/80 bg-white dark:bg-zinc-900' ?> space-y-3 text-xs">
                    <div class="flex items-center justify-between">
                        <h3 class="font-bold text-sm">SMTP Gönderim Yapılandırması</h3>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold <?= !empty($smtpStatus['smtp_ready']) ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' ?>">
                            <?php if (!empty($smtpStatus['smtp_ready'])): ?>
                                SMTP Aktif
                            <?php elseif (empty($smtpStatus['plugin_active'])): ?>
                                Eklenti Devre Dışı
                            <?php else: ?>
                                SMTP Kapalı / Eksik
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="space-y-1.5 text-zinc-600 dark:text-zinc-400">
                        <div><strong>Gönderen Adı:</strong> <?= e($smtpStatus['from_name'] ?? '') ?></div>
                        <div><strong>Gönderen E-posta:</strong> <span class="font-mono"><?= e($smtpStatus['from_email'] ?? '') ?></span></div>
                        <?php if (!empty($smtpStatus['smtp_ready'])): ?>
                            <div><strong>Sunucu:</strong> <span class="font-mono"><?= e($smtpStatus['host'] . ':' . $smtpStatus['port'] . ' (' . strtoupper((string) $smtpStatus['encryption']) . ')') ?></span></div>
                        <?php else: ?>
                            <div class="text-amber-700 dark:text-amber-400 font-medium">
                                <i class="fa-solid fa-circle-info mr-1" aria-hidden="true"></i><?= e($smtpStatus['reason'] ?? 'Yerel PHP mail() kullanılıyor.') ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="pt-2 border-t border-zinc-100 dark:border-zinc-800">
                        <?php if (empty($smtpStatus['plugin_active'])): ?>
                            <a href="<?= e(url('/admin/plugins')) ?>" class="font-semibold text-amber-700 dark:text-amber-400 hover:underline inline-flex items-center gap-1">
                                <span>Eklentiler Sayfasından SMTP'yi Etkinleştir</span>
                                <i class="fa-solid fa-arrow-right text-[10px]" aria-hidden="true"></i>
                            </a>
                        <?php else: ?>
                            <a href="<?= e(url('/admin/smtp')) ?>" class="font-semibold text-blue-600 dark:text-blue-400 hover:underline inline-flex items-center gap-1">
                                <span>SMTP Eklenti Ayarlarını Düzenle</span>
                                <i class="fa-solid fa-arrow-right text-[10px]" aria-hidden="true"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="p-5 rounded-xl border border-emerald-200 dark:border-emerald-900/50 bg-emerald-50/40 dark:bg-emerald-950/20 space-y-2.5 text-xs">
                    <div class="font-bold text-emerald-900 dark:text-emerald-200 flex items-center gap-2">
                        <i class="fa-solid fa-shield-halved text-emerald-600" aria-hidden="true"></i>
                        <span>KVKK & 6563 Sayılı ETK Uyumluluğu</span>
                    </div>
                    <ul class="space-y-1.5 text-emerald-800 dark:text-emerald-300 leading-relaxed list-disc list-inside">
                        <li>Ziyaretçiler açık rıza kutusunu işaretlemeden abone olamaz.</li>
                        <li>Her abonenin onay zamanı, IP adresi ve onayladığı metnin SHA-256 özeti veritabanında saklanır.</li>
                        <li>Alıcıların e-posta adresleri birbirine görünmez (her aboneye bağımsız zarf ile gönderilir).</li>
                        <li>Her iletide tek tıkla <strong>Abonelikten Çık (`List-Unsubscribe`)</strong> başlığı ve bağlantısı yer alır.</li>
                    </ul>
                </div>
            </div>
        </div>

    <?php elseif ($section === 'subscribers'): ?>
        <!-- 2. ABONELER & YASAL İZİN KAYITLARI -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-8 space-y-4">
                <!-- Arama ve Durum Filtresi -->
                <form action="<?= e(url('/admin/newsletter/subscribers')) ?>" method="GET"
                      class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 flex flex-wrap items-center gap-2.5">
                    <input type="search" name="q" value="<?= e($search ?? '') ?>"
                           placeholder="E-posta veya isim ara..."
                           class="flex-1 min-w-[180px] px-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
                    <select name="status" class="px-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
                        <option value="">Tüm Durumlar</option>
                        <option value="active" <?= ($statusFilter ?? '') === 'active' ? 'selected' : '' ?>>Aktif Aboneler</option>
                        <option value="pending" <?= ($statusFilter ?? '') === 'pending' ? 'selected' : '' ?>>Doğrulama Bekleyenler</option>
                        <option value="unsubscribed" <?= ($statusFilter ?? '') === 'unsubscribed' ? 'selected' : '' ?>>İzni İptal Edenler</option>
                    </select>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold">
                        Filtrele
                    </button>
                </form>

                <!-- Abone Tablosu -->
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950/50 text-zinc-500 font-semibold">
                                    <th class="py-3 px-4">Abone Bilgisi</th>
                                    <th class="py-3 px-4">Durum</th>
                                    <th class="py-3 px-4">KVKK Açık Rıza İspatı</th>
                                    <th class="py-3 px-4 text-right">İşlem</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                <?php if (empty($subscribers)): ?>
                                    <tr>
                                        <td colspan="4" class="py-8 text-center text-zinc-500">Kayıtlı abone bulunamadı.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($subscribers as $sub): ?>
                                        <tr class="hover:bg-zinc-50/60 dark:hover:bg-zinc-800/30 transition-colors">
                                            <td class="py-3.5 px-4">
                                                <div class="font-bold text-zinc-900 dark:text-zinc-100"><?= e($sub['email']) ?></div>
                                                <?php if (!empty($sub['name'])): ?>
                                                    <div class="text-[11px] text-zinc-500"><?= e($sub['name']) ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3.5 px-4 whitespace-nowrap">
                                                <?php if (($sub['status'] ?? '') === 'active'): ?>
                                                    <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 font-semibold">
                                                        Aktif
                                                    </span>
                                                <?php elseif (($sub['status'] ?? '') === 'pending'): ?>
                                                    <span class="px-2 py-0.5 rounded bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800 font-semibold">
                                                        Onay Bekliyor
                                                    </span>
                                                <?php else: ?>
                                                    <span class="px-2 py-0.5 rounded bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 font-semibold">
                                                        Ayrıldı
                                                    </span>
                                                    <?php if (!empty($sub['unsubscribed_at'])): ?>
                                                        <div class="text-[10px] text-zinc-400 mt-0.5"><?= e(format_date($sub['unsubscribed_at'], true)) ?></div>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3.5 px-4 text-[11px] text-zinc-500">
                                                <div>Onay: <strong class="text-zinc-700 dark:text-zinc-300"><?= !empty($sub['consent_at']) ? e(format_date($sub['consent_at'], true)) : e(format_date($sub['created_at'], true)) ?></strong></div>
                                                <div class="font-mono text-[10px] text-zinc-400">IP: <?= e($sub['consent_ip'] ?: '—') ?></div>
                                            </td>
                                            <td class="py-3.5 px-4 text-right whitespace-nowrap space-x-1">
                                                <form action="<?= e(url('/admin/newsletter/subscribers/' . (int) $sub['id'] . '/status')) ?>" method="POST" class="inline">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="status" value="<?= ($sub['status'] ?? '') === 'active' ? 'unsubscribed' : 'active' ?>">
                                                    <button type="submit" class="px-2.5 py-1 rounded border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800 font-semibold">
                                                        <?= ($sub['status'] ?? '') === 'active' ? 'Pasife Al' : 'Aktifleştir' ?>
                                                    </button>
                                                </form>

                                                <form action="<?= e(url('/admin/newsletter/subscribers/' . (int) $sub['id'] . '/delete')) ?>" method="POST"
                                                      data-confirm="Bu aboneyi kalıcı olarak silmek istediğinize emin misiniz?"
                                                      class="inline">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="px-2.5 py-1 rounded border border-red-200 dark:border-red-900/50 text-red-600 hover:bg-red-50 dark:hover:bg-red-950/40 font-semibold">
                                                        Sil
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Sağ Sütun: Manuel Abone Ekle -->
            <div class="lg:col-span-4">
                <form action="<?= e(url('/admin/newsletter/subscribers/add')) ?>" method="POST"
                      class="p-5 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-4">
                    <?= csrf_field() ?>
                    <div>
                        <h3 class="text-sm font-bold">Manuel Abone Ekle</h3>
                        <p class="text-xs text-zinc-500 mt-0.5">Açık rızasını aldığınız bir okuyucuyu doğrudan listeye ekleyin.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold mb-1">E-posta Adresi</label>
                        <input type="email" name="email" required placeholder="okuyucu@eposta.com"
                               class="w-full px-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold mb-1">Ad Soyad (Opsiyonel)</label>
                        <input type="text" name="name" placeholder="Adı Soyadı"
                               class="w-full px-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
                    </div>

                    <label class="flex items-start gap-2 text-xs text-zinc-600 dark:text-zinc-400 cursor-pointer">
                        <input type="checkbox" name="admin_consent_confirm" value="1" required class="mt-0.5 rounded">
                        <span>Bu kişinin 6698 sayılı KVKK ve 6563 sayılı ETK kapsamında elektronik ileti alma açık rızasının bulunduğunu beyan ederim.</span>
                    </label>

                    <button type="submit" class="w-full py-2.5 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90">
                        Aboneyi Kaydet
                    </button>
                </form>
            </div>
        </div>

    <?php elseif ($section === 'campaigns'): ?>
        <!-- 3. GÖNDERİM GEÇMİŞİ -->
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 overflow-hidden">
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold">Gönderilen Bülten Geçmişi</h2>
                    <p class="text-xs text-zinc-500">Daha önce abonelere gönderilen e-posta kampanyalarının teslimat kayıtları.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950/50 text-zinc-500 font-semibold">
                            <th class="py-3 px-4">Konu Başlığı</th>
                            <th class="py-3 px-4">Gönderim Tarihi</th>
                            <th class="py-3 px-4">İletim Sonucu</th>
                            <th class="py-3 px-4 text-right">İşlem</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        <?php if (empty($campaigns)): ?>
                            <tr>
                                <td colspan="4" class="py-8 text-center text-zinc-500">Henüz gönderilmiş bir bülten kampanyası bulunmuyor.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($campaigns as $camp): ?>
                                <tr class="hover:bg-zinc-50/60 dark:hover:bg-zinc-800/30">
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-sm text-zinc-900 dark:text-zinc-100"><?= e($camp['subject']) ?></div>
                                        <div class="text-[11px] text-zinc-500 line-clamp-1 mt-0.5"><?= e(excerpt(strip_tags((string) $camp['content_html']), 110)) ?></div>
                                    </td>
                                    <td class="py-3.5 px-4 whitespace-nowrap text-zinc-600 dark:text-zinc-400">
                                        <?= e(format_date($camp['sent_at'] ?: $camp['created_at'], true)) ?>
                                    </td>
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 font-semibold">
                                            <?= (int) $camp['sent_count'] ?> / <?= (int) $camp['total_recipients'] ?> Başarılı
                                        </span>
                                        <?php if ((int) $camp['failed_count'] > 0): ?>
                                            <span class="ml-1 px-2 py-0.5 rounded bg-red-50 text-red-700 dark:bg-red-950/60 dark:text-red-300 font-semibold">
                                                <?= (int) $camp['failed_count'] ?> Hata
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                        <form action="<?= e(url('/admin/newsletter/campaigns/' . (int) $camp['id'] . '/delete')) ?>" method="POST"
                                              data-confirm="Bu gönderim kaydını silmek istediğinize emin misiniz?"
                                              class="inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="px-2.5 py-1 rounded border border-red-200 dark:border-red-900/50 text-red-600 hover:bg-red-50 font-semibold">
                                                Kaydı Sil
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php elseif ($section === 'settings'): ?>
        <!-- 4. AYARLAR & KVKK YASAL METİNLERİ -->
        <form action="<?= e(url('/admin/newsletter/settings')) ?>" method="POST"
              class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-6 w-full">
            <?= csrf_field() ?>

            <div class="border-b border-zinc-100 dark:border-zinc-800 pb-4">
                <h2 class="text-base font-bold tracking-tight">Footer Üstü Bülten Kutusu & Yasal Onay Ayarları</h2>
                <p class="text-xs text-zinc-500 mt-0.5">Sitenin altbilgi (footer) üstünde görünen bülten formunun metinlerini, çift aşamalı doğrulama (Double Opt-In) tercihini ve KVKK Aydınlatma Metni'ni özelleştirin.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold mb-1.5">Bülten Kutusu Başlığı</label>
                    <input type="text" name="newsletter_title" value="<?= e($config['title'] ?? '') ?>" required
                           class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1.5">Abone Ol Buton Yazısı</label>
                    <input type="text" name="newsletter_button_text" value="<?= e($config['button_text'] ?? 'Bültene Abone Ol') ?>" required
                           class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1.5">Bülten Kutusu Açıklama Metni</label>
                <textarea name="newsletter_description" rows="2"
                          class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950"><?= e($config['description'] ?? '') ?></textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <label class="flex items-center justify-between gap-4 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 cursor-pointer">
                    <div class="text-xs space-y-0.5">
                        <strong class="block text-sm font-bold text-zinc-900 dark:text-zinc-100">Çift Aşamalı Doğrulama (Double Opt-In)</strong>
                        <span class="text-zinc-500">Aktif edildiğinde aboneye önce doğrulama e-postası gider; linke tıklayana kadar durumu "Onay Bekliyor" olur.</span>
                    </div>
                    <input type="checkbox" name="newsletter_double_opt_in" value="1" role="switch" <?= !empty($config['double_opt_in']) ? 'checked' : '' ?> class="ios-switch shrink-0">
                </label>

                <label class="flex items-center justify-between gap-4 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 cursor-pointer">
                    <div class="text-xs space-y-0.5">
                        <strong class="block text-sm font-bold text-zinc-900 dark:text-zinc-100">Hoş Geldiniz E-postası Gönder</strong>
                        <span class="text-zinc-500">Yeni abone kaydolduğunda (veya doğruladığında) otomatik karşılama e-postası iletilir.</span>
                    </div>
                    <input type="checkbox" name="newsletter_send_welcome" value="1" role="switch" <?= !empty($config['send_welcome']) ? 'checked' : '' ?> class="ios-switch shrink-0">
                </label>

                <label class="flex items-center justify-between gap-4 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 cursor-pointer">
                    <div class="text-xs space-y-0.5">
                        <strong class="block text-sm font-bold text-zinc-900 dark:text-zinc-100">Ad Soyad Alanını Göster</strong>
                        <span class="text-zinc-500">Footer üstü bülten formunda e-posta adresinin yanında isteğe bağlı isim kutusu gösterilir.</span>
                    </div>
                    <input type="checkbox" name="newsletter_show_name" value="1" role="switch" <?= !empty($config['show_name_field']) ? 'checked' : '' ?> class="ios-switch shrink-0">
                </label>

                <label class="flex items-center justify-between gap-4 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 cursor-pointer">
                    <div class="text-xs space-y-0.5">
                        <strong class="block text-sm font-bold text-zinc-900 dark:text-zinc-100">Yeni Blog Yazısında Otomatik Bildir</strong>
                        <span class="text-zinc-500">Yeni bir blog yazısı yayınlandığında aktif abonelere otomatik duyuru e-postası gönderir.</span>
                    </div>
                    <input type="checkbox" name="newsletter_auto_notify_post" value="1" role="switch" <?= !empty($config['auto_notify_post']) ? 'checked' : '' ?> class="ios-switch shrink-0">
                </label>
            </div>

            <div class="border-t border-zinc-100 dark:border-zinc-800 pt-5 space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-400">KVKK & 6563 Sayılı ETK Yasal Onay Metinleri</h3>

                <div>
                    <label class="block text-xs font-semibold mb-1.5">Onay Kutusu (Checkbox) Yanındaki Açık Rıza Beyanı</label>
                    <input type="text" name="newsletter_consent_label" value="<?= e($config['consent_label'] ?? '') ?>" required
                           class="w-full px-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1.5">Yasal Aydınlatma Modalı Başlığı</label>
                    <input type="text" name="newsletter_legal_modal_title" value="<?= e($config['legal_modal_title'] ?? '') ?>" required
                           class="w-full px-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1.5">KVKK Aydınlatma Metni ve Elektronik İleti Koşulları (Tam Metin)</label>
                    <textarea name="newsletter_legal_modal_body" rows="10" required
                              class="w-full px-3 py-2 text-xs font-mono rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 leading-relaxed"><?= e($config['legal_modal_body'] ?? '') ?></textarea>
                    <span class="block text-[11px] text-zinc-400 mt-1">Bu metnin SHA-256 kriptografik özeti her abonenin açık rıza kaydıyla birlikte veritabanında saklanır.</span>
                </div>
            </div>

            <button type="submit" class="px-5 py-2.5 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90">
                Bülten & Yasal Metin Ayarlarını Kaydet
            </button>
        </form>
    <?php endif; ?>

</div>
