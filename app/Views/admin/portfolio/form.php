<?php
$isEdit = !empty($project);
$actionUrl = $isEdit ? url('/admin/portfolio/' . $project['id'] . '/update') : url('/admin/portfolio');
?>

<form action="<?= e($actionUrl) ?>" method="POST" enctype="multipart/form-data"
      x-data="{ thumbnail: <?= e(json_encode($project['thumbnail'] ?? '')) ?> }"
      class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
    <?= csrf_field() ?>

    <div class="lg:col-span-8 p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-4">
        <div>
            <label class="block text-xs font-semibold mb-1.5">Proje Başlığı *</label>
            <input type="text" name="title" value="<?= e($project['title'] ?? '') ?>" required maxlength="200"
                   class="w-full px-3.5 py-2 text-sm font-bold rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 focus:outline-none">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold mb-1.5">Slug (Kalıcı Bağlantı)</label>
                <input type="text" name="slug" value="<?= e($project['slug'] ?? '') ?>"
                       placeholder="otomatik-olusturulur"
                       class="w-full px-3 py-2 text-xs font-mono rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1.5">Kullanılan Teknolojiler (Virgülle ayırın)</label>
                <input type="text" name="tech_stack" value="<?= e($project['tech_stack'] ?? '') ?>"
                       placeholder="PHP 8, MySQL, Tailwind CSS, Alpine.js"
                       class="w-full px-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold mb-1.5">Canlı Demo Bağlantısı</label>
                <input type="url" name="demo_url" value="<?= e($project['demo_url'] ?? '') ?>"
                       placeholder="https://..."
                       class="w-full px-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1.5">GitHub / Kaynak Kod Bağlantısı</label>
                <input type="url" name="github_url" value="<?= e($project['github_url'] ?? '') ?>"
                       placeholder="https://github.com/..."
                       class="w-full px-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold mb-1.5">Kısa Özet</label>
            <textarea name="summary" rows="2" maxlength="400"
                      class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950"><?= e($project['summary'] ?? '') ?></textarea>
        </div>

        <div>
            <label class="block text-xs font-semibold mb-1.5">Detaylı Proje Açıklaması</label>
            <textarea name="description" rows="8"
                      class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950"><?= e($project['description'] ?? '') ?></textarea>
        </div>
    </div>

    <div class="lg:col-span-4 space-y-6">
        <div class="p-5 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-4">
            <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-400">Yayın & Sıralama</h3>

            <div>
                <label class="block text-xs font-semibold mb-1">Durum</label>
                <select name="status" class="w-full px-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
                    <option value="published" <?= ($project['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>Yayında</option>
                    <option value="draft" <?= ($project['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Taslak</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1">Sıralama Numarası</label>
                <input type="number" name="display_order" value="<?= (int) ($project['display_order'] ?? 0) ?>"
                       class="w-full px-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
            </div>

            <label class="flex items-center justify-between gap-3 p-3 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-950/40 cursor-pointer hover:bg-zinc-100/60 dark:hover:bg-zinc-800/40 transition-colors">
                <div class="text-xs font-medium">
                    <span class="block font-bold text-zinc-900 dark:text-zinc-100">Öne Çıkan Proje</span>
                    <span class="text-zinc-500 text-[11px]">Projeyi anasayfada öne çıkar</span>
                </div>
                <input type="checkbox" name="is_featured" value="1" role="switch" <?= !empty($project['is_featured']) ? 'checked' : '' ?> class="ios-switch shrink-0">
            </label>

            <div class="pt-3 border-t border-zinc-100 dark:border-zinc-800 flex items-center gap-2">
                <button type="submit" class="flex-1 py-2.5 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90">
                    <?= $isEdit ? 'Güncelle' : 'Projeyi Kaydet' ?>
                </button>
                <a href="<?= e(url('/admin/portfolio')) ?>" class="px-3.5 py-2.5 rounded-lg border border-zinc-200 dark:border-zinc-700 text-xs font-medium">İptal</a>
            </div>
        </div>

        <div class="p-5 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-400">Proje Kapak Görseli</h3>
            <input type="hidden" name="thumbnail" x-model="thumbnail">

            <template x-if="thumbnail">
                <div class="relative rounded-lg overflow-hidden border border-zinc-200 dark:border-zinc-800 aspect-[16/9]">
                    <img :src="thumbnail" alt="" class="w-full h-full object-cover">
                    <button type="button" @click="thumbnail = ''" class="absolute top-2 right-2 w-6 h-6 rounded-full bg-zinc-900/80 text-white text-xs">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </template>

            <div class="grid grid-cols-2 gap-2">
                <button type="button"
                        @click="$dispatch('open-media-picker', { mode: 'featured', onSelect: (item) => { thumbnail = item.filepath; } })"
                        class="py-2 px-3 rounded-lg border border-zinc-200 dark:border-zinc-800 text-xs font-semibold hover:bg-zinc-50 dark:hover:bg-zinc-800">
                    <i class="fa-regular fa-images mr-1"></i>Galeriden Seç
                </button>
                <label class="py-2 px-3 rounded-lg border border-zinc-200 dark:border-zinc-800 text-xs font-semibold hover:bg-zinc-50 dark:hover:bg-zinc-800 text-center cursor-pointer">
                    <i class="fa-solid fa-upload mr-1"></i>Yeni Yükle
                    <input type="file" name="thumbnail_file" accept="image/*" class="hidden">
                </label>
            </div>
        </div>
    </div>
</form>
