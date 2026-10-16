<form action="<?= e(url('/install/database')) ?>" method="POST" class="space-y-4">
    <?= csrf_field() ?>
    <div>
        <h2 class="text-base font-bold mb-1">MySQL Veritabanı Bağlantı Bilgileri</h2>
        <p class="text-xs text-zinc-500">cPanel veya sunucunuzda oluşturduğunuz MySQL veritabanı bilgilerini girin.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="sm:col-span-2">
            <label class="block text-xs font-semibold mb-1.5">Veritabanı Sunucusu (Host)</label>
            <input type="text" name="db_host" value="<?= e($db['host'] ?? '127.0.0.1') ?>" required
                   class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-300 focus:outline-none focus:border-zinc-900">
        </div>
        <div>
            <label class="block text-xs font-semibold mb-1.5">Port</label>
            <input type="text" name="db_port" value="<?= e($db['port'] ?? '3306') ?>" required
                   class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-300 focus:outline-none focus:border-zinc-900">
        </div>
    </div>

    <div>
        <label class="block text-xs font-semibold mb-1.5">Veritabanı Adı</label>
        <input type="text" name="db_name" value="<?= e($db['database'] ?? 'bvlog') ?>" required
               class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-300 focus:outline-none focus:border-zinc-900">
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-xs font-semibold mb-1.5">Kullanıcı Adı</label>
            <input type="text" name="db_user" value="<?= e($db['username'] ?? 'root') ?>" required
                   class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-300 focus:outline-none focus:border-zinc-900">
        </div>
        <div>
            <label class="block text-xs font-semibold mb-1.5">Parola</label>
            <input type="password" name="db_pass" value="<?= e($db['password'] ?? '') ?>"
                   placeholder="Boş bırakılabilir (yerel sunucu)"
                   class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-300 focus:outline-none focus:border-zinc-900">
        </div>
    </div>

    <div class="pt-4 flex items-center justify-between border-t border-zinc-100">
        <a href="<?= e(url('/install?step=1')) ?>" class="text-xs font-medium text-zinc-500 hover:text-zinc-900">
            <i class="fa-solid fa-arrow-left mr-1"></i>Geri Dön
        </a>
        <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-zinc-900 text-white text-sm font-semibold hover:opacity-90 transition-opacity">
            <span>Bağlantıyı Test Et ve Devam Et</span>
            <i class="fa-solid fa-arrow-right text-xs"></i>
        </button>
    </div>
</form>
