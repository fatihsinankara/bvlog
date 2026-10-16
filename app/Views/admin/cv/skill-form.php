<?php
$isEdit = !empty($skill);
$actionUrl = $isEdit ? url('/admin/cv/skill/' . $skill['id'] . '/update') : url('/admin/cv/skill');

$selectedCategoryId = (int) ($skill['category_id'] ?? 0);
if ($selectedCategoryId === 0 && !empty($skill['category'])) {
    foreach ($categories as $cat) {
        if ($cat['name'] === $skill['category']) {
            $selectedCategoryId = (int) $cat['id'];
            break;
        }
    }
}
?>

<div class="max-w-2xl space-y-6"
     x-data="{
         proficiency: <?= (int) ($skill['proficiency'] ?? 90) ?>,
         categories: <?= e(json_encode(array_values(array_map(fn($c) => ['id' => (int)$c['id'], 'name' => $c['name']], $categories)))) ?>,
         selectedCategoryId: <?= (int) $selectedCategoryId ?>,
         showQuickAdd: false,
         newCategoryName: '',
         newCategoryOrder: 1,
         quickAddLoading: false,
         quickAddError: '',
         async createQuickCategory() {
             const name = this.newCategoryName.trim();
             if (!name) {
                 this.quickAddError = 'Lütfen kategori adı girin.';
                 return;
             }
             this.quickAddLoading = true;
             this.quickAddError = '';
             try {
                 const token = document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || '';
                 const formData = new FormData();
                 formData.append('name', name);
                 formData.append('display_order', this.newCategoryOrder);
                 formData.append('_csrf_token', token);

                 const res = await fetch('<?= e(url('/admin/cv/category/quick')) ?>', {
                     method: 'POST',
                     headers: {
                         'X-Requested-With': 'XMLHttpRequest',
                         'X-CSRF-TOKEN': token
                     },
                     body: formData
                 });
                 const data = await res.json();
                 if (data.success && data.category) {
                     this.categories.push({ id: data.category.id, name: data.category.name });
                     this.selectedCategoryId = data.category.id;
                     this.newCategoryName = '';
                     this.showQuickAdd = false;
                 } else {
                     this.quickAddError = data.message || 'Kategori eklenirken hata oluştu.';
                 }
             } catch (e) {
                 this.quickAddError = 'Bir ağ hatası oluştu.';
             } finally {
                 this.quickAddLoading = false;
             }
         }
     }">

    <div class="flex items-center justify-between">
        <a href="<?= e(url('/admin/cv?tab=skills')) ?>" class="inline-flex items-center gap-1.5 text-xs font-semibold text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Teknik Yetenekler Listesine Dön</span>
        </a>
    </div>

    <!-- Kategori Yoksa Uyarı -->
    <template x-if="categories.length === 0">
        <div class="p-4 rounded-xl border border-amber-300 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/40 text-amber-900 dark:text-amber-200 text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation text-amber-600"></i>
                <span>Henüz tanımlı bir yetenek kategorisi bulunmuyor. Yetenek eklemeden önce en az bir grup oluşturmalısınız.</span>
            </div>
            <button type="button" @click="showQuickAdd = true" class="px-3 py-1.5 rounded bg-amber-600 text-white font-semibold hover:bg-amber-700 transition-colors shrink-0">
                <i class="fa-solid fa-plus mr-1"></i>Hızlı Kategori Oluştur
            </button>
        </div>
    </template>

    <form action="<?= e($actionUrl) ?>" method="POST" class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-5">
        <?= csrf_field() ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Dinamik Kategori / Grup Seçimi -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-xs font-semibold">Kategori / Grup *</label>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="showQuickAdd = !showQuickAdd"
                                class="text-[11px] font-semibold text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100 flex items-center gap-1">
                            <i class="fa-solid" :class="showQuickAdd ? 'fa-xmark' : 'fa-plus'"></i>
                            <span x-text="showQuickAdd ? 'Kapat' : 'Hızlı Grup Ekle'"></span>
                        </button>
                        <span class="text-zinc-300 dark:text-zinc-700">|</span>
                        <a href="<?= e(url('/admin/cv?tab=categories')) ?>" target="_blank"
                           class="text-[11px] text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300" title="Grupları Yönet">
                            <i class="fa-solid fa-gear"></i>
                        </a>
                    </div>
                </div>

                <!-- Hızlı Kategori Ekleme Kutusu -->
                <div x-show="showQuickAdd" x-cloak
                     class="mb-2.5 p-3 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-950 space-y-2">
                    <div class="text-[11px] font-bold text-zinc-700 dark:text-zinc-300 flex items-center justify-between">
                        <span>Yeni Kategori Oluştur</span>
                        <span x-show="quickAddError" class="text-red-500 font-normal" x-text="quickAddError"></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="text" x-model="newCategoryName" placeholder="Grup Adı (Örn: Mobil Geliştirme)"
                               @keydown.enter.prevent="createQuickCategory()"
                               class="flex-1 px-2.5 py-1.5 text-xs rounded border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 focus:outline-none focus:border-zinc-500">
                        <input type="number" x-model="newCategoryOrder" title="Sıra Numarası"
                               class="w-16 px-2 py-1.5 text-xs rounded border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 focus:outline-none text-center">
                        <button type="button" @click="createQuickCategory()" :disabled="quickAddLoading"
                                class="px-3 py-1.5 text-xs rounded bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 font-semibold hover:opacity-90 disabled:opacity-50 shrink-0">
                            <i x-show="quickAddLoading" class="fa-solid fa-spinner fa-spin mr-1"></i>
                            <span>Ekle</span>
                        </button>
                    </div>
                </div>

                <select name="category_id" x-model="selectedCategoryId" required
                        class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 focus:outline-none focus:border-zinc-500">
                    <option value="">-- Kategori / Grup Seçin --</option>
                    <template x-for="cat in categories" :key="cat.id">
                        <option :value="cat.id" :selected="cat.id == selectedCategoryId" x-text="cat.name"></option>
                    </template>
                </select>
            </div>

            <!-- Yetenek / Teknoloji Adı -->
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
