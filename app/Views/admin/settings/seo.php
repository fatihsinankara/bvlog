<div class="space-y-6 w-full"
     x-data="{
         ogImage: <?= e(json_encode($settings['og_image'] ?? '')) ?>
     }">

    <form action="<?= e(url('/admin/settings/seo')) ?>" method="POST"
          class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-5 w-full">
        <?= csrf_field() ?>
        <input type="hidden" name="active_tab" value="seo">

        <div class="border-b border-zinc-100 dark:border-zinc-800 pb-4">
            <h2 class="text-base font-bold tracking-tight">SEO & Sosyal Paylaşım</h2>
            <p class="text-xs text-zinc-500 mt-0.5">Arama motoru indeksleme tercihlerini, OpenGraph paylaşım görselini ve otomatik besleme (XML/RSS) adreslerini yönetin.</p>
        </div>

        <div>
            <label class="block text-xs font-semibold mb-1.5">Varsayılan Sosyal Medya Paylaşım Görseli</label>
            <div class="flex items-center gap-2">
                <input type="text" name="og_image" x-model="ogImage" placeholder="/storage/uploads/..."
                       class="flex-1 px-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
                <button type="button"
                        @click="$dispatch('open-media-picker', { mode: 'featured', onSelect: (item) => { ogImage = item.filepath; } })"
                        class="px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700 text-xs font-semibold hover:bg-zinc-100 dark:hover:bg-zinc-800">
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

</div>
