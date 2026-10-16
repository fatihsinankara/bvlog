<?php
$isEdit = !empty($post);
$actionUrl = $isEdit ? url('/admin/posts/' . $post['id'] . '/update') : url('/admin/posts');
?>

<form action="<?= e($actionUrl) ?>" method="POST" enctype="multipart/form-data"
      x-data="bvlogPostEditor({
          title: <?= e(json_encode($post['title'] ?? '')) ?>,
          slug: <?= e(json_encode($post['slug'] ?? '')) ?>,
          summary: <?= e(json_encode($post['summary'] ?? '')) ?>,
          seoTitle: <?= e(json_encode($post['seo_title'] ?? '')) ?>,
          seoDesc: <?= e(json_encode($post['seo_description'] ?? '')) ?>,
          coverImage: <?= e(json_encode($post['cover_image'] ?? '')) ?>,
          siteTitle: <?= e(json_encode(setting('site_title', 'Bvlog'))) ?>,
          baseUrl: <?= e(json_encode(url('/blog/'))) ?>
      })"
      @submit="syncEditorContent()"
      class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
    <?= csrf_field() ?>

    <!-- Sol Sütun: Başlık, Özel Görsel WYSIWYG Editör ve SEO -->
    <div class="lg:col-span-8 space-y-6">

        <!-- Başlık & Slug Kartı -->
        <div class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-4">
            <div>
                <label class="block text-xs font-semibold mb-1.5">Yazı Başlığı *</label>
                <input type="text" name="title" x-model="title" @input="autoSlug()" required
                       placeholder="Etkileyici bir yazı başlığı girin..."
                       class="w-full px-3.5 py-2.5 text-base font-bold rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 focus:outline-none focus:border-zinc-500">
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1">Kalıcı Bağlantı (Slug)</label>
                <div class="flex items-center rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 overflow-hidden text-xs">
                    <span class="px-3 py-2 text-zinc-400 border-r border-zinc-200 dark:border-zinc-800 select-none">/blog/</span>
                    <input type="text" name="slug" x-model="slug" @input="slugEdited = true"
                           placeholder="otomatik-olusturulur"
                           class="w-full px-3 py-2 bg-transparent focus:outline-none font-mono">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1.5">Kısa Özet (Liste görünümü ve giriş için)</label>
                <textarea name="summary" x-model="summary" rows="2" maxlength="500"
                          placeholder="Yazının ana fikrini 1-2 cümleyle özetleyin..."
                          class="w-full px-3.5 py-2 text-sm rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 focus:outline-none focus:border-zinc-500"></textarea>
            </div>
        </div>

        <!-- ÖZEL BVLOG GÖRSEL (WYSIWYG) EDİTÖRÜ -->
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 overflow-hidden flex flex-col">
            <!-- Editör Araç Çubuğu -->
            <div @mousedown="saveSelection()" class="px-3 py-2 border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 flex flex-wrap items-center justify-between gap-1.5">
                <div class="flex flex-wrap items-center gap-1">
                    <button type="button" @click="formatBlock('h2')" class="px-2.5 py-1.5 rounded text-xs font-bold hover:bg-zinc-200 dark:hover:bg-zinc-800" title="Başlık 2">H2</button>
                    <button type="button" @click="formatBlock('h3')" class="px-2.5 py-1.5 rounded text-xs font-bold hover:bg-zinc-200 dark:hover:bg-zinc-800" title="Başlık 3">H3</button>
                    <button type="button" @click="formatBlock('p')" class="px-2 py-1.5 rounded text-xs font-medium hover:bg-zinc-200 dark:hover:bg-zinc-800" title="Paragraf">¶</button>

                    <span class="w-px h-4 bg-zinc-300 dark:bg-zinc-700 mx-1"></span>

                    <button type="button" @click="exec('bold')" class="w-7 h-7 rounded text-xs hover:bg-zinc-200 dark:hover:bg-zinc-800" title="Kalın (Ctrl+B)"><i class="fa-solid fa-bold"></i></button>
                    <button type="button" @click="exec('italic')" class="w-7 h-7 rounded text-xs hover:bg-zinc-200 dark:hover:bg-zinc-800" title="İtalik (Ctrl+I)"><i class="fa-solid fa-italic"></i></button>
                    <button type="button" @click="exec('strikeThrough')" class="w-7 h-7 rounded text-xs hover:bg-zinc-200 dark:hover:bg-zinc-800" title="Üstü Çizili"><i class="fa-solid fa-strikethrough"></i></button>

                    <span class="w-px h-4 bg-zinc-300 dark:bg-zinc-700 mx-1"></span>

                    <button type="button" @click="exec('insertUnorderedList')" class="w-7 h-7 rounded text-xs hover:bg-zinc-200 dark:hover:bg-zinc-800" title="Madde İşaretli Liste"><i class="fa-solid fa-list-ul"></i></button>
                    <button type="button" @click="exec('insertOrderedList')" class="w-7 h-7 rounded text-xs hover:bg-zinc-200 dark:hover:bg-zinc-800" title="Numaralı Liste"><i class="fa-solid fa-list-ol"></i></button>
                    <button type="button" @click="formatBlock('blockquote')" class="w-7 h-7 rounded text-xs hover:bg-zinc-200 dark:hover:bg-zinc-800" title="Alıntı Kutusu"><i class="fa-solid fa-quote-left"></i></button>
                    <button type="button" @click="insertCodeBlock()" class="w-7 h-7 rounded text-xs hover:bg-zinc-200 dark:hover:bg-zinc-800" title="Kod Bloğu Ekle"><i class="fa-solid fa-code"></i></button>

                    <span class="w-px h-4 bg-zinc-300 dark:bg-zinc-700 mx-1"></span>

                    <button type="button" @click="insertLink()" class="w-7 h-7 rounded text-xs hover:bg-zinc-200 dark:hover:bg-zinc-800" title="Bağlantı (Link) Ekle"><i class="fa-solid fa-link"></i></button>

                    <!-- Inline Resim Yükle -->
                    <label class="px-2.5 py-1.5 rounded text-xs font-medium hover:bg-zinc-200 dark:hover:bg-zinc-800 cursor-pointer inline-flex items-center gap-1" title="Bilgisayardan Görsel Yükle">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <span class="hidden sm:inline">Yükle</span>
                        <input type="file" accept="image/*" class="hidden" @change="uploadInlineFile($event)">
                    </label>

                    <!-- Medya Kütüphanesinden Seç -->
                    <button type="button" @click="openEditorMediaPicker()" class="px-2.5 py-1.5 rounded text-xs font-medium hover:bg-zinc-200 dark:hover:bg-zinc-800 inline-flex items-center gap-1" title="Medya Galerisinden Seç">
                        <i class="fa-regular fa-images"></i>
                        <span class="hidden sm:inline">Galeri</span>
                    </button>

                    <!-- Akıllı Embed Ekle -->
                    <button type="button" @click="promptEmbed()" class="px-2.5 py-1.5 rounded text-xs font-medium hover:bg-zinc-200 dark:hover:bg-zinc-800 inline-flex items-center gap-1" title="Video veya Sosyal Medya Bağlantısı Ekle">
                        <i class="fa-brands fa-youtube"></i>
                        <span class="hidden sm:inline">Embed</span>
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <span x-show="inlineUploading" class="text-[11px] font-semibold text-amber-600 dark:text-amber-400">
                        <i class="fa-solid fa-circle-notch fa-spin mr-1"></i>Görsel yükleniyor...
                    </span>
                    <button type="button" @click="toggleSourceMode()"
                            :class="sourceMode ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-500 hover:bg-zinc-200 dark:hover:bg-zinc-800'"
                            class="px-2 py-1 rounded text-[11px] font-mono transition-colors" title="Kaynak Kodu Görünümü">
                        &lt;/&gt;
                    </button>
                </div>
            </div>

            <!-- Yazma Alanı -->
            <div x-show="!sourceMode"
                 x-ref="wysiwyg"
                 contenteditable="true"
                 @input="syncEditorContent(); saveSelection()"
                 @keyup="saveSelection()"
                 @mouseup="saveSelection()"
                 @touchend="saveSelection()"
                 @focus="saveSelection()"
                 @paste="handlePaste($event)"
                 @drop.prevent="handleDrop($event)"
                 @dragover.prevent="isDragging = true"
                 @dragleave.prevent="isDragging = false"
                 :class="isDragging ? 'ring-2 ring-zinc-900 dark:ring-zinc-100 bg-zinc-50 dark:bg-zinc-800/40' : ''"
                 class="bvlog-editor-content prose prose-zinc dark:prose-invert max-w-none px-4 py-3 min-h-[420px] focus:outline-none">
                <?= $post['content'] ?? '<p><br></p>' ?>
            </div>

            <!-- Kaynak Kodu Modu -->
            <textarea x-show="sourceMode"
                      x-ref="rawHtml"
                      name="content"
                      rows="18"
                      style="display: none;"
                      class="w-full px-4 py-3 font-mono text-xs bg-zinc-950 text-zinc-100 focus:outline-none min-h-[420px]"><?= e($post['content'] ?? '') ?></textarea>

            <!-- Alt Bilgi Çubuğu -->
            <div class="px-4 py-2 border-t border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 flex items-center justify-between text-[11px] text-zinc-500">
                <span><i class="fa-regular fa-lightbulb mr-1"></i>İpucu: Görselleri doğrudan editöre sürükleyebilir veya <kbd class="px-1 py-0.5 rounded border border-zinc-300 dark:border-zinc-700">Ctrl+V</kbd> ile yapıştırabilirsiniz.</span>
            </div>
        </div>

        <!-- SEO & ARAMA MOTORU ÖNİZLEMESİ -->
        <div class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-5">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold"><i class="fa-solid fa-magnifying-glass mr-1.5"></i>Arama Motoru Görünümü (SEO)</h2>
                    <p class="text-xs text-zinc-500">Google arama sonuçlarında ve sosyal medya paylaşımlarında görünecek bilgiler.</p>
                </div>
            </div>

            <!-- Canlı Google Önizleme Kartı -->
            <div class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/70 dark:bg-zinc-950 space-y-1">
                <div class="text-[11px] text-zinc-500 truncate" x-text="baseUrl + (slug || 'yazi-adresi')"></div>
                <div class="text-base font-semibold text-blue-700 dark:text-blue-400 truncate"
                     x-text="(seoTitle || title || 'Yazı Başlığı') + ' — ' + siteTitle"></div>
                <div class="text-xs text-zinc-600 dark:text-zinc-400 line-clamp-2"
                     x-text="seoDesc || summary || 'Yazı özeti veya ilk paragrafı arama motorlarında bu alanda açıklama olarak gösterilecektir.'"></div>
            </div>

            <div class="space-y-4">
                <div>
                    <div class="flex items-center justify-between text-xs mb-1">
                        <label class="font-semibold">Arama Motoru Başlığı (Boş bırakılırsa yazı başlığı kullanılır)</label>
                        <span :class="(seoTitle.length > 60) ? 'text-red-500 font-bold' : 'text-zinc-400'" x-text="seoTitle.length + ' / 60'"></span>
                    </div>
                    <input type="text" name="seo_title" x-model="seoTitle" maxlength="255"
                           placeholder="Arama sonuçları için alternatif başlık..."
                           class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 focus:outline-none focus:border-zinc-500">
                </div>

                <div>
                    <div class="flex items-center justify-between text-xs mb-1">
                        <label class="font-semibold">Arama Motoru Açıklaması</label>
                        <span :class="(seoDesc.length > 160) ? 'text-red-500 font-bold' : 'text-zinc-400'" x-text="seoDesc.length + ' / 160'"></span>
                    </div>
                    <textarea name="seo_description" x-model="seoDesc" rows="2" maxlength="255"
                              placeholder="Arama motorları için 150-160 karakterlik açıklama..."
                              class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 focus:outline-none focus:border-zinc-500"></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Sağ Sütun: Yayın Durumu, Kategori, Etiketler ve Öne Çıkan Görsel -->
    <div class="lg:col-span-4 space-y-6">

        <!-- Yayınlama Kutusu -->
        <div class="p-5 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-4">
            <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-400">Yayın Ayarları</h3>

            <div>
                <label class="block text-xs font-semibold mb-1.5">Durum</label>
                <select name="status" class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 focus:outline-none">
                    <option value="published" <?= ($post['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>Yayında (Herkese Açık)</option>
                    <option value="draft" <?= ($post['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Taslak (Gizli)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1.5">Kategori</label>
                <select name="category_id" class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 focus:outline-none">
                    <option value="0">— Kategori Seçin —</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int) $cat['id'] ?>" <?= (int) ($post['category_id'] ?? 0) === (int) $cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <label class="flex items-center gap-2.5 text-xs font-medium cursor-pointer pt-1">
                <input type="checkbox" name="is_featured" value="1" <?= !empty($post['is_featured']) ? 'checked' : '' ?> class="rounded border-zinc-300">
                <span>Bu yazıyı <strong>Öne Çıkanlar</strong> vitrininde göster</span>
            </label>

            <div class="pt-3 border-t border-zinc-100 dark:border-zinc-800 flex items-center gap-2">
                <button type="submit" class="flex-1 py-2.5 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90 transition-opacity">
                    <i class="fa-solid fa-floppy-disk mr-1.5"></i><?= $isEdit ? 'Değişiklikleri Kaydet' : 'Yazıyı Yayınla' ?>
                </button>
                <a href="<?= e(url('/admin/posts')) ?>" class="px-3.5 py-2.5 rounded-lg border border-zinc-200 dark:border-zinc-700 text-xs font-medium hover:bg-zinc-100 dark:hover:bg-zinc-800">
                    İptal
                </a>
            </div>
        </div>

        <!-- Öne Çıkan Görsel -->
        <div class="p-5 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-4">
            <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-400">Öne Çıkan Görsel</h3>

            <input type="hidden" name="cover_image" x-model="coverImage">

            <template x-if="coverImage">
                <div class="relative rounded-lg overflow-hidden border border-zinc-200 dark:border-zinc-800 bg-zinc-100 dark:bg-zinc-950 aspect-[16/9]">
                    <img :src="coverImage" alt="Kapak Önizleme" class="w-full h-full object-cover">
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

        <!-- Etiketler -->
        <div class="p-5 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-400">Etiketler</h3>
            <input type="text" name="tags" value="<?= e($tagsString ?? '') ?>"
                   placeholder="yazılım, mimari, notlar"
                   class="w-full px-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 focus:outline-none focus:border-zinc-500">
            <p class="text-[11px] text-zinc-500">Etiketleri virgülle ayırarak yazın.</p>
        </div>
    </div>
</form>
