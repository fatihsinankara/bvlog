<div class="text-center py-6 space-y-4">
    <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-700 inline-flex items-center justify-center text-xl">
        <i class="fa-solid fa-check"></i>
    </div>
    <h2 class="text-xl font-bold">Bvlog Kurulumu Başarıyla Tamamlandı!</h2>
    <p class="text-sm text-zinc-600 max-w-md mx-auto">
        Veritabanı tabloları oluşturuldu, örnek içerikler yüklendi ve kurulum sihirbazı güvenlik amacıyla otomatik olarak kilitlendi (<code class="px-1.5 py-0.5 rounded bg-zinc-100 text-xs">storage/installed.lock</code>).
    </p>

    <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-3">
        <a href="<?= e(url('/admin')) ?>"
           class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg bg-zinc-900 text-white text-sm font-semibold hover:opacity-90">
            <i class="fa-solid fa-sliders"></i>
            <span>Yönetim Paneline Git</span>
        </a>
        <a href="<?= e(url('/')) ?>"
           class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg border border-zinc-300 text-sm font-semibold hover:bg-zinc-50">
            <i class="fa-solid fa-globe"></i>
            <span>Siteyi Görüntüle</span>
        </a>
    </div>
</div>
