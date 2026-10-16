<?php
$isEdit = !empty($category);
$actionUrl = $isEdit ? url('/admin/categories/' . $category['id'] . '/update') : url('/admin/categories');

$presetIcons = [
    'fa-solid fa-folder',
    'fa-solid fa-code',
    'fa-solid fa-cubes',
    'fa-solid fa-shield-halved',
    'fa-solid fa-bolt',
    'fa-solid fa-terminal',
    'fa-solid fa-server',
    'fa-solid fa-database',
    'fa-solid fa-feather',
    'fa-solid fa-book-open',
    'fa-solid fa-microchip',
    'fa-solid fa-globe',
];

$presetColors = [
    '#18181b' => 'Antrasit',
    '#2563eb' => 'Mavi',
    '#16a34a' => 'Yeşil',
    '#d97706' => 'Kehribar',
    '#dc2626' => 'Kırmızı',
    '#9333ea' => 'Mor',
    '#0891b2' => 'Camgöbeği',
    '#52525b' => 'Çelik Gri',
];
?>

<div class="max-w-3xl space-y-6"
     x-data="{
         catName: <?= e(json_encode($category['name'] ?? '')) ?>,
         catIcon: <?= e(json_encode($category['icon'] ?? 'fa-solid fa-code')) ?>,
         catColor: <?= e(json_encode($category['color'] ?? '#18181b')) ?>,
         catImage: <?= e(json_encode($category['image'] ?? '')) ?>
     }">

    <div class="flex items-center justify-between">
        <a href="<?= e(url('/admin/categories')) ?>" class="inline-flex items-center gap-1.5 text-xs font-semibold text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Kategoriler Listesine Dön</span>
        </a>
    </div>

    <form action="<?= e($actionUrl) ?>" method="POST" class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-5">
        <?= csrf_field() ?>

        <!-- Canlı Rozet Önizlemesi -->
        <div class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 flex items-center justify-between">
            <div class="space-y-0.5">
                <div class="text-xs font-bold">Canlı Rozet Önizlemesi</div>
                <div class="text-[11px] text-zinc-500">Yazı kartlarında ve arşiv sayfalarında bu şekilde görünür.</div>
            </div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium rounded border bg-white dark:bg-zinc-900"
                  :style="'color: ' + catColor + '; border-color: ' + catColor">
                <i :class="catIcon" class="text-[11px]"></i>
                <span x-text="catName || 'Kategori Adı'"></span>
            </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold mb-1.5">Kategori Adı *</label>
                <input type="text" name="name" x-model="catName" required maxlength="100"
                       placeholder="Örn: Yazılım Mimarisi"
                       class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 focus:outline-none focus:border-zinc-500">
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1.5">Kalıcı Bağlantı (Slug)</label>
                <input type="text" name="slug" value="<?= e($category['slug'] ?? '') ?>"
                       placeholder="Boş bırakılırsa otomatik üretilir"
                       class="w-full px-3 py-2 text-xs font-mono rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 focus:outline-none focus:border-zinc-500">
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold mb-1.5">Kategori Açıklaması</label>
            <textarea name="description" rows="2" maxlength="255"
                      placeholder="Bu kategoride yer alan içerikler hakkında kısa açıklama..."
                      class="w-full px-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 focus:outline-none focus:border-zinc-500"><?= e($category['description'] ?? '') ?></textarea>
        </div>

        <!-- İkon Seçici -->
        <div>
            <label class="block text-xs font-semibold mb-1.5">Kategori İkonu</label>
            <input type="text" name="icon" x-model="catIcon"
                   placeholder="fa-solid fa-code"
                   class="w-full px-3 py-2 text-xs font-mono rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 mb-2.5 focus:outline-none">
            <div class="grid grid-cols-6 sm:grid-cols-12 gap-1.5">
                <?php foreach ($presetIcons as $ic): ?>
                    <button type="button"
                            @click="catIcon = '<?= e($ic) ?>'"
                            :class="catIcon === '<?= e($ic) ?>' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 border-zinc-900' : 'border-zinc-200 dark:border-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-800'"
                            class="h-9 rounded-lg border flex items-center justify-center text-xs transition-colors">
                        <i class="<?= e($ic) ?>"></i>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Renk Seçici -->
        <div>
            <label class="block text-xs font-semibold mb-1.5">Kategori Rengi</label>
            <div class="flex items-center gap-2 mb-2.5">
                <input type="color" x-model="catColor" class="w-9 h-9 rounded border border-zinc-200 cursor-pointer p-0.5 bg-white">
                <input type="text" name="color" x-model="catColor" maxlength="7"
                       class="w-40 px-3 py-2 text-xs font-mono rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 focus:outline-none">
            </div>
            <div class="flex flex-wrap gap-1.5">
                <?php foreach ($presetColors as $hex => $label): ?>
                    <button type="button"
                            @click="catColor = '<?= e($hex) ?>'"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-zinc-200 dark:border-zinc-800 text-xs hover:bg-zinc-50 dark:hover:bg-zinc-800">
                        <span class="w-3 h-3 rounded-full" style="background-color: <?= e($hex) ?>;"></span>
                        <span><?= e($label) ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Kategori Kapak Görseli -->
        <div>
            <label class="block text-xs font-semibold mb-1.5">Kategori Kapak Görseli</label>
            <input type="hidden" name="image" x-model="catImage">
            <template x-if="catImage">
                <div class="relative mb-2.5 rounded-lg overflow-hidden border border-zinc-200 dark:border-zinc-800 h-36 bg-zinc-100">
                    <img :src="catImage" alt="" class="w-full h-full object-cover">
                    <button type="button" @click="catImage = ''" class="absolute top-2 right-2 w-7 h-7 rounded-full bg-zinc-900/80 text-white text-xs">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </template>
            <button type="button"
                    @click="$dispatch('open-media-picker', { mode: 'featured', onSelect: (item) => { catImage = item.filepath; } })"
                    class="w-full py-2.5 px-3 rounded-lg border border-zinc-200 dark:border-zinc-800 text-xs font-semibold hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                <i class="fa-regular fa-images mr-1.5"></i>Medya Galerisinden Görsel Seç / Yükle
            </button>
        </div>

        <div class="pt-4 border-t border-zinc-100 dark:border-zinc-800 flex items-center justify-end gap-2">
            <a href="<?= e(url('/admin/categories')) ?>" class="px-4 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700 text-xs font-semibold hover:bg-zinc-100 dark:hover:bg-zinc-800">
                Vazgeç
            </a>
            <button type="submit" class="px-5 py-2 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90">
                <i class="fa-solid fa-check mr-1.5"></i><?= $isEdit ? 'Kategoriyi Güncelle' : 'Kategoriyi Kaydet' ?>
            </button>
        </div>
    </form>
</div>
