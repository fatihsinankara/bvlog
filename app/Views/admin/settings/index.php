<?php
use Core\Request;
$activeTab = Request::get('tab', 'general');
?>

<div class="space-y-6"
     x-data="{
         tab: <?= e(json_encode($activeTab)) ?>,
         copiedKey: '',
         copyText(text, key) {
             if (navigator.clipboard && navigator.clipboard.writeText) {
                 navigator.clipboard.writeText(text);
             } else {
                 const ta = document.createElement('textarea');
                 ta.value = text;
                 document.body.appendChild(ta);
                 ta.select();
                 document.execCommand('copy');
                 document.body.removeChild(ta);
             }
             this.copiedKey = key;
             setTimeout(() => { if (this.copiedKey === key) this.copiedKey = ''; }, 2000);
         },
         siteLogo: <?= e(json_encode($settings['site_logo'] ?? '')) ?>,
         siteFavicon: <?= e(json_encode($settings['site_favicon'] ?? '')) ?>,
         authorAvatar: <?= e(json_encode($author['avatar'] ?? '')) ?>,
         ogImage: <?= e(json_encode($settings['og_image'] ?? '')) ?>,
         sections: <?= e(json_encode($sections)) ?>,
         moveUp(idx) {
             if (idx <= 0) return;
             const temp = this.sections[idx - 1];
             this.sections[idx - 1] = this.sections[idx];
             this.sections[idx] = temp;
         },
         moveDown(idx) {
             if (idx >= this.sections.length - 1) return;
             const temp = this.sections[idx + 1];
             this.sections[idx + 1] = this.sections[idx];
             this.sections[idx] = temp;
         }
     }">

    <!-- Sekme Çubuğu -->
    <div class="flex flex-wrap items-center gap-1.5 border-b border-zinc-200 dark:border-zinc-800 pb-3 text-xs font-semibold">
        <?php
        $tabs = [
            'general'    => ['icon' => 'fa-solid fa-sliders', 'label' => 'Genel Ayarlar'],
            'profile'    => ['icon' => 'fa-regular fa-id-badge', 'label' => 'Yazar & Profil'],
            'homepage'   => ['icon' => 'fa-solid fa-table-columns', 'label' => 'Anasayfa Yerleşimi'],
            'modules'    => ['icon' => 'fa-solid fa-cubes', 'label' => 'Modül Yönetimi'],
            'permalinks' => ['icon' => 'fa-solid fa-link', 'label' => 'Kalıcı Bağlantılar'],
            'seo'        => ['icon' => 'fa-solid fa-magnifying-glass-chart', 'label' => 'SEO & Sosyal Paylaşım'],
            'cron'       => ['icon' => 'fa-regular fa-clock', 'label' => 'Zamanlanmış Görevler (Cron)', 'badge' => !empty($scheduledPostsCount)],
            'updates'    => ['icon' => 'fa-solid fa-arrows-rotate', 'label' => 'Güncellemeler', 'badge' => !empty($updateInfo['has_update'])],
            'security'   => ['icon' => 'fa-solid fa-shield-halved', 'label' => 'Bakım & Güvenlik'],
        ];
        foreach ($tabs as $key => $info):
        ?>
            <button type="button"
                    @click="tab = '<?= e($key) ?>'"
                    :class="tab === '<?= e($key) ?>' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100'"
                    class="px-3.5 py-2 rounded-lg transition-colors inline-flex items-center gap-1.5 relative">
                <i class="<?= e($info['icon']) ?>"></i>
                <span><?= e($info['label']) ?></span>
                <?php if (!empty($info['badge'])): ?>
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse" title="Yeni güncelleme mevcut"></span>
                <?php endif; ?>
            </button>
        <?php endforeach; ?>
    </div>

    <!-- 1. GENEL AYARLAR -->
    <form x-show="tab === 'general'" action="<?= e(url('/admin/settings')) ?>" method="POST"
          class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-5 max-w-3xl">
        <?= csrf_field() ?>
        <input type="hidden" name="active_tab" value="general">

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold mb-1.5">Site Başlığı</label>
                <input type="text" name="site_title" value="<?= e($settings['site_title'] ?? 'Bvlog') ?>" required
                       class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1.5">Site Sloganı (Tagline)</label>
                <input type="text" name="site_tagline" value="<?= e($settings['site_tagline'] ?? '') ?>"
                       class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold mb-1.5">Site Meta Açıklaması</label>
            <textarea name="site_description" rows="2"
                      class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950"><?= e($settings['site_description'] ?? '') ?></textarea>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold mb-1.5">Site Logosu (Opsiyonel)</label>
                <div class="flex items-center gap-2">
                    <input type="text" name="site_logo" x-model="siteLogo" placeholder="/storage/uploads/..."
                           class="flex-1 px-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
                    <button type="button"
                            @click="$dispatch('open-media-picker', { mode: 'inline', onSelect: (item) => { siteLogo = item.filepath; } })"
                            class="px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700 text-xs font-semibold hover:bg-zinc-100">
                        Seç
                    </button>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1.5">Site Favicon</label>
                <div class="flex items-center gap-2">
                    <input type="text" name="site_favicon" x-model="siteFavicon" placeholder="/storage/uploads/..."
                           class="flex-1 px-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
                    <button type="button"
                            @click="$dispatch('open-media-picker', { mode: 'inline', onSelect: (item) => { siteFavicon = item.filepath; } })"
                            class="px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700 text-xs font-semibold hover:bg-zinc-100">
                        Seç
                    </button>
                </div>
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold mb-1.5">Sayfa Başına Yazı Sayısı</label>
            <input type="number" name="posts_per_page" value="<?= (int) ($settings['posts_per_page'] ?? 10) ?>" min="1" max="50"
                   class="w-full sm:w-48 px-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
        </div>

        <button type="submit" class="px-5 py-2.5 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90">
            Genel Ayarları Kaydet
        </button>
    </form>

    <!-- 2. YAZAR & PROFİL -->
    <form x-show="tab === 'profile'" style="display: none;" action="<?= e(url('/admin/settings')) ?>" method="POST" enctype="multipart/form-data"
          class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-5 max-w-3xl">
        <?= csrf_field() ?>
        <input type="hidden" name="active_tab" value="profile">

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold mb-1.5">Yazar Adı Soyadı</label>
                <input type="text" name="author_name" value="<?= e($author['name'] ?? ($settings['author_name'] ?? '')) ?>" required
                       class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1.5">Unvan / Rol</label>
                <input type="text" name="author_title" value="<?= e($author['role_title'] ?? ($settings['author_title'] ?? '')) ?>"
                       class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold mb-1.5">Kısa Biyografi (Anasayfa & Yazı Altı Kutusu)</label>
            <textarea name="author_bio" rows="3"
                      class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950"><?= e($author['bio'] ?? ($settings['author_bio'] ?? '')) ?></textarea>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold mb-1.5">Profil Fotoğrafı (Avatar)</label>
                <div class="flex items-center gap-2">
                    <input type="text" name="author_avatar" x-model="authorAvatar" placeholder="/storage/uploads/..."
                           class="flex-1 px-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
                    <button type="button"
                            @click="$dispatch('open-media-picker', { mode: 'inline', onSelect: (item) => { authorAvatar = item.filepath; } })"
                            class="px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700 text-xs font-semibold hover:bg-zinc-100">
                        Galeriden Seç
                    </button>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1.5">İndirilebilir Hazır CV (PDF Dosyası)</label>
                <input type="file" name="cv_pdf_file" accept="application/pdf"
                       class="w-full text-xs file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-zinc-100 file:text-zinc-900">
                <?php if (!empty($author['cv_pdf'])): ?>
                    <span class="block text-[11px] text-emerald-600 mt-1">Mevcut PDF: <?= e($author['cv_pdf']) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <div class="border-t border-zinc-100 dark:border-zinc-800 pt-4">
            <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-400 mb-3">Sosyal Ağ Bağlantıları</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold mb-1"><i class="fa-brands fa-github mr-1"></i>GitHub URL</label>
                    <input type="url" name="social_github" value="<?= e($settings['social_github'] ?? '') ?>" class="w-full px-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1"><i class="fa-brands fa-linkedin mr-1"></i>LinkedIn URL</label>
                    <input type="url" name="social_linkedin" value="<?= e($settings['social_linkedin'] ?? '') ?>" class="w-full px-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1"><i class="fa-brands fa-x-twitter mr-1"></i>X / Twitter URL</label>
                    <input type="url" name="social_twitter" value="<?= e($settings['social_twitter'] ?? '') ?>" class="w-full px-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1"><i class="fa-regular fa-envelope mr-1"></i>İletişim E-posta</label>
                    <input type="email" name="social_email" value="<?= e($settings['social_email'] ?? '') ?>" class="w-full px-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
                </div>
            </div>
        </div>

        <button type="submit" class="px-5 py-2.5 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90">
            Profil Bilgilerini Kaydet
        </button>
    </form>

    <!-- 3. ANASAYFA YERLEŞİMİ & BLOK SIRALAMASI -->
    <form x-show="tab === 'homepage'" style="display: none;" action="<?= e(url('/admin/settings')) ?>" method="POST"
          class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-6 max-w-3xl">
        <?= csrf_field() ?>
        <input type="hidden" name="active_tab" value="homepage">
        <input type="hidden" name="homepage_sections_json" :value="JSON.stringify(sections)">

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold mb-1.5">Anasayfa Giriş (Hero) Stili</label>
                <select name="homepage_hero_style" class="w-full px-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
                    <?php $hs = $settings['homepage_hero_style'] ?? 'minimal_bio'; ?>
                    <option value="minimal_bio" <?= $hs === 'minimal_bio' ? 'selected' : '' ?>>Minimalist Yazar Kartı (Önerilen)</option>
                    <option value="featured_post" <?= $hs === 'featured_post' ? 'selected' : '' ?>>Öne Çıkan Yazı Vitrini</option>
                    <option value="split" <?= $hs === 'split' ? 'selected' : '' ?>>İkiye Bölünmüş (Sol Biyo / Sağ Son Yazılar)</option>
                    <option value="none" <?= $hs === 'none' ? 'selected' : '' ?>>Hero Yok (Doğrudan İçerik Akışı)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1.5">Yazı Listeleme Görünümü</label>
                <select name="homepage_post_style" class="w-full px-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
                    <?php $ps = $settings['homepage_post_style'] ?? 'list'; ?>
                    <option value="list" <?= $ps === 'list' ? 'selected' : '' ?>>Tek Sütun Minimalist Okuma Listesi (Medium/Substack)</option>
                    <option value="grid" <?= $ps === 'grid' ? 'selected' : '' ?>>Çok Sütunlu Kart Izgara (Grid)</option>
                </select>
            </div>
        </div>

        <div class="space-y-3">
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-400">Anasayfa Blok Sıralaması & Görünürlük</h3>
                <p class="text-xs text-zinc-500 mt-0.5">Blokların sırasını yukarı/aşağı oklarla değiştirebilir veya istediğiniz bölümü gizleyebilirsiniz.</p>
            </div>

            <div class="divide-y divide-zinc-100 dark:divide-zinc-800 border border-zinc-200 dark:border-zinc-800 rounded-xl overflow-hidden">
                <template x-for="(sec, idx) in sections" :key="sec.id">
                    <div class="px-4 py-3 flex items-center justify-between bg-zinc-50/50 dark:bg-zinc-950/40 text-xs">
                        <label class="flex items-center gap-3 cursor-pointer font-medium">
                            <input type="checkbox" x-model="sec.enabled" role="switch" class="ios-switch">
                            <span x-text="(idx + 1) + '. ' + sec.title" :class="sec.enabled ? 'font-bold text-zinc-900 dark:text-zinc-100' : 'line-through text-zinc-400'"></span>
                        </label>
                        <div class="flex items-center gap-1">
                            <button type="button" @click="moveUp(idx)" :disabled="idx === 0"
                                    class="w-7 h-7 rounded border border-zinc-200 dark:border-zinc-700 disabled:opacity-30 hover:bg-white dark:hover:bg-zinc-800">
                                <i class="fa-solid fa-arrow-up"></i>
                            </button>
                            <button type="button" @click="moveDown(idx)" :disabled="idx === sections.length - 1"
                                    class="w-7 h-7 rounded border border-zinc-200 dark:border-zinc-700 disabled:opacity-30 hover:bg-white dark:hover:bg-zinc-800">
                                <i class="fa-solid fa-arrow-down"></i>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <button type="submit" class="px-5 py-2.5 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90">
            Anasayfa Düzenini Kaydet
        </button>
    </form>

    <!-- 4. MODÜL YÖNETİMİ -->
    <form x-show="tab === 'modules'" style="display: none;" action="<?= e(url('/admin/settings')) ?>" method="POST"
          class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-4 max-w-3xl">
        <?= csrf_field() ?>
        <input type="hidden" name="active_tab" value="modules">

        <p class="text-xs text-zinc-500">Kişisel blogunuz her zaman aktiftir. İhtiyacınıza göre Portfolyo, CV, Yorum ve İletişim modüllerini açıp kapatabilirsiniz.</p>

        <div class="space-y-3">
            <label class="flex items-center justify-between gap-4 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition-colors">
                <div class="text-xs space-y-0.5">
                    <strong class="block text-sm font-bold text-zinc-900 dark:text-zinc-100">Portfolyo Modülü (/portfolio)</strong>
                    <span class="text-zinc-500 dark:text-zinc-400">Geliştirdiğiniz projeleri, kullanılan teknolojileri ve canlı demo bağlantılarını sergiler.</span>
                </div>
                <input type="checkbox" name="module_portfolio" value="1" role="switch" <?= ($settings['module_portfolio'] ?? '1') === '1' ? 'checked' : '' ?> class="ios-switch shrink-0">
            </label>

            <label class="flex items-center justify-between gap-4 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition-colors">
                <div class="text-xs space-y-0.5">
                    <strong class="block text-sm font-bold text-zinc-900 dark:text-zinc-100">CV / Özgeçmiş Modülü (/cv)</strong>
                    <span class="text-zinc-500 dark:text-zinc-400">İş deneyimlerinizi, eğitiminizi ve yeteneklerinizi yazdırılabilir/PDF uyumlu sayfada sunar.</span>
                </div>
                <input type="checkbox" name="module_cv" value="1" role="switch" <?= ($settings['module_cv'] ?? '1') === '1' ? 'checked' : '' ?> class="ios-switch shrink-0">
            </label>

            <label class="flex items-center justify-between gap-4 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition-colors">
                <div class="text-xs space-y-0.5">
                    <strong class="block text-sm font-bold text-zinc-900 dark:text-zinc-100">Yazı Yorum Sistemi</strong>
                    <span class="text-zinc-500 dark:text-zinc-400">Ziyaretçilerin blog yazılarına yorum bırakmasına izin verir.</span>
                </div>
                <input type="checkbox" name="module_comments" value="1" role="switch" <?= ($settings['module_comments'] ?? '1') === '1' ? 'checked' : '' ?> class="ios-switch shrink-0">
            </label>

            <label class="flex items-center justify-between gap-4 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition-colors">
                <div class="text-xs space-y-0.5">
                    <strong class="block text-sm font-bold text-zinc-900 dark:text-zinc-100">Yorum Moderasyon Zorunluluğu</strong>
                    <span class="text-zinc-500 dark:text-zinc-400">Yeni yorumlar doğrudan yayınlanmaz, yönetici onayına düşer.</span>
                </div>
                <input type="checkbox" name="comments_moderation" value="1" role="switch" <?= ($settings['comments_moderation'] ?? '1') === '1' ? 'checked' : '' ?> class="ios-switch shrink-0">
            </label>

            <label class="flex items-center justify-between gap-4 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition-colors">
                <div class="text-xs space-y-0.5">
                    <strong class="block text-sm font-bold text-zinc-900 dark:text-zinc-100">İletişim Sayfası (/contact)</strong>
                    <span class="text-zinc-500 dark:text-zinc-400">Spam korumalı iletişim formunu ve mesaj kutusunu aktif tutar.</span>
                </div>
                <input type="checkbox" name="module_contact" value="1" role="switch" <?= ($settings['module_contact'] ?? '1') === '1' ? 'checked' : '' ?> class="ios-switch shrink-0">
            </label>
        </div>

        <button type="submit" class="px-5 py-2.5 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90">
            Modül Ayarlarını Kaydet
        </button>
    </form>

    <!-- 5. SEO & SOSYAL PAYLAŞIM -->
    <form x-show="tab === 'seo'" style="display: none;" action="<?= e(url('/admin/settings')) ?>" method="POST"
          class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-5 max-w-3xl">
        <?= csrf_field() ?>
        <input type="hidden" name="active_tab" value="seo">

        <div>
            <label class="block text-xs font-semibold mb-1.5">Varsayılan Sosyal Medya Paylaşım Görseli</label>
            <div class="flex items-center gap-2">
                <input type="text" name="og_image" x-model="ogImage" placeholder="/storage/uploads/..."
                       class="flex-1 px-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
                <button type="button"
                        @click="$dispatch('open-media-picker', { mode: 'featured', onSelect: (item) => { ogImage = item.filepath; } })"
                        class="px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700 text-xs font-semibold hover:bg-zinc-100">
                    Galeriden Seç
                </button>
            </div>
        </div>

        <label class="flex items-center justify-between gap-4 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition-colors">
            <span class="text-xs space-y-0.5">
                <strong class="block text-sm font-bold text-zinc-900 dark:text-zinc-100">Arama Motorlarının Siteyi İndekslemesine İzin Ver</strong>
                <span class="text-zinc-500 dark:text-zinc-400">Kapatıldığında <code class="font-mono">noindex, nofollow</code> etiketi ve <code class="font-mono">robots.txt</code> engeli uygulanır.</span>
            </span>
            <input type="checkbox" name="seo_allow_index" value="1" role="switch" <?= ($settings['seo_allow_index'] ?? '1') === '1' ? 'checked' : '' ?> class="ios-switch shrink-0">
        </label>

        <div class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 text-xs space-y-1.5">
            <div class="font-bold">Otomatik SEO & Besleme Adresleri:</div>
            <div>Site Haritası: <a href="<?= e(url('/sitemap.xml')) ?>" target="_blank" class="font-mono underline"><?= e(url('/sitemap.xml')) ?></a></div>
            <div>RSS 2.0 Beslemesi: <a href="<?= e(url('/rss.xml')) ?>" target="_blank" class="font-mono underline"><?= e(url('/rss.xml')) ?></a></div>
            <div>Robots.txt: <a href="<?= e(url('/robots.txt')) ?>" target="_blank" class="font-mono underline"><?= e(url('/robots.txt')) ?></a></div>
        </div>

        <button type="submit" class="px-5 py-2.5 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90">
            SEO Ayarlarını Kaydet
        </button>
    </form>

    <!-- 5. KALICI BAĞLANTILAR (PERMALINKS) -->
    <form x-show="tab === 'permalinks'" style="display: none;" action="<?= e(url('/admin/settings')) ?>" method="POST"
          class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-6 max-w-3xl">
        <?= csrf_field() ?>
        <input type="hidden" name="active_tab" value="permalinks">

        <div>
            <h2 class="text-sm font-bold tracking-tight">Blog Yazısı Kalıcı Bağlantı Yapısı (URL)</h2>
            <p class="text-xs text-zinc-500 mt-1">Yazılarınızın ziyaretçilere ve arama motorlarına hangi URL şablonuyla sunulacağını belirleyin.</p>
        </div>

        <?php
        $currentPermalink = $settings['permalink_structure'] ?? 'default';
        $siteHost = rtrim(base_url(), '/');
        $permalinkOptions = [
            'default' => [
                'title'       => 'Varsayılan (Standart)',
                'tag'         => '/blog/%post_name%',
                'preview'     => $siteHost . '/blog/ornek-yazi',
                'description' => 'Yazılarınız standart /blog/ ön ekiyle sunulur. Basit ve güvenilirdir.',
            ],
            'category_slug' => [
                'title'       => 'Kategori ve Yazı Başlığı (Önerilen)',
                'tag'         => '/%category%/%post_name%',
                'preview'     => $siteHost . '/yazilim/ornek-yazi',
                'description' => 'Yazı, doğrudan bağlı olduğu kategorinin altında açılır (site.com/kategori/konu-adi). Kategorisiz yazılar /blog/ altında listelenir.',
            ],
            'blog_category_slug' => [
                'title'       => 'Blog, Kategori ve Yazı Başlığı',
                'tag'         => '/blog/%category%/%post_name%',
                'preview'     => $siteHost . '/blog/yazilim/ornek-yazi',
                'description' => '/blog/ ön ekiyle birlikte kategori dizini korunur.',
            ],
            'post_only' => [
                'title'       => 'Yalnızca Yazı Başlığı (Sade URL)',
                'tag'         => '/%post_name%',
                'preview'     => $siteHost . '/ornek-yazi',
                'description' => 'Doğrudan alan adından sonra yazı adı gelir. En kısa ve yalın URL biçimidir.',
            ],
        ];
        ?>

        <div class="space-y-3">
            <?php foreach ($permalinkOptions as $optKey => $opt): ?>
                <label class="block p-4 rounded-xl border transition-all cursor-pointer <?= $currentPermalink === $optKey ? 'border-zinc-900 dark:border-zinc-100 bg-zinc-50/80 dark:bg-zinc-800/40 ring-1 ring-zinc-900 dark:ring-zinc-100' : 'border-zinc-200 dark:border-zinc-800 hover:border-zinc-300 dark:hover:border-zinc-700 bg-white dark:bg-zinc-900' ?>">
                    <div class="flex items-start gap-3">
                        <input type="radio" name="permalink_structure" value="<?= e($optKey) ?>"
                               <?= $currentPermalink === $optKey ? 'checked' : '' ?>
                               class="mt-1 text-zinc-900 dark:text-zinc-100 focus:ring-zinc-500">
                        <div class="space-y-1 flex-1">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="text-sm font-bold text-zinc-900 dark:text-zinc-100"><?= e($opt['title']) ?></span>
                                <span class="font-mono text-xs px-2 py-0.5 rounded border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 text-zinc-600 dark:text-zinc-400 font-semibold"><?= e($opt['tag']) ?></span>
                            </div>
                            <div class="font-mono text-xs text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 px-2.5 py-1 rounded inline-block">
                                <?= e($opt['preview']) ?>
                            </div>
                            <p class="text-xs text-zinc-500 pt-0.5"><?= e($opt['description']) ?></p>
                        </div>
                    </div>
                </label>
            <?php endforeach; ?>
        </div>

        <div class="p-4 rounded-xl border border-blue-200 dark:border-blue-900/50 bg-blue-50/50 dark:bg-blue-950/20 text-xs text-blue-900 dark:text-blue-200 flex items-start gap-2.5">
            <i class="fa-solid fa-circle-info text-blue-500 mt-0.5 shrink-0"></i>
            <div>
                <strong>Otomatik 301 Kalıcı Yönlendirme:</strong> Kalıcı bağlantı yapısını değiştirdiğinizde, eski URL yapısıyla gelen ziyaretçiler ve arama motorları yeni URL yapısına otomatik olarak kalıcı (301) olarak yönlendirilir. Böylece hiçbir kırık bağlantı oluşmaz ve arama motoru sıralamalarınız korunur.
            </div>
        </div>

        <button type="submit" class="px-5 py-2.5 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90">
            Kalıcı Bağlantı Ayarlarını Kaydet
        </button>
    </form>

    <!-- 6. GÜNCELLEMELER (UPDATES) -->
    <div x-show="tab === 'updates'" style="display: none;" class="space-y-6 max-w-3xl">
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
                        v<?= e($updateInfo['current_version'] ?? '1.2.0') ?>
                    </div>
                    <span class="inline-block text-[11px] text-zinc-500">Mevcut sistem sürümünüz</span>
                </div>

                <div class="p-4 rounded-xl border <?= !empty($updateInfo['has_update']) ? 'border-amber-300 dark:border-amber-800 bg-amber-50/40 dark:bg-amber-950/20' : 'border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-950/40' ?> space-y-1">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400">En Son GitHub Sürümü</span>
                    <div class="text-2xl font-black font-mono <?= !empty($updateInfo['has_update']) ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-900 dark:text-zinc-100' ?>">
                        v<?= e($updateInfo['latest_version'] ?? '1.2.0') ?>
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
                        <!-- Tek Tıkla Otomatik Güncelleme -->
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

                <!-- Otomatik Yükseltme Rehberi -->
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
                                Bvlog'un en son kararlı sürümünü (v<?= e($updateInfo['current_version'] ?? '1.2.0') ?>) kullanıyorsunuz. Herhangi bir işlem yapmanıza gerek yoktur.
                            </div>
                        </div>
                    </div>

                    <!-- Test Amaçlı Yeniden Kurulum Butonu -->
                    <form action="<?= e(url('/admin/updates/apply')) ?>" method="POST"
                          x-data="{ isSubmitting: false }"
                          @submit="if (!confirm('Mevcut v<?= e($updateInfo['current_version'] ?? '1.2.0') ?> sürümü GitHub Releases üzerinden yeniden indirilip kurulacaktır.\n\nBu işlem güncelleme motorunu test etmek veya sistem dosyalarını onarmak için kullanılır. Verileriniz korunur. Devam etmek istiyor musunuz?')) { $event.preventDefault(); return false; } isSubmitting = true;">
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

    <!-- 7. BAKIM MODU & GÜVENLİK -->
    <form x-show="tab === 'security'" style="display: none;" action="<?= e(url('/admin/settings')) ?>" method="POST"
          class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-5 max-w-3xl">
        <?= csrf_field() ?>
        <input type="hidden" name="active_tab" value="security">

        <label class="flex items-center justify-between gap-4 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition-colors">
            <div class="text-xs space-y-0.5">
                <strong class="block text-sm font-bold text-zinc-900 dark:text-zinc-100">Bakım Modunu Etkinleştir</strong>
                <span class="text-zinc-500 dark:text-zinc-400">Aktif edildiğinde ziyaretçiler bakım ekranını görür; yöneticiler siteyi görüntülemeye devam edebilir.</span>
            </div>
            <input type="checkbox" name="maintenance_mode" value="1" role="switch" <?= ($settings['maintenance_mode'] ?? '0') === '1' ? 'checked' : '' ?> class="ios-switch shrink-0">
        </label>

        <div>
            <label class="block text-xs font-semibold mb-1.5">Bakım Modu Mesajı</label>
            <textarea name="maintenance_message" rows="2"
                      class="w-full px-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950"><?= e($settings['maintenance_message'] ?? '') ?></textarea>
        </div>

        <div class="border-t border-zinc-100 dark:border-zinc-800 pt-4 space-y-4">
            <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-400">Yönetici Parolasını Değiştir</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold mb-1.5">Mevcut Parola</label>
                    <input type="password" name="current_password" placeholder="Değiştirmek istemiyorsanız boş bırakın"
                           class="w-full px-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1.5">Yeni Parola (En az 6 karakter)</label>
                    <input type="password" name="new_password" placeholder="Yeni parolanız"
                           class="w-full px-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
                </div>
            </div>
        </div>

        <button type="submit" class="px-5 py-2.5 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90">
            Güvenlik Ayarlarını Kaydet
        </button>
    </form>

    <!-- 8. ZAMANLANMIŞ GÖREVLER (CRON YÖNETİMİ) -->
    <div x-show="tab === 'cron'" style="display: none;" class="space-y-6 max-w-4xl">

        <!-- Üst Özet ve Hızlı Çalıştırma Kartı -->
        <div class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-zinc-100 dark:border-zinc-800 pb-5">
                <div>
                    <h2 class="text-base font-bold tracking-tight flex items-center gap-2">
                        <i class="fa-regular fa-clock text-zinc-500"></i>
                        <span>Zamanlanmış Görevler (Cron) Yönetimi</span>
                    </h2>
                    <p class="text-xs text-zinc-500 mt-0.5">
                        İleri tarihli blog yazılarının otomatik yayınlanması ve sistem bakım görevlerinin çalışma durumu.
                    </p>
                </div>
                <form action="<?= e(url('/admin/cron/run')) ?>" method="POST">
                    <?= csrf_field() ?>
                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90 transition-opacity">
                        <i class="fa-solid fa-play text-[10px]"></i>
                        <span>Tüm Görevleri Şimdi Çalıştır</span>
                    </button>
                </form>
            </div>

            <!-- Durum Metrikleri -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-950/40 space-y-1">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400">Son Cron Çalışması</span>
                    <div class="text-sm font-bold text-zinc-900 dark:text-zinc-100">
                        <?= !empty($cronSummary['ran_at']) ? e(format_date($cronSummary['ran_at'], true)) : 'Henüz çalışmadı' ?>
                    </div>
                    <span class="inline-block text-[11px] text-zinc-500">
                        Kaynak: <?= e($cronSummary['trigger_label'] ?? '—') ?>
                        <?php if (!empty($cronSummary['duration_ms'])): ?>
                            (<?= (int) $cronSummary['duration_ms'] ?> ms)
                        <?php endif; ?>
                    </span>
                </div>

                <div class="p-4 rounded-xl border <?= !empty($scheduledPostsCount) ? 'border-amber-300 dark:border-amber-800 bg-amber-50/40 dark:bg-amber-950/20' : 'border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-950/40' ?> space-y-1">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400">Bekleyen Zamanlanmış Yazı</span>
                    <div class="text-2xl font-black font-mono <?= !empty($scheduledPostsCount) ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-900 dark:text-zinc-100' ?>">
                        <?= (int) ($scheduledPostsCount ?? 0) ?>
                    </div>
                    <a href="<?= e(url('/admin/posts')) ?>" class="inline-block text-[11px] text-zinc-500 hover:underline">
                        Yazı listesinde görüntüle &rarr;
                    </a>
                </div>

                <div class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-950/40 space-y-1">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400">Aktif Görev Sayısı</span>
                    <div class="text-2xl font-black font-mono text-zinc-900 dark:text-zinc-100">
                        <?= count($cronTasks ?? []) ?>
                    </div>
                    <span class="inline-block text-[11px] text-zinc-500">Çekirdek & eklenti görevleri</span>
                </div>
            </div>
        </div>

        <!-- Sunucuya Özel cPanel / Plesk / SSH Cron Komutları -->
        <div class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-5">
            <div>
                <h3 class="text-sm font-bold tracking-tight flex items-center gap-2">
                    <i class="fa-solid fa-terminal text-emerald-600"></i>
                    <span>Sunucunuza Özel Hazır Cron Komutları (cPanel / Plesk / DirectAdmin)</span>
                </h3>
                <p class="text-xs text-zinc-500 mt-1">
                    Sunucu dizininiz (<code class="font-mono text-[11px] px-1 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800"><?= e($cronCommands['cron_file'] ?? '') ?></code>) ve PHP çalışma yolunuz otomatik algılandı. Hosting panelinizdeki <strong>Cron Jobs (Zamanlanmış Görevler)</strong> ekranına aşağıdaki komutlardan birini <strong>Her Dakika (<code>* * * * *</code>)</strong> çalışacak şekilde ekleyin.
                </p>
            </div>

            <div class="space-y-4">
                <!-- 1. PHP CLI Komutu (cPanel Önerilen) -->
                <div class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/60 dark:bg-zinc-950/50 space-y-2">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="text-xs font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                            <span>1. cPanel / DirectAdmin Komutu (PHP CLI)</span>
                            <span class="px-2 py-0.5 rounded text-[10px] bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 font-semibold">Önerilen</span>
                        </div>
                        <span class="text-[11px] text-zinc-400 font-mono">Sıklık: * * * * * (Dakikada bir)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="text" readonly value="<?= e($cronCommands['cpanel_cli'] ?? '') ?>"
                               class="flex-1 px-3 py-2 text-xs font-mono rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200 select-all focus:outline-none">
                        <button type="button"
                                @click="copyText(<?= e(json_encode($cronCommands['cpanel_cli'] ?? '')) ?>, 'cpanel_cli')"
                                class="px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-xs font-semibold hover:bg-zinc-100 dark:hover:bg-zinc-800 shrink-0 inline-flex items-center gap-1.5">
                            <i :class="copiedKey === 'cpanel_cli' ? 'fa-solid fa-check text-emerald-500' : 'fa-regular fa-copy'"></i>
                            <span x-text="copiedKey === 'cpanel_cli' ? 'Kopyalandı!' : 'Kopyala'">Kopyala</span>
                        </button>
                    </div>
                    <p class="text-[11px] text-zinc-500">
                        cPanel'de süre seçimi (<code>* * * * *</code>) ayrı kutulardan yapıldığı için "Komut (Command)" alanına doğrudan yukarıdaki satırı yapıştırabilirsiniz.
                    </p>
                </div>

                <!-- 2. HTTP / cURL Komutu -->
                <div class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/60 dark:bg-zinc-950/50 space-y-2">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100">2. cURL ile HTTP Tetikleme Komutu (Alternatif)</span>
                        <span class="text-[11px] text-zinc-400 font-mono">Plesk / Harici Cron Uyumlu</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="text" readonly value="<?= e($cronCommands['cpanel_curl'] ?? '') ?>"
                               class="flex-1 px-3 py-2 text-xs font-mono rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200 select-all focus:outline-none">
                        <button type="button"
                                @click="copyText(<?= e(json_encode($cronCommands['cpanel_curl'] ?? '')) ?>, 'cpanel_curl')"
                                class="px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-xs font-semibold hover:bg-zinc-100 dark:hover:bg-zinc-800 shrink-0 inline-flex items-center gap-1.5">
                            <i :class="copiedKey === 'cpanel_curl' ? 'fa-solid fa-check text-emerald-500' : 'fa-regular fa-copy'"></i>
                            <span x-text="copiedKey === 'cpanel_curl' ? 'Kopyalandı!' : 'Kopyala'">Kopyala</span>
                        </button>
                    </div>
                </div>

                <!-- 3. Doğrudan Cron URL ve Güvenlik Anahtarı (Token) -->
                <div class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/60 dark:bg-zinc-950/50 space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100">3. Doğrudan Tetikleme URL'si & Gizli Anahtar (Token)</span>
                        <a href="<?= e($cronCommands['cron_url'] ?? '#') ?>" target="_blank"
                           class="text-[11px] font-semibold text-blue-600 dark:text-blue-400 hover:underline inline-flex items-center gap-1">
                            <span>Tarayıcıda Aç / Test Et</span>
                            <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                        </a>
                    </div>
                    <div class="flex flex-wrap sm:flex-nowrap items-center gap-2">
                        <input type="text" readonly value="<?= e($cronCommands['cron_url'] ?? '') ?>"
                               class="w-full sm:flex-1 px-3 py-2 text-xs font-mono rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200 select-all focus:outline-none">
                        <button type="button"
                                @click="copyText(<?= e(json_encode($cronCommands['cron_url'] ?? '')) ?>, 'cron_url')"
                                class="px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-xs font-semibold hover:bg-zinc-100 dark:hover:bg-zinc-800 shrink-0 inline-flex items-center gap-1.5">
                            <i :class="copiedKey === 'cron_url' ? 'fa-solid fa-check text-emerald-500' : 'fa-regular fa-copy'"></i>
                            <span x-text="copiedKey === 'cron_url' ? 'Kopyalandı!' : 'URL Kopyala'">URL Kopyala</span>
                        </button>
                        <form action="<?= e(url('/admin/cron/regenerate-token')) ?>" method="POST"
                              data-confirm="Cron güvenlik anahtarını (token) yenilemek istediğinize emin misiniz? Mevcut HTTP/cURL Cron komutlarındaki eski anahtar geçersiz olacaktır."
                              data-confirm-title="Cron Anahtarını Yenile"
                              data-confirm-btn="Evet, Anahtarı Yenile"
                              data-confirm-variant="primary"
                              class="inline">
                            <?= csrf_field() ?>
                            <button type="submit" class="px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-xs font-semibold text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/30 shrink-0 inline-flex items-center gap-1.5">
                                <i class="fa-solid fa-key"></i>
                                <span>Anahtarı Yenile</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Opsiyonel Ziyaretçi Tetiklemeli Yedek Mod (Web-Cron) -->
            <form action="<?= e(url('/admin/settings')) ?>" method="POST" class="pt-3 border-t border-zinc-100 dark:border-zinc-800 space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="active_tab" value="cron">

                <label class="flex items-center justify-between gap-4 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition-colors">
                    <div class="text-xs space-y-0.5">
                        <strong class="block text-sm font-bold text-zinc-900 dark:text-zinc-100">Ziyaretçi Trafiğiyle Otomatik Tetikleme (Web-Cron Yedek Modu)</strong>
                        <span class="text-zinc-500 dark:text-zinc-400">cPanel Cron tanımlayamadığınız ortamlarda etkinleştirebilirsiniz. Siteye ziyaretçi girdiğinde dakikada en fazla 1 kez bekleyen zamanlanmış görevleri çalıştırır.</span>
                    </div>
                    <input type="checkbox" name="cron_web_fallback" value="1" role="switch" <?= ($settings['cron_web_fallback'] ?? '0') === '1' ? 'checked' : '' ?> class="ios-switch shrink-0">
                </label>

                <button type="submit" class="px-5 py-2.5 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90">
                    Cron Tercihlerini Kaydet
                </button>
            </form>
        </div>

        <!-- Kayıtlı Zamanlanmış Görevler Tablosu -->
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 overflow-hidden">
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold">Kayıtlı Zamanlanmış Görevler</h3>
                    <p class="text-xs text-zinc-500">Sistemde tanımlı tüm çekirdek ve eklenti görevlerini tekil olarak test edebilirsiniz.</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950/50 text-zinc-500 font-semibold">
                            <th class="py-3 px-4">Görev & Açıklama</th>
                            <th class="py-3 px-4">Sıklık</th>
                            <th class="py-3 px-4">Son Çalışma</th>
                            <th class="py-3 px-4">Sonuç / Durum</th>
                            <th class="py-3 px-4 text-right">İşlem</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        <?php foreach ($cronTasks ?? [] as $task): ?>
                            <tr class="hover:bg-zinc-50/60 dark:hover:bg-zinc-800/30 transition-colors">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-sm text-zinc-900 dark:text-zinc-100"><?= e($task['title']) ?></span>
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-zinc-100 dark:bg-zinc-800 text-zinc-500"><?= e($task['source']) ?></span>
                                    </div>
                                    <p class="text-[11px] text-zinc-500 mt-0.5"><?= e($task['description']) ?></p>
                                    <span class="text-[10px] font-mono text-zinc-400">ID: <?= e($task['id']) ?></span>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-950 font-semibold">
                                        <?= e($task['interval_label']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap text-zinc-600 dark:text-zinc-400">
                                    <?php if (!empty($task['last_run_at'])): ?>
                                        <div class="font-medium text-zinc-900 dark:text-zinc-100"><?= e(format_date($task['last_run_at'], true)) ?></div>
                                        <div class="text-[11px] text-zinc-400"><?= (int) ($task['last_duration_ms'] ?? 0) ?> ms &middot; <?= (int) $task['run_count'] ?> kez</div>
                                    <?php else: ?>
                                        <span class="text-zinc-400">Henüz çalışmadı</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <?php if (($task['last_status'] ?? '') === 'success'): ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 font-semibold mb-1">
                                            <i class="fa-solid fa-check text-[10px]"></i>
                                            <span>Başarılı</span>
                                        </span>
                                    <?php elseif (($task['last_status'] ?? '') === 'error'): ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-red-50 text-red-700 dark:bg-red-950/60 dark:text-red-300 border border-red-200 dark:border-red-800 font-semibold mb-1">
                                            <i class="fa-solid fa-xmark text-[10px]"></i>
                                            <span>Hata</span>
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 font-semibold mb-1">
                                            <span>Bekliyor</span>
                                        </span>
                                    <?php endif; ?>
                                    <div class="text-[11px] text-zinc-500 max-w-xs"><?= e($task['last_message']) ?></div>
                                </td>
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <form action="<?= e(url('/admin/cron/run')) ?>" method="POST" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="task_id" value="<?= e($task['id']) ?>">
                                        <button type="submit" class="px-2.5 py-1.5 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800 font-semibold inline-flex items-center gap-1">
                                            <i class="fa-solid fa-play text-[10px]"></i>
                                            <span>Çalıştır</span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Son Çalışma Günlüğü (Cron Geçmişi) -->
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 overflow-hidden">
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-800">
                <h3 class="text-sm font-bold">Son Çalışma Günlüğü (Geçmiş)</h3>
                <p class="text-xs text-zinc-500">En son gerçekleşen 25 Cron tetiklemesinin özeti.</p>
            </div>
            <?php if (empty($cronHistory)): ?>
                <div class="p-8 text-center text-xs text-zinc-500">Henüz kaydedilmiş bir Cron çalışma geçmişi bulunmuyor.</div>
            <?php else: ?>
                <div class="divide-y divide-zinc-100 dark:divide-zinc-800 max-h-80 overflow-y-auto">
                    <?php foreach ($cronHistory as $h): ?>
                        <div class="px-6 py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                            <div class="space-y-0.5">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-zinc-900 dark:text-zinc-100"><?= e(format_date($h['ran_at'] ?? '', true)) ?></span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-950">
                                        <?= e($h['trigger_label'] ?? $h['trigger'] ?? 'CLI') ?>
                                    </span>
                                    <span class="text-[11px] text-zinc-400"><?= (int) ($h['duration_ms'] ?? 0) ?> ms</span>
                                </div>
                                <div class="text-zinc-600 dark:text-zinc-400 text-[11px]"><?= e($h['summary'] ?? '') ?></div>
                            </div>
                            <div class="shrink-0">
                                <?php if (($h['status'] ?? 'success') === 'error'): ?>
                                    <span class="px-2 py-0.5 rounded bg-red-50 text-red-700 dark:bg-red-950/60 dark:text-red-300 font-semibold text-[11px]">Hata</span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 font-semibold text-[11px]">Tamamlandı</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>
