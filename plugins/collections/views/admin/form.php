<?php
/**
 * @var array|null $collection
 * @var array $assignedPosts
 * @var array $allPosts
 * @var string $pageTitle
 */

$isEdit = !empty($collection);
$actionUrl = $isEdit ? url('/admin/collections/' . $collection['id'] . '/update') : url('/admin/collections');
$currentColor = $collection['color'] ?? '#3b82f6';
$currentIcon = $collection['icon'] ?? 'fa-solid fa-layer-group';
$currentImage = $collection['cover_image'] ?? '';

// Hazır renk paleti
$colorPresets = [
    '#3b82f6' => 'Mavi',
    '#6366f1' => 'İndigo',
    '#8b5cf6' => 'Mor',
    '#ec4899' => 'Pembe',
    '#f43f5e' => 'Gül',
    '#ef4444' => 'Kırmızı',
    '#f97316' => 'Turuncu',
    '#eab308' => 'Sarı',
    '#10b981' => 'Zümrüt',
    '#06b6d4' => 'Camgöbeği',
    '#18181b' => 'Siyah/Koyu',
];

// Hazır ikonlar
$iconPresets = [
    'fa-solid fa-layer-group'    => 'Katmanlar',
    'fa-solid fa-book-open'      => 'Kitap / Rehber',
    'fa-solid fa-server'         => 'Sunucu',
    'fa-solid fa-terminal'       => 'Terminal',
    'fa-solid fa-code'           => 'Kod',
    'fa-solid fa-laptop-code'    => 'Geliştirici',
    'fa-solid fa-database'       => 'Veritabanı',
    'fa-solid fa-cube'           => 'Modül',
    'fa-solid fa-shield-halved'  => 'Güvenlik',
    'fa-solid fa-rocket'         => 'Hızlı Başlangıç',
    'fa-solid fa-graduation-cap' => 'Eğitim',
    'fa-solid fa-gears'          => 'Yapılandırma',
];

// Halihazırda bağlı yazı ID'leri ve sıraları
$assignedMap = [];
foreach ($assignedPosts as $ap) {
    $assignedMap[(int) $ap['id']] = (int) ($ap['part_order'] ?? 1);
}
?>

<div x-data="{
    title: <?= e(json_encode($collection['title'] ?? '')) ?>,
    slug: <?= e(json_encode($collection['slug'] ?? '')) ?>,
    slugEdited: <?= $isEdit ? 'true' : 'false' ?>,
    color: <?= e(json_encode($currentColor)) ?>,
    icon: <?= e(json_encode($currentIcon)) ?>,
    coverImage: <?= e(json_encode($currentImage)) ?>,
    postSearch: '',
    autoSlug() {
        if (!this.slugEdited) {
            this.slug = (typeof turkishSlugify === 'function') 
                ? turkishSlugify(this.title) 
                : this.title.toLowerCase().replace(/[^a-z0-9]/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '');
        }
    },
    previewLocalCover(event) {
        const file = event.target.files[0];
        if (file) {
            this.coverImage = URL.createObjectURL(file);
        }
    }
}" class="space-y-6">

    <!-- Başlık ve Geri Dönüş -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="<?= e(url('/admin/collections')) ?>" class="p-2 rounded-lg border border-zinc-200 dark:border-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-600 dark:text-zinc-400">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <h2 class="text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                    <?= $isEdit ? 'Koleksiyonu Düzenle' : 'Yeni Koleksiyon Oluştur' ?>
                </h2>
                <p class="text-xs text-zinc-500">
                    Ubuntu Kurulumu, Python Temelleri gibi seriler tanımlayın.
                </p>
            </div>
        </div>

        <?php if ($isEdit && $collection['status'] === 'published'): ?>
            <a href="<?= e(url('/collections/' . $collection['slug'])) ?>" target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg border border-zinc-200 dark:border-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-800">
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                <span>Sitede Gör</span>
            </a>
        <?php endif; ?>
    </div>

    <form action="<?= e($actionUrl) ?>" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <?= csrf_field() ?>

        <!-- Sol Sütun: Temel Bilgiler ve Yazı Listesi (8 Kolon) -->
        <div class="lg:col-span-8 space-y-6">

            <!-- Başlık, Slug ve Açıklama -->
            <div class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-4 shadow-sm">
                <div>
                    <label class="block text-xs font-semibold mb-1.5">Koleksiyon Başlığı *</label>
                    <input type="text" name="title" x-model="title" @input="autoSlug()" required
                           placeholder="Örn: Ubuntu 26.04 LAMP Kurulumu"
                           class="w-full px-3.5 py-2.5 text-base font-bold rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 focus:outline-none focus:border-zinc-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1">Kalıcı Bağlantı (Slug)</label>
                    <div class="flex items-center rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 overflow-hidden text-xs">
                        <span class="px-3 py-2 text-zinc-400 border-r border-zinc-200 dark:border-zinc-800 select-none font-mono">/collections/</span>
                        <input type="text" name="slug" x-model="slug" @input="slugEdited = true"
                               placeholder="otomatik-olusturulur"
                               class="w-full px-3 py-2 bg-transparent focus:outline-none font-mono">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1.5">Kısa Açıklama / Giriş</label>
                    <textarea name="description" rows="3"
                              placeholder="Bu koleksiyonun ne hakkında olduğunu 1-2 cümleyle özetleyin..."
                              class="w-full px-3.5 py-2 text-sm rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 focus:outline-none focus:border-zinc-500"><?= e($collection['description'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Koleksiyondaki Yazılar & Sıralama -->
            <div class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-4 shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-zinc-100 dark:border-zinc-800 pb-3">
                    <div>
                        <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                            <i class="fa-solid fa-list-ol text-blue-500"></i>
                            <span>Koleksiyondaki Yazılar ve Bölüm Sıralaması</span>
                        </h3>
                        <p class="text-xs text-zinc-500 mt-0.5">
                            Seriye dahil etmek istediğiniz yazıları işaretleyin ve bölüm sırasını belirleyin.
                        </p>
                    </div>

                    <!-- Hızlı Yazı Arama -->
                    <div class="w-full sm:w-48">
                        <input type="text" x-model="postSearch" placeholder="Yazılarda filtrele..."
                               class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 focus:outline-none">
                    </div>
                </div>

                <?php if (empty($allPosts)): ?>
                    <p class="text-xs text-zinc-500 italic py-4 text-center">Henüz yayınlanmış bir blog yazısı bulunmuyor.</p>
                <?php else: ?>
                    <div class="space-y-2 max-h-[420px] overflow-y-auto pr-1">
                        <?php foreach ($allPosts as $p): ?>
                            <?php 
                                $pid = (int) $p['id'];
                                $isAssigned = isset($assignedMap[$pid]);
                                $orderVal = $isAssigned ? $assignedMap[$pid] : 1;
                            ?>
                            <div class="flex items-center justify-between gap-3 p-3 rounded-lg border border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition-colors"
                                 x-show="!postSearch || <?= e(json_encode(mb_strtolower($p['title']))) ?>.includes(postSearch.toLowerCase())">
                                
                                <label class="flex items-center gap-3 cursor-pointer flex-1 min-w-0">
                                    <input type="checkbox" name="selected_posts[]" value="<?= $pid ?>" 
                                           <?= $isAssigned ? 'checked' : '' ?>
                                           class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500">
                                    <div class="truncate">
                                        <span class="text-xs font-semibold text-zinc-800 dark:text-zinc-200 block truncate">
                                            <?= e($p['title']) ?>
                                        </span>
                                        <span class="text-[11px] text-zinc-400">
                                            <?= format_date($p['published_at'] ?: $p['created_at']) ?> &middot; 
                                            <?= $p['status'] === 'published' ? 'Yayında' : 'Taslak' ?>
                                        </span>
                                    </div>
                                </label>

                                <div class="flex items-center gap-2 shrink-0">
                                    <span class="text-[11px] text-zinc-400">Bölüm:</span>
                                    <input type="number" name="post_orders[<?= $pid ?>]" value="<?= $orderVal ?>" min="1"
                                           class="w-16 px-2 py-1 text-xs text-center font-mono rounded border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-950 focus:outline-none">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sağ Sütun: Yayın Ayarı, Renk, İkon, Kapak Görseli (4 Kolon) -->
        <div class="lg:col-span-4 space-y-6">

            <!-- Yayınlama & Kaydetme Kutusu -->
            <div class="p-5 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-4 shadow-sm">
                <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-400">Yayın Ayarları</h3>

                <div>
                    <label class="block text-xs font-semibold mb-1.5">Durum</label>
                    <select name="status" class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 focus:outline-none">
                        <option value="published" <?= ($collection['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>Yayında (Herkese Açık)</option>
                        <option value="draft" <?= ($collection['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Taslak (Gizli)</option>
                    </select>
                </div>

                <div class="pt-3 border-t border-zinc-100 dark:border-zinc-800 flex items-center gap-2">
                    <button type="submit" class="flex-1 py-2.5 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90 transition-opacity">
                        <i class="fa-solid fa-floppy-disk mr-1.5"></i><?= $isEdit ? 'Değişiklikleri Kaydet' : 'Koleksiyonu Oluştur' ?>
                    </button>
                    <a href="<?= e(url('/admin/collections')) ?>" class="px-3.5 py-2.5 rounded-lg border border-zinc-200 dark:border-zinc-700 text-xs font-medium hover:bg-zinc-100 dark:hover:bg-zinc-800">
                        İptal
                    </a>
                </div>
            </div>

            <!-- Simge (İkon) Seçici -->
            <div class="p-5 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-400">Koleksiyon Simgesi</h3>
                    <div class="w-7 h-7 rounded-lg border border-zinc-200 dark:border-zinc-700 flex items-center justify-center text-sm"
                         :style="'color: ' + color">
                        <i :class="icon"></i>
                    </div>
                </div>

                <!-- Hazır İkon Butonları -->
                <div class="grid grid-cols-4 gap-2">
                    <?php foreach ($iconPresets as $iconClass => $iconLabel): ?>
                        <button type="button" 
                                @click="icon = '<?= $iconClass ?>'" 
                                :class="icon === '<?= $iconClass ?>' ? 'ring-2 ring-blue-500 bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 border-blue-500' : 'border-zinc-200 dark:border-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-600 dark:text-zinc-400'"
                                class="h-9 rounded-lg border flex items-center justify-center text-sm transition-all"
                                title="<?= e($iconLabel) ?>">
                            <i class="<?= $iconClass ?>"></i>
                        </button>
                    <?php endforeach; ?>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-zinc-500 mb-1">Özel FontAwesome Sınıfı</label>
                    <input type="text" name="icon" x-model="icon" placeholder="fa-solid fa-layer-group"
                           class="w-full px-3 py-1.5 text-xs font-mono rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 focus:outline-none">
                </div>
            </div>

            <!-- Tema Rengi Seçici -->
            <div class="p-5 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-400">Vurgu Rengi</h3>
                    <div class="w-6 h-6 rounded-full border border-black/10 shadow-sm" :style="'background-color: ' + color"></div>
                </div>

                <!-- Hazır Renk Paleti -->
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($colorPresets as $hex => $label): ?>
                        <button type="button" 
                                @click="color = '<?= $hex ?>'" 
                                class="w-6 h-6 rounded-full border border-black/10 transition-transform hover:scale-110 shrink-0"
                                :class="color.toLowerCase() === '<?= strtolower($hex) ?>' ? 'ring-2 ring-offset-2 ring-zinc-500 scale-110' : ''"
                                style="background-color: <?= $hex ?>;" 
                                title="<?= e($label) ?>">
                        </button>
                    <?php endforeach; ?>
                </div>

                <div class="flex items-center gap-2">
                    <input type="color" x-model="color" class="w-8 h-8 rounded border-0 cursor-pointer bg-transparent">
                    <input type="text" name="color" x-model="color" maxlength="7"
                           class="flex-1 px-3 py-1.5 text-xs font-mono rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 focus:outline-none">
                </div>
            </div>

            <!-- İsteğe Bağlı Kapak Görseli -->
            <div class="p-5 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-400">Kapak Görseli (İsteğe Bağlı)</h3>
                </div>

                <p class="text-[11px] text-zinc-500">
                    Görsel eklerseniz kartlarda ve sayfa başında şık bir afiş gösterilir; boş bırakırsanız seçilen renkle minimalist bir tema uygulanır.
                </p>

                <input type="hidden" name="cover_image" x-model="coverImage">

                <template x-if="coverImage">
                    <div class="relative rounded-lg overflow-hidden border border-zinc-200 dark:border-zinc-800 bg-zinc-100 dark:bg-zinc-950 aspect-[16/9]">
                        <img :src="coverImage" alt="Önizleme" class="w-full h-full object-cover">
                        <button type="button" @click="coverImage = ''"
                                class="absolute top-2 right-2 w-7 h-7 rounded-full bg-zinc-900/80 text-white flex items-center justify-center text-xs hover:bg-red-600 transition-colors"
                                title="Görseli Kaldır">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </template>

                <div class="grid grid-cols-2 gap-2">
                    <button type="button"
                            @click="$dispatch('open-media-picker', { mode: 'featured', onSelect: (item) => { coverImage = item.filepath; } })"
                            class="py-2 px-3 rounded-lg border border-zinc-200 dark:border-zinc-800 text-xs font-semibold hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                        <i class="fa-regular fa-images mr-1"></i>Galeriden Seç
                    </button>
                    <label class="py-2 px-3 rounded-lg border border-zinc-200 dark:border-zinc-800 text-xs font-semibold hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors text-center cursor-pointer">
                        <i class="fa-solid fa-upload mr-1"></i>Yeni Yükle
                        <input type="file" name="cover_image_file" accept="image/*" class="hidden" @change="previewLocalCover($event)">
                    </label>
                </div>
            </div>

        </div>
    </form>
</div>
