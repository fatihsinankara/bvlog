<div class="space-y-6 w-full"
     x-data="{
         siteLogo: <?= e(json_encode($settings['site_logo'] ?? '')) ?>,
         siteFavicon: <?= e(json_encode($settings['site_favicon'] ?? '')) ?>
     }">

    <form action="<?= e(url('/admin/settings/general')) ?>" method="POST"
          class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-5 w-full">
        <?= csrf_field() ?>
        <input type="hidden" name="active_tab" value="general">

        <div class="border-b border-zinc-100 dark:border-zinc-800 pb-4">
            <h2 class="text-base font-bold tracking-tight">Genel Site Ayarları</h2>
            <p class="text-xs text-zinc-500 mt-0.5">Sitenizin başlığı, sloganı, logosu ve listeleme tercihlerini yapılandırın.</p>
        </div>

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
                            class="px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700 text-xs font-semibold hover:bg-zinc-100 dark:hover:bg-zinc-800">
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
                            class="px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700 text-xs font-semibold hover:bg-zinc-100 dark:hover:bg-zinc-800">
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

        <div>
            <label class="block text-xs font-semibold mb-1.5">Kanonik site adresi</label>
            <input type="url" name="site_url" value="<?= e($settings['site_url'] ?? '') ?>" placeholder="https://alanadiniz.com"
                   class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
            <p class="text-[11px] text-zinc-500 mt-1">Canonical, RSS ve e-posta bağlantıları bu adresi kullanır. Boşsa istekteki host başlığına düşülür.</p>
        </div>

        <button type="submit" class="px-5 py-2.5 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90">
            Genel Ayarları Kaydet
        </button>
    </form>

</div>
