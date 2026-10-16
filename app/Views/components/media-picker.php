<dialog id="bvlog-media-picker-dialog"
        closedby="any"
        aria-labelledby="bvlog-media-picker-title"
        x-data="bvlogMediaPicker()"
        @open-media-picker.window="openPicker($event.detail)"
        class="w-full max-w-4xl rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 p-0 shadow-2xl">
    <div class="flex flex-col h-[80vh] max-h-[680px]">
        <!-- Üst Başlık -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-zinc-200 dark:border-zinc-800">
            <div class="flex items-center gap-4">
                <h3 id="bvlog-media-picker-title" class="text-base font-bold">Medya Kütüphanesi</h3>
                <div class="flex items-center border border-zinc-200 dark:border-zinc-800 rounded-lg overflow-hidden text-xs font-medium">
                    <button type="button"
                            @click="tab = 'library'"
                            :class="tab === 'library' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'bg-transparent text-zinc-600 dark:text-zinc-400'"
                            class="px-3 py-1.5 transition-colors">
                        <i class="fa-regular fa-images mr-1.5"></i>Galeriden Seç
                    </button>
                    <button type="button"
                            @click="tab = 'upload'"
                            :class="tab === 'upload' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'bg-transparent text-zinc-600 dark:text-zinc-400'"
                            class="px-3 py-1.5 transition-colors">
                        <i class="fa-solid fa-cloud-arrow-up mr-1.5"></i>Yeni Görsel Yükle
                    </button>
                </div>
            </div>
            <button type="button" @click="closePicker()" class="text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- Sekme 1: Galeri -->
        <div x-show="tab === 'library'" class="flex-1 flex flex-col overflow-hidden">
            <div class="px-6 py-3 border-b border-zinc-100 dark:border-zinc-800/80 flex items-center justify-between gap-4">
                <div class="relative flex-1 max-w-sm">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 text-xs"></i>
                    <input type="text"
                           x-model="searchQuery"
                           @input.debounce.300ms="fetchMedia()"
                           placeholder="Görsel adı veya alt metni ara..."
                           class="w-full pl-8 pr-3 py-1.5 text-sm rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 focus:outline-none focus:border-zinc-500">
                </div>
                <span class="text-xs text-zinc-500" x-text="items.length + ' görsel listeleniyor'"></span>
            </div>

            <div class="flex-1 overflow-y-auto p-6">
                <template x-if="loading">
                    <div class="py-16 text-center text-sm text-zinc-500">
                        <i class="fa-solid fa-circle-notch fa-spin mr-2"></i>Medya kütüphanesi yükleniyor...
                    </div>
                </template>

                <template x-if="!loading && items.length === 0">
                    <div class="py-16 text-center">
                        <i class="fa-regular fa-image text-3xl text-zinc-400 mb-2"></i>
                        <p class="text-sm text-zinc-500">Henüz görsel bulunmuyor. "Yeni Görsel Yükle" sekmesinden ekleyebilirsiniz.</p>
                    </div>
                </template>

                <div x-show="!loading && items.length > 0" class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-5 gap-3">
                    <template x-for="item in items" :key="item.id">
                        <div @click="selected = item"
                             :class="selected && selected.id === item.id ? 'ring-2 ring-zinc-900 dark:ring-zinc-100 border-zinc-900 dark:border-zinc-100' : 'border-zinc-200 dark:border-zinc-800 hover:border-zinc-400'"
                             class="group relative aspect-square rounded-lg border overflow-hidden cursor-pointer bg-zinc-100 dark:bg-zinc-950">
                            <img :src="item.filepath" :alt="item.alt_text || item.title" loading="lazy" class="w-full h-full object-cover">
                            <div class="absolute inset-x-0 bottom-0 bg-zinc-950/75 text-white px-2 py-1 text-[11px] truncate" x-text="item.title || item.filename"></div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Sekme 2: Yeni Yükle -->
        <div x-show="tab === 'upload'" class="flex-1 flex flex-col items-center justify-center p-8">
            <label class="w-full max-w-lg border-2 border-dashed border-zinc-300 dark:border-zinc-700 hover:border-zinc-500 rounded-xl p-10 text-center cursor-pointer transition-colors bg-zinc-50/50 dark:bg-zinc-950/40">
                <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden" @change="handleUpload($event)">
                <div x-show="!uploading">
                    <i class="fa-solid fa-cloud-arrow-up text-3xl text-zinc-500 mb-3"></i>
                    <p class="text-sm font-semibold mb-1">Görseli seçmek için tıklayın</p>
                    <p class="text-xs text-zinc-500">Maksimum dosya boyutu: 15MB</p>
                </div>
                <div x-show="uploading" class="py-4">
                    <i class="fa-solid fa-circle-notch fa-spin text-2xl text-zinc-700 dark:text-zinc-200 mb-2"></i>
                    <p class="text-sm font-medium">Görsel yükleniyor, lütfen bekleyin...</p>
                </div>
            </label>
            <template x-if="uploadError">
                <p class="mt-4 text-xs font-medium text-red-600 dark:text-red-400" x-text="uploadError"></p>
            </template>
        </div>

        <!-- Alt Çubuk -->
        <div class="px-6 py-3.5 border-t border-zinc-200 dark:border-zinc-800 flex items-center justify-between bg-zinc-50 dark:bg-zinc-950">
            <div class="text-xs text-zinc-500 truncate max-w-md">
                <template x-if="selected">
                    <span>Seçilen: <strong class="text-zinc-900 dark:text-zinc-100" x-text="selected.filepath"></strong> (<span x-text="selected.width + 'x' + selected.height + 'px'"></span>)</span>
                </template>
                <template x-if="!selected">
                    <span>Lütfen bir görsel seçin veya yükleyin.</span>
                </template>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" @click="closePicker()" class="px-3.5 py-1.5 text-xs font-medium rounded-lg border border-zinc-300 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800">
                    İptal
                </button>
                <button type="button"
                        :disabled="!selected"
                        @click="confirmSelection()"
                        class="px-4 py-1.5 text-xs font-semibold rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 disabled:opacity-40 hover:opacity-90 transition-opacity">
                    Görseli Kullan
                </button>
            </div>
        </div>
    </div>
</dialog>
