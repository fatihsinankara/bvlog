<div class="space-y-6 w-full"
     x-data="{
         authorAvatar: <?= e(json_encode($author['avatar'] ?? '')) ?>
     }">

    <form action="<?= e(url('/admin/settings/profile')) ?>" method="POST" enctype="multipart/form-data"
          class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-5 w-full">
        <?= csrf_field() ?>
        <input type="hidden" name="active_tab" value="profile">

        <div class="border-b border-zinc-100 dark:border-zinc-800 pb-4">
            <h2 class="text-base font-bold tracking-tight">Yazar & Profil Bilgileri</h2>
            <p class="text-xs text-zinc-500 mt-0.5">Anasayfa yazar kartında, yazı altlarında ve özgeçmiş sayfasında görünecek kişisel bilgilerinizi düzenleyin.</p>
        </div>

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
                            class="px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700 text-xs font-semibold hover:bg-zinc-100 dark:hover:bg-zinc-800">
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

</div>
