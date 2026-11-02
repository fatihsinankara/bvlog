<div class="space-y-6 w-full">

    <form action="<?= e(url('/admin/settings/permalinks')) ?>" method="POST"
          class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-6 w-full">
        <?= csrf_field() ?>
        <input type="hidden" name="active_tab" value="permalinks">

        <div class="border-b border-zinc-100 dark:border-zinc-800 pb-4">
            <h2 class="text-base font-bold tracking-tight">Blog Yazısı Kalıcı Bağlantı Yapısı (URL)</h2>
            <p class="text-xs text-zinc-500 mt-0.5">Yazılarınızın ziyaretçilere ve arama motorlarına hangi URL şablonuyla sunulacağını belirleyin.</p>
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

</div>
