<div class="space-y-6" x-data="{ activeItem: null, copied: false }">
    <!-- Üst Yükleme & Arama Alanı -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-center">
        <form action="<?= e(url('/admin/media/upload')) ?>" method="POST" enctype="multipart/form-data"
              class="lg:col-span-7 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 flex flex-wrap items-center justify-between gap-3">
            <?= csrf_field() ?>
            <div class="flex items-center gap-3">
                <input type="file" name="file" accept="image/*" required
                       class="text-xs file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-zinc-900 file:text-white dark:file:bg-zinc-100 dark:file:text-zinc-900 cursor-pointer">
            </div>
            <div class="flex items-center gap-2">
                <select name="mode" class="px-2.5 py-1.5 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
                    <option value="inline">İçerik Görseli</option>
                    <option value="featured">Kapak Görseli</option>
                </select>
                <button type="submit" class="px-4 py-1.5 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90">
                    <i class="fa-solid fa-cloud-arrow-up mr-1"></i>Yükle
                </button>
            </div>
        </form>

        <form action="<?= e(url('/admin/media')) ?>" method="GET" class="lg:col-span-5 flex items-center gap-2">
            <div class="relative flex-1">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 text-xs"></i>
                <input type="text" name="q" value="<?= e($query ?? '') ?>"
                       placeholder="Görsel başlığı, alt metni veya dosya adı..."
                       class="w-full pl-8 pr-3 py-2.5 text-xs rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 focus:outline-none">
            </div>
            <button type="submit" class="px-4 py-2.5 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-xs font-semibold hover:bg-zinc-50">
                Ara
            </button>
        </form>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <!-- Sol: Görsel Izgarası -->
        <div :class="activeItem ? 'lg:col-span-8' : 'lg:col-span-12'" class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-5">
            <?php if (empty($items)): ?>
                <div class="py-16 text-center space-y-2">
                    <i class="fa-regular fa-images text-3xl text-zinc-400"></i>
                    <p class="text-sm text-zinc-500">Medya kütüphanesinde henüz görsel bulunmuyor.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-5 gap-3.5">
                    <?php foreach ($items as $item): ?>
                        <div @click="activeItem = <?= e(json_encode($item)) ?>; copied = false"
                             :class="activeItem && activeItem.id == <?= (int) $item['id'] ?> ? 'ring-2 ring-zinc-900 dark:ring-zinc-100 border-zinc-900' : 'border-zinc-200 dark:border-zinc-800 hover:border-zinc-400'"
                             class="group relative aspect-square rounded-lg border overflow-hidden cursor-pointer bg-zinc-100 dark:bg-zinc-950">
                            <img src="<?= e(url($item['filepath'])) ?>"
                                 alt="<?= e($item['alt_text'] ?: $item['title']) ?>"
                                 loading="lazy"
                                 class="w-full h-full object-cover">
                            <div class="absolute inset-x-0 bottom-0 bg-zinc-950/75 text-white px-2 py-1 text-[10px] flex items-center justify-between">
                                <span class="truncate"><?= e($item['title'] ?: $item['filename']) ?></span>
                                <span class="opacity-75 shrink-0 ml-1"><?= (int) $item['width'] ?>p</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sağ: Seçili Görsel Detay ve Düzenleme Paneli -->
        <template x-if="activeItem">
            <div class="lg:col-span-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-5 space-y-4 sticky top-20">
                <div class="flex items-center justify-between border-b border-zinc-100 dark:border-zinc-800 pb-3">
                    <h3 class="text-sm font-bold">Görsel Detayları</h3>
                    <button type="button" @click="activeItem = null" class="text-zinc-400 hover:text-zinc-700">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="rounded-lg overflow-hidden border border-zinc-200 dark:border-zinc-800 bg-zinc-100 dark:bg-zinc-950 max-h-48 flex items-center justify-center">
                    <img :src="activeItem.filepath" alt="" class="max-h-48 w-auto object-contain">
                </div>

                <div class="text-xs space-y-1 text-zinc-500 bg-zinc-50 dark:bg-zinc-950 p-3 rounded-lg border border-zinc-100 dark:border-zinc-800">
                    <div><strong>Dosya:</strong> <span x-text="activeItem.filename"></span></div>
                    <div><strong>Çözünürlük:</strong> <span x-text="activeItem.width + ' x ' + activeItem.height + ' piksel'"></span></div>
                    <div><strong>Format:</strong> <span x-text="activeItem.mime_type"></span> (<span x-text="Math.round(activeItem.filesize / 1024) + ' KB'"></span>)</div>
                </div>

                <!-- URL Kopyala -->
                <div>
                    <label class="block text-xs font-semibold mb-1">Görsel Bağlantısı (URL)</label>
                    <div class="flex items-center gap-1.5">
                        <input type="text" readonly :value="activeItem.filepath" class="flex-1 px-2.5 py-1.5 text-xs font-mono rounded border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
                        <button type="button"
                                @click="navigator.clipboard.writeText(activeItem.filepath); copied = true; setTimeout(() => copied = false, 2000)"
                                class="px-2.5 py-1.5 rounded border border-zinc-200 dark:border-zinc-700 text-xs font-semibold hover:bg-zinc-100 dark:hover:bg-zinc-800">
                            <span x-text="copied ? 'Kopyalandı!' : 'Kopyala'"></span>
                        </button>
                    </div>
                </div>

                <!-- Başlık ve Alt Metin Güncelleme -->
                <form :action="'<?= e(url('/admin/media/')) ?>' + activeItem.id + '/update'" method="POST" class="space-y-3 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                    <?= csrf_field() ?>
                    <div>
                        <label class="block text-xs font-semibold mb-1">Görsel Başlığı</label>
                        <input type="text" name="title" x-model="activeItem.title"
                               class="w-full px-3 py-1.5 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">Alternatif Metin (SEO Alt Text)</label>
                        <input type="text" name="alt_text" x-model="activeItem.alt_text"
                               class="w-full px-3 py-1.5 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
                    </div>
                    <button type="submit" class="w-full py-2 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90">
                        Bilgileri Güncelle
                    </button>
                </form>

                <!-- Görseli Sil -->
                <form :action="'<?= e(url('/admin/media/')) ?>' + activeItem.id + '/delete'" method="POST"
                      data-confirm="Bu görseli hem sunucu diskinden hem de medya kütüphanesinden kalıcı olarak silmek istediğinize emin misiniz?"
                      data-confirm-title="Görseli Kalıcı Olarak Sil"
                      class="pt-2 border-t border-zinc-100 dark:border-zinc-800">
                    <?= csrf_field() ?>
                    <button type="submit" class="w-full py-1.5 rounded-lg border border-red-200 dark:border-red-900 text-red-600 text-xs font-semibold hover:bg-red-50 dark:hover:bg-red-950/40">
                        <i class="fa-solid fa-trash mr-1"></i>Görseli Kalıcı Olarak Sil
                    </button>
                </form>
            </div>
        </template>
    </div>
</div>
