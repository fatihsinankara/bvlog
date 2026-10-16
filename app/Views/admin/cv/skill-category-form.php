<?php
$isEdit = !empty($category);
$actionUrl = $isEdit ? url('/admin/cv/category/' . $category['id'] . '/update') : url('/admin/cv/category');
?>

<div class="max-w-2xl space-y-6">
    <div class="flex items-center justify-between">
        <a href="<?= e(url('/admin/cv?tab=categories')) ?>" class="inline-flex items-center gap-1.5 text-xs font-semibold text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Yetenek Grupları Listesine Dön</span>
        </a>
    </div>

    <form action="<?= e($actionUrl) ?>" method="POST" class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-5">
        <?= csrf_field() ?>

        <div class="space-y-4">
            <div>
                <label class="block text-xs font-semibold mb-1.5">Kategori / Grup Adı *</label>
                <input type="text" name="name" required maxlength="100"
                       value="<?= e($category['name'] ?? '') ?>"
                       placeholder="Örn: Backend & Veritabanı, Mobil Geliştirme, Yapay Zeka & Veri"
                       class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 focus:outline-none focus:border-zinc-500">
                <p class="text-[11px] text-zinc-500 mt-1">CV sayfasında ve anasayfada yetenekler bu grup başlığı altında toplanır.</p>
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1.5">Sıralama Numarası</label>
                <input type="number" name="display_order" value="<?= (int) ($category['display_order'] ?? 1) ?>"
                       class="w-full sm:w-48 px-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 focus:outline-none focus:border-zinc-500">
                <p class="text-[11px] text-zinc-500 mt-1">Grupların CV sayfasında görüntülenme sırasını belirler (Küçükten büyüğe).</p>
            </div>
        </div>

        <div class="pt-4 border-t border-zinc-100 dark:border-zinc-800 flex items-center justify-end gap-2">
            <a href="<?= e(url('/admin/cv?tab=categories')) ?>" class="px-4 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700 text-xs font-semibold hover:bg-zinc-100 dark:hover:bg-zinc-800">
                Vazgeç
            </a>
            <button type="submit" class="px-5 py-2 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90">
                <i class="fa-solid fa-check mr-1.5"></i><?= $isEdit ? 'Kategoriyi Güncelle' : 'Kategoriyi Kaydet' ?>
            </button>
        </div>
    </form>
</div>
