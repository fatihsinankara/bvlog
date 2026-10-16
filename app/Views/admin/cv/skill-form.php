<?php
$isEdit = !empty($skill);
$actionUrl = $isEdit ? url('/admin/cv/skill/' . $skill['id'] . '/update') : url('/admin/cv/skill');
?>

<div class="max-w-2xl space-y-6" x-data="{ proficiency: <?= (int) ($skill['proficiency'] ?? 90) ?> }">
    <div class="flex items-center justify-between">
        <a href="<?= e(url('/admin/cv?tab=skills')) ?>" class="inline-flex items-center gap-1.5 text-xs font-semibold text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Teknik Yetenekler Listesine Dön</span>
        </a>
    </div>

    <form action="<?= e($actionUrl) ?>" method="POST" class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-5">
        <?= csrf_field() ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold mb-1.5">Kategori / Grup Adı *</label>
                <input type="text" name="category" required maxlength="100"
                       value="<?= e($skill['category'] ?? '') ?>"
                       placeholder="Örn: Backend & Veritabanı"
                       class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 focus:outline-none focus:border-zinc-500">
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1.5">Yetenek / Teknoloji Adı *</label>
                <input type="text" name="name" required maxlength="100"
                       value="<?= e($skill['name'] ?? '') ?>"
                       placeholder="Örn: PHP 8.4 / MySQL"
                       class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 focus:outline-none focus:border-zinc-500">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
            <div class="sm:col-span-2">
                <div class="flex items-center justify-between mb-1.5">
                    <label class="text-xs font-semibold">Yetkinlik Yüzdesi (10 - 100)</label>
                    <span class="text-xs font-mono font-bold" x-text="'%' + proficiency"></span>
                </div>
                <input type="range" name="proficiency" x-model="proficiency" min="10" max="100" step="5"
                       class="w-full accent-zinc-900 dark:accent-zinc-100 cursor-pointer">
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1.5">Sıralama Numarası</label>
                <input type="number" name="display_order" value="<?= (int) ($skill['display_order'] ?? 1) ?>"
                       class="w-full px-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
            </div>
        </div>

        <div class="pt-4 border-t border-zinc-100 dark:border-zinc-800 flex items-center justify-end gap-2">
            <a href="<?= e(url('/admin/cv?tab=skills')) ?>" class="px-4 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700 text-xs font-semibold hover:bg-zinc-100 dark:hover:bg-zinc-800">
                Vazgeç
            </a>
            <button type="submit" class="px-5 py-2 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90">
                <i class="fa-solid fa-check mr-1.5"></i><?= $isEdit ? 'Yeteneği Güncelle' : 'Yeteneği Kaydet' ?>
            </button>
        </div>
    </form>
</div>
