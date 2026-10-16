<?php
use Core\Request;
$activeTab = Request::get('tab', 'general');
?>

<div class="space-y-6"
     x-data="{
         tab: <?= e(json_encode($activeTab)) ?>,
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
            'general'  => ['icon' => 'fa-solid fa-sliders', 'label' => 'Genel Ayarlar'],
            'profile'  => ['icon' => 'fa-regular fa-id-badge', 'label' => 'Yazar & Profil'],
            'homepage' => ['icon' => 'fa-solid fa-table-columns', 'label' => 'Anasayfa Yerleşimi'],
            'modules'  => ['icon' => 'fa-solid fa-cubes', 'label' => 'Modül Yönetimi'],
            'seo'      => ['icon' => 'fa-solid fa-magnifying-glass-chart', 'label' => 'SEO & Sosyal Paylaşım'],
            'security' => ['icon' => 'fa-solid fa-shield-halved', 'label' => 'Bakım & Güvenlik'],
        ];
        foreach ($tabs as $key => $info):
        ?>
            <button type="button"
                    @click="tab = '<?= e($key) ?>'"
                    :class="tab === '<?= e($key) ?>' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100'"
                    class="px-3.5 py-2 rounded-lg transition-colors inline-flex items-center gap-1.5">
                <i class="<?= e($info['icon']) ?>"></i>
                <span><?= e($info['label']) ?></span>
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

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="sm:col-span-2">
                <label class="block text-xs font-semibold mb-1.5">Altbilgi (Footer) Telif Metni</label>
                <input type="text" name="footer_text" value="<?= e($settings['footer_text'] ?? '') ?>"
                       class="w-full px-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1.5">Sayfa Başına Yazı Sayısı</label>
                <input type="number" name="posts_per_page" value="<?= (int) ($settings['posts_per_page'] ?? 10) ?>" min="1" max="50"
                       class="w-full px-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
            </div>
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
                            <input type="checkbox" x-model="sec.enabled" class="rounded border-zinc-300">
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
            <label class="flex items-start gap-3 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                <input type="checkbox" name="module_portfolio" value="1" <?= ($settings['module_portfolio'] ?? '1') === '1' ? 'checked' : '' ?> class="mt-0.5 rounded">
                <div class="text-xs">
                    <strong class="block text-sm font-bold">Portfolyo Modülü (/portfolio)</strong>
                    <span class="text-zinc-500">Geliştirdiğiniz projeleri, kullanılan teknolojileri ve canlı demo bağlantılarını sergiler.</span>
                </div>
            </label>

            <label class="flex items-start gap-3 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                <input type="checkbox" name="module_cv" value="1" <?= ($settings['module_cv'] ?? '1') === '1' ? 'checked' : '' ?> class="mt-0.5 rounded">
                <div class="text-xs">
                    <strong class="block text-sm font-bold">CV / Özgeçmiş Modülü (/cv)</strong>
                    <span class="text-zinc-500">İş deneyimlerinizi, eğitiminizi ve yeteneklerinizi yazdırılabilir/PDF uyumlu sayfada sunar.</span>
                </div>
            </label>

            <label class="flex items-start gap-3 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                <input type="checkbox" name="module_comments" value="1" <?= ($settings['module_comments'] ?? '1') === '1' ? 'checked' : '' ?> class="mt-0.5 rounded">
                <div class="text-xs">
                    <strong class="block text-sm font-bold">Yazı Yorum Sistemi</strong>
                    <span class="text-zinc-500">Ziyaretçilerin blog yazılarına yorum bırakmasına izin verir.</span>
                </div>
            </label>

            <label class="flex items-start gap-3 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                <input type="checkbox" name="comments_moderation" value="1" <?= ($settings['comments_moderation'] ?? '1') === '1' ? 'checked' : '' ?> class="mt-0.5 rounded">
                <div class="text-xs">
                    <strong class="block text-sm font-bold">Yorum Moderasyon Zorunluluğu</strong>
                    <span class="text-zinc-500">Yeni yorumlar doğrudan yayınlanmaz, yönetici onayına düşer.</span>
                </div>
            </label>

            <label class="flex items-start gap-3 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                <input type="checkbox" name="module_contact" value="1" <?= ($settings['module_contact'] ?? '1') === '1' ? 'checked' : '' ?> class="mt-0.5 rounded">
                <div class="text-xs">
                    <strong class="block text-sm font-bold">İletişim Sayfası (/contact)</strong>
                    <span class="text-zinc-500">Spam korumalı iletişim formunu ve mesaj kutusunu aktif tutar.</span>
                </div>
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

        <label class="flex items-center gap-3 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 cursor-pointer">
            <input type="checkbox" name="seo_allow_index" value="1" <?= ($settings['seo_allow_index'] ?? '1') === '1' ? 'checked' : '' ?> class="rounded">
            <span class="text-xs">
                <strong class="block font-bold">Arama Motorlarının Siteyi İndekslemesine İzin Ver</strong>
                <span class="text-zinc-500">Kapatıldığında <code class="font-mono">noindex, nofollow</code> etiketi ve <code class="font-mono">robots.txt</code> engeli uygulanır.</span>
            </span>
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

    <!-- 6. BAKIM MODU & GÜVENLİK -->
    <form x-show="tab === 'security'" style="display: none;" action="<?= e(url('/admin/settings')) ?>" method="POST"
          class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-5 max-w-3xl">
        <?= csrf_field() ?>
        <input type="hidden" name="active_tab" value="security">

        <label class="flex items-start gap-3 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 cursor-pointer">
            <input type="checkbox" name="maintenance_mode" value="1" <?= ($settings['maintenance_mode'] ?? '0') === '1' ? 'checked' : '' ?> class="mt-0.5 rounded">
            <div class="text-xs">
                <strong class="block text-sm font-bold">Bakım Modunu Etkinleştir</strong>
                <span class="text-zinc-500">Aktif edildiğinde ziyaretçiler bakım ekranını görür; yöneticiler siteyi görüntülemeye devam edebilir.</span>
            </div>
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
</div>
