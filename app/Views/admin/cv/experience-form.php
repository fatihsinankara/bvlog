<?php
$isEdit = !empty($experience);
$actionUrl = $isEdit ? url('/admin/cv/experience/' . $experience['id'] . '/update') : url('/admin/cv/experience');
?>

<div class="max-w-3xl space-y-6" x-data="{ isCurrent: <?= !empty($experience['is_current']) ? 'true' : 'false' ?> }">
    <div class="flex items-center justify-between">
        <a href="<?= e(url('/admin/cv?tab=experience')) ?>" class="inline-flex items-center gap-1.5 text-xs font-semibold text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100">
            <i class="fa-solid fa-arrow-left"></i>
            <span>İş Deneyimleri Listesine Dön</span>
        </a>
    </div>

    <form action="<?= e($actionUrl) ?>" method="POST" class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-5">
        <?= csrf_field() ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold mb-1.5">Şirket / Kurum Adı *</label>
                <input type="text" name="company" required maxlength="150"
                       value="<?= e($experience['company'] ?? '') ?>"
                       placeholder="Örn: EnvKit Teknoloji"
                       class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 focus:outline-none focus:border-zinc-500">
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1.5">Pozisyon / Unvan *</label>
                <input type="text" name="role" required maxlength="150"
                       value="<?= e($experience['role'] ?? '') ?>"
                       placeholder="Örn: Kıdemli Yazılım Mimarı"
                       class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 focus:outline-none focus:border-zinc-500">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-semibold mb-1.5">Konum (Şehir / Uzaktan)</label>
                <input type="text" name="location" maxlength="100"
                       value="<?= e($experience['location'] ?? '') ?>"
                       placeholder="İstanbul / Uzaktan"
                       class="w-full px-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 focus:outline-none focus:border-zinc-500">
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1.5">Başlangıç Tarihi *</label>
                <input type="text" name="start_date" required maxlength="50"
                       value="<?= e($experience['start_date'] ?? '') ?>"
                       placeholder="Örn: 2022"
                       class="w-full px-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 focus:outline-none focus:border-zinc-500">
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1.5">Bitiş Tarihi</label>
                <input type="text" name="end_date" maxlength="50"
                       :disabled="isCurrent"
                       value="<?= e($experience['end_date'] ?? '') ?>"
                       placeholder="Örn: 2025"
                       class="w-full px-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 disabled:opacity-40 focus:outline-none focus:border-zinc-500">
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-4 pt-1">
            <label class="inline-flex items-center gap-3 text-xs font-medium cursor-pointer">
                <input type="checkbox" name="is_current" value="1" role="switch" x-model="isCurrent" class="ios-switch shrink-0">
                <span class="text-zinc-800 dark:text-zinc-200">Halen burada çalışıyorum (Devam Ediyor)</span>
            </label>

            <div class="flex items-center gap-2">
                <label class="text-xs font-semibold">Sıralama Numarası:</label>
                <input type="number" name="display_order" value="<?= (int) ($experience['display_order'] ?? 1) ?>"
                       class="w-20 px-3 py-1.5 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold mb-1.5">Görev ve Başarı Açıklaması</label>
            <textarea name="description" rows="4" maxlength="2000"
                      placeholder="Üstlendiğiniz sorumluluklar, mimari kararlar ve kazanımlar..."
                      class="w-full px-3 py-2 text-xs leading-relaxed rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 focus:outline-none focus:border-zinc-500"><?= e($experience['description'] ?? '') ?></textarea>
        </div>

        <div class="pt-4 border-t border-zinc-100 dark:border-zinc-800 flex items-center justify-end gap-2">
            <a href="<?= e(url('/admin/cv?tab=experience')) ?>" class="px-4 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700 text-xs font-semibold hover:bg-zinc-100 dark:hover:bg-zinc-800">
                Vazgeç
            </a>
            <button type="submit" class="px-5 py-2 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90">
                <i class="fa-solid fa-check mr-1.5"></i><?= $isEdit ? 'Deneyimi Güncelle' : 'Deneyimi Kaydet' ?>
            </button>
        </div>
    </form>
</div>
