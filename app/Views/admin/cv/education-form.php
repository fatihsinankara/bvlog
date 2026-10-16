<?php
$isEdit = !empty($education);
$actionUrl = $isEdit ? url('/admin/cv/education/' . $education['id'] . '/update') : url('/admin/cv/education');
?>

<div class="max-w-3xl space-y-6" x-data="{ isCurrent: <?= !empty($education['is_current']) ? 'true' : 'false' ?> }">
    <div class="flex items-center justify-between">
        <a href="<?= e(url('/admin/cv?tab=education')) ?>" class="inline-flex items-center gap-1.5 text-xs font-semibold text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Eğitim Geçmişi Listesine Dön</span>
        </a>
    </div>

    <form action="<?= e($actionUrl) ?>" method="POST" class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-5">
        <?= csrf_field() ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold mb-1.5">Üniversite / Okul Adı *</label>
                <input type="text" name="institution" required maxlength="150"
                       value="<?= e($education['institution'] ?? '') ?>"
                       placeholder="Örn: İstanbul Teknik Üniversitesi"
                       class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 focus:outline-none focus:border-zinc-500">
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1.5">Derece (Lisans, Yüksek Lisans vb.) *</label>
                <input type="text" name="degree" required maxlength="150"
                       value="<?= e($education['degree'] ?? '') ?>"
                       placeholder="Örn: Lisans Derecesi"
                       class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 focus:outline-none focus:border-zinc-500">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-semibold mb-1.5">Bölüm / Alan</label>
                <input type="text" name="field_of_study" maxlength="150"
                       value="<?= e($education['field_of_study'] ?? '') ?>"
                       placeholder="Örn: Bilgisayar Mühendisliği"
                       class="w-full px-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 focus:outline-none focus:border-zinc-500">
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1.5">Başlangıç Yılı *</label>
                <input type="text" name="start_date" required maxlength="50"
                       value="<?= e($education['start_date'] ?? '') ?>"
                       placeholder="Örn: 2016"
                       class="w-full px-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 focus:outline-none focus:border-zinc-500">
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1.5">Mezuniyet Yılı</label>
                <input type="text" name="end_date" maxlength="50"
                       :disabled="isCurrent"
                       value="<?= e($education['end_date'] ?? '') ?>"
                       placeholder="Örn: 2020"
                       class="w-full px-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 disabled:opacity-40 focus:outline-none focus:border-zinc-500">
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-4 pt-1">
            <label class="inline-flex items-center gap-2 text-xs font-medium cursor-pointer">
                <input type="checkbox" name="is_current" value="1" x-model="isCurrent" class="rounded border-zinc-300">
                <span>Eğitimim halen devam ediyor</span>
            </label>

            <div class="flex items-center gap-2">
                <label class="text-xs font-semibold">Sıralama Numarası:</label>
                <input type="number" name="display_order" value="<?= (int) ($education['display_order'] ?? 1) ?>"
                       class="w-20 px-3 py-1.5 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold mb-1.5">Açıklama / Notlar</label>
            <textarea name="description" rows="3" maxlength="2000"
                      placeholder="Onur derecesi, tez konusu veya öne çıkan çalışmalar..."
                      class="w-full px-3 py-2 text-xs leading-relaxed rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 focus:outline-none focus:border-zinc-500"><?= e($education['description'] ?? '') ?></textarea>
        </div>

        <div class="pt-4 border-t border-zinc-100 dark:border-zinc-800 flex items-center justify-end gap-2">
            <a href="<?= e(url('/admin/cv?tab=education')) ?>" class="px-4 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700 text-xs font-semibold hover:bg-zinc-100 dark:hover:bg-zinc-800">
                Vazgeç
            </a>
            <button type="submit" class="px-5 py-2 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90">
                <i class="fa-solid fa-check mr-1.5"></i><?= $isEdit ? 'Eğitimi Güncelle' : 'Eğitimi Kaydet' ?>
            </button>
        </div>
    </form>
</div>
