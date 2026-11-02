<div class="space-y-6 w-full">

    <form action="<?= e(url('/admin/settings/security')) ?>" method="POST"
          class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-5 w-full">
        <?= csrf_field() ?>
        <input type="hidden" name="active_tab" value="security">

        <div class="border-b border-zinc-100 dark:border-zinc-800 pb-4">
            <h2 class="text-base font-bold tracking-tight">Bakım Modu & Güvenlik</h2>
            <p class="text-xs text-zinc-500 mt-0.5">Siteyi ziyaretçilere geçici olarak bakım moduna alabilir veya yönetici parolanızı güncelleyebilirsiniz.</p>
        </div>

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

</div>
