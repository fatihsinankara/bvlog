<form action="<?= e(url('/install/complete')) ?>" method="POST" class="space-y-5">
    <?= csrf_field() ?>
    <div>
        <h2 class="text-base font-bold mb-1">Site Bilgileri & Yönetici Hesabı</h2>
        <p class="text-xs text-zinc-500">Sitenizin temel kimliğini, yönetici bilgilerinizi ve başlangıç modüllerini seçin.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-xs font-semibold mb-1.5">Site Başlığı</label>
            <input type="text" name="site_title" value="Bvlog" required
                   class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-300 focus:outline-none focus:border-zinc-900">
        </div>
        <div>
            <label class="block text-xs font-semibold mb-1.5">Kısa Slogan (Tagline)</label>
            <input type="text" name="site_tagline" value="Yazılım, Mimari ve Kişisel Notlar"
                   class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-300 focus:outline-none focus:border-zinc-900">
        </div>
    </div>

    <div class="border-t border-zinc-100 pt-4">
        <div class="text-xs font-bold uppercase tracking-wider text-zinc-400 mb-3">Yönetici (Admin) Hesabı</div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold mb-1.5">Ad Soyad (Yazar Adı)</label>
                <input type="text" name="admin_name" value="Fatih" required
                       class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-300 focus:outline-none focus:border-zinc-900">
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1.5">Kullanıcı Adı</label>
                <input type="text" name="admin_username" value="admin" required
                       class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-300 focus:outline-none focus:border-zinc-900">
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1.5">E-posta Adresi</label>
                <input type="email" name="admin_email" value="admin@bvlog.test" required
                       class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-300 focus:outline-none focus:border-zinc-900">
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1.5">Yönetici Parolası (En az 6 karakter)</label>
                <input type="password" name="admin_password" required minlength="6" placeholder="••••••••"
                       class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-300 focus:outline-none focus:border-zinc-900">
            </div>
        </div>
    </div>

    <div class="border-t border-zinc-100 pt-4">
        <div class="text-xs font-bold uppercase tracking-wider text-zinc-400 mb-3">Opsiyonel Modüller (Daha sonra panelden değiştirilebilir)</div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <label class="flex items-center justify-between gap-3 p-3.5 rounded-xl border border-zinc-200 cursor-pointer hover:bg-zinc-50 transition-colors">
                <span class="text-xs space-y-0.5">
                    <strong class="block text-zinc-900 font-semibold">Portfolyo Modülü</strong>
                    <span class="text-zinc-500">Projelerinizi ve canlı demo bağlantılarını sergileyin.</span>
                </span>
                <input type="checkbox" name="module_portfolio" value="1" role="switch" checked class="ios-switch shrink-0">
            </label>
            <label class="flex items-center justify-between gap-3 p-3.5 rounded-xl border border-zinc-200 cursor-pointer hover:bg-zinc-50 transition-colors">
                <span class="text-xs space-y-0.5">
                    <strong class="block text-zinc-900 font-semibold">CV / Özgeçmiş Modülü</strong>
                    <span class="text-zinc-500">İş deneyimi, eğitim ve yeteneklerinizi sunun.</span>
                </span>
                <input type="checkbox" name="module_cv" value="1" role="switch" checked class="ios-switch shrink-0">
            </label>
        </div>
    </div>

    <div class="pt-4 flex items-center justify-between border-t border-zinc-100">
        <a href="<?= e(url('/install?step=2')) ?>" class="text-xs font-medium text-zinc-500 hover:text-zinc-900">
            <i class="fa-solid fa-arrow-left mr-1"></i>Geri Dön
        </a>
        <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg bg-zinc-900 text-white text-sm font-semibold hover:opacity-90 transition-opacity">
            <i class="fa-solid fa-check"></i>
            <span>Kurulumu Tamamla</span>
        </button>
    </div>
</form>
