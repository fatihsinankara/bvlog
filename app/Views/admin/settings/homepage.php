<div class="space-y-6 w-full"
     x-data="{
         sections: <?= e(json_encode($sections)) ?>,
         moveUp(idx) {
             if (idx <= 0) return;
             const temp = this.sections[idx - 1];
             this.sections[idx - 1] = this.sections[idx];
             this.sections[idx] = temp;
         },
         moveDown(idx) {
             if (idx >= this.sections.length - 1) return;
             const temp = this.sections[idx + 1];
             this.sections[idx + 1] = this.sections[idx];
             this.sections[idx] = temp;
         }
     }">

    <form action="<?= e(url('/admin/settings/homepage')) ?>" method="POST"
          class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 space-y-6 w-full">
        <?= csrf_field() ?>
        <input type="hidden" name="active_tab" value="homepage">
        <input type="hidden" name="homepage_sections_json" :value="JSON.stringify(sections)">

        <div class="border-b border-zinc-100 dark:border-zinc-800 pb-4">
            <h2 class="text-base font-bold tracking-tight">Anasayfa Yerleşimi & Blok Sıralaması</h2>
            <p class="text-xs text-zinc-500 mt-0.5">Anasayfa karşılama (Hero) stilini, yazı listeleme düzenini ve blokların sıralamasını belirleyin.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold mb-1.5">Anasayfa Giriş (Hero) Stili</label>
                <select name="homepage_hero_style" class="w-full px-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
                    <?php $hs = $settings['homepage_hero_style'] ?? 'minimal_bio'; ?>
                    <option value="minimal_bio" <?= $hs === 'minimal_bio' ? 'selected' : '' ?>>Minimalist Yazar Kartı (Önerilen)</option>
                    <option value="featured_post" <?= $hs === 'featured_post' ? 'selected' : '' ?>>Öne Çıkan Yazı Vitrini</option>
                    <option value="split" <?= $hs === 'split' ? 'selected' : '' ?>>İkiye Bölünmüş (Sol Biyo / Sağ Son Yazılar)</option>
                    <option value="none" <?= $hs === 'none' ? 'selected' : '' ?>>Hero Yok (Doğrudan İçerik Akışı)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1.5">Yazı Listeleme Görünümü</label>
                <select name="homepage_post_style" class="w-full px-3 py-2 text-xs rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
                    <?php $ps = $settings['homepage_post_style'] ?? 'list'; ?>
                    <option value="list" <?= $ps === 'list' ? 'selected' : '' ?>>Tek Sütun Minimalist Okuma Listesi (Medium/Substack)</option>
                    <option value="grid" <?= $ps === 'grid' ? 'selected' : '' ?>>Çok Sütunlu Kart Izgara (Grid)</option>
                </select>
            </div>
        </div>

        <div class="space-y-3">
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-400">Anasayfa Blok Sıralaması & Görünürlük</h3>
                <p class="text-xs text-zinc-500 mt-0.5">Blokların sırasını yukarı/aşağı oklarla değiştirebilir veya istediğiniz bölümü gizleyebilirsiniz.</p>
            </div>

            <div class="divide-y divide-zinc-100 dark:divide-zinc-800 border border-zinc-200 dark:border-zinc-800 rounded-xl overflow-hidden">
                <template x-for="(sec, idx) in sections" :key="sec.id">
                    <div class="px-4 py-3 flex items-center justify-between bg-zinc-50/50 dark:bg-zinc-950/40 text-xs">
                        <label class="flex items-center gap-3 cursor-pointer font-medium">
                            <input type="checkbox" x-model="sec.enabled" role="switch" class="ios-switch">
                            <span x-text="(idx + 1) + '. ' + sec.title" :class="sec.enabled ? 'font-bold text-zinc-900 dark:text-zinc-100' : 'line-through text-zinc-400'"></span>
                        </label>
                        <div class="flex items-center gap-1">
                            <button type="button" @click="moveUp(idx)" :disabled="idx === 0"
                                    class="w-7 h-7 rounded border border-zinc-200 dark:border-zinc-700 disabled:opacity-30 hover:bg-white dark:hover:bg-zinc-800">
                                <i class="fa-solid fa-arrow-up"></i>
                            </button>
                            <button type="button" @click="moveDown(idx)" :disabled="idx === sections.length - 1"
                                    class="w-7 h-7 rounded border border-zinc-200 dark:border-zinc-700 disabled:opacity-30 hover:bg-white dark:hover:bg-zinc-800">
                                <i class="fa-solid fa-arrow-down"></i>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <button type="submit" class="px-5 py-2.5 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90">
            Anasayfa Düzenini Kaydet
        </button>
    </form>

</div>
