<div class="space-y-6 w-full">

    <form action="<?= e(url('/admin/settings/modules')) ?>" method="POST"
          class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-4 w-full">
        <?= csrf_field() ?>
        <input type="hidden" name="active_tab" value="modules">

        <div class="border-b border-zinc-100 dark:border-zinc-800 pb-4">
            <h2 class="text-base font-bold tracking-tight">Modül Yönetimi</h2>
            <p class="text-xs text-zinc-500 mt-0.5">Kişisel blogunuz her zaman aktiftir. İhtiyacınıza göre Portfolyo, CV, Yorum ve İletişim modüllerini açıp kapatabilirsiniz.</p>
        </div>

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

</div>
