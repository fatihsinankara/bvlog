<?php
use Core\Request;
$activeTab = Request::get('tab', 'experience');
?>

<div class="space-y-6" x-data="{ tab: <?= e(json_encode($activeTab)) ?> }">
    <?php if (!is_module_active('cv')): ?>
        <div class="p-4 rounded-xl border border-amber-300 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/50 text-amber-900 dark:text-amber-200 text-xs flex items-center justify-between">
            <span><i class="fa-solid fa-circle-info mr-1.5"></i>CV & Özgeçmiş modülü şu anda ziyaretçilere kapalıdır. Ayarlar'dan aktifleştirebilirsiniz.</span>
            <a href="<?= e(url('/admin/settings?tab=modules')) ?>" class="font-semibold underline">Modülü Aç</a>
        </div>
    <?php endif; ?>

    <!-- Üst Sekmeler & Yeni Ekle Butonu -->
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-zinc-200 dark:border-zinc-800 pb-4">
        <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
            <button type="button" @click="tab = 'experience'"
                    :class="tab === 'experience' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400'"
                    class="px-4 py-2 rounded-lg transition-colors">
                <i class="fa-solid fa-briefcase mr-1.5"></i>İş Deneyimleri (<?= count($experiences) ?>)
            </button>
            <button type="button" @click="tab = 'education'"
                    :class="tab === 'education' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400'"
                    class="px-4 py-2 rounded-lg transition-colors">
                <i class="fa-solid fa-graduation-cap mr-1.5"></i>Eğitim Geçmişi (<?= count($educations) ?>)
            </button>
            <button type="button" @click="tab = 'skills'"
                    :class="tab === 'skills' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400'"
                    class="px-4 py-2 rounded-lg transition-colors">
                <i class="fa-solid fa-code mr-1.5"></i>Teknik Yetenekler (<?= count($skills) ?>)
            </button>
            <button type="button" @click="tab = 'categories'"
                    :class="tab === 'categories' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400'"
                    class="px-4 py-2 rounded-lg transition-colors">
                <i class="fa-solid fa-layer-group mr-1.5"></i>Yetenek Grupları (<?= count($categories) ?>)
            </button>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="<?= e(url('/cv')) ?>" target="_blank"
               class="px-3.5 py-2 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-xs font-semibold hover:bg-zinc-50 dark:hover:bg-zinc-800">
                <i class="fa-solid fa-arrow-up-right-from-square mr-1.5"></i>CV Sayfasını Gör
            </a>

            <a x-show="tab === 'experience'"
               href="<?= e(url('/admin/cv/experience/create')) ?>"
               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90">
                <i class="fa-solid fa-plus"></i>
                <span>Yeni İş Deneyimi Ekle</span>
            </a>

            <a x-show="tab === 'education'" style="display: none;"
               href="<?= e(url('/admin/cv/education/create')) ?>"
               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90">
                <i class="fa-solid fa-plus"></i>
                <span>Yeni Eğitim Bilgisi Ekle</span>
            </a>

            <div x-show="tab === 'skills'" style="display: none;" class="flex items-center gap-2">
                <button type="button" @click="tab = 'categories'"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-xs font-semibold hover:bg-zinc-50 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-300">
                    <i class="fa-solid fa-layer-group text-zinc-400"></i>
                    <span>Grupları Yönet</span>
                </button>
                <a href="<?= e(url('/admin/cv/skill/create')) ?>"
                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90">
                    <i class="fa-solid fa-plus"></i>
                    <span>Yeni Yetenek Ekle</span>
                </a>
            </div>

            <a x-show="tab === 'categories'" style="display: none;"
               href="<?= e(url('/admin/cv/category/create')) ?>"
               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90">
                <i class="fa-solid fa-plus"></i>
                <span>Yeni Kategori Ekle</span>
            </a>
        </div>
    </div>

    <!-- SEKME 1: İŞ DENEYİMLERİ LİSTESİ -->
    <div x-show="tab === 'experience'" class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950/50 text-zinc-500 font-semibold">
                        <th class="py-3 px-4">Pozisyon & Şirket</th>
                        <th class="py-3 px-4">Tarih Aralığı</th>
                        <th class="py-3 px-4">Konum</th>
                        <th class="py-3 px-4">Sıra</th>
                        <th class="py-3 px-4 text-right">İşlem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    <?php if (empty($experiences)): ?>
                        <tr>
                            <td colspan="5" class="py-12 text-center text-zinc-500">Henüz iş deneyimi eklenmemiş.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($experiences as $exp): ?>
                            <tr class="hover:bg-zinc-50/60 dark:hover:bg-zinc-800/30 transition-colors">
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-sm text-zinc-900 dark:text-zinc-100"><?= e($exp['role']) ?></div>
                                    <div class="text-zinc-500 font-medium"><?= e($exp['company']) ?></div>
                                    <?php if (!empty($exp['description'])): ?>
                                        <p class="text-zinc-400 text-[11px] truncate max-w-md mt-1"><?= e($exp['description']) ?></p>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="px-2 py-0.5 rounded border border-zinc-200 dark:border-zinc-800 font-mono text-[11px]">
                                        <?= e($exp['start_date']) ?> — <?= !empty($exp['is_current']) ? 'Devam Ediyor' : e($exp['end_date']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-zinc-500"><?= e($exp['location'] ?: '—') ?></td>
                                <td class="py-3.5 px-4"><?= (int) $exp['display_order'] ?></td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <a href="<?= e(url('/admin/cv/experience/' . $exp['id'] . '/edit')) ?>"
                                           class="p-1.5 rounded border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800" title="Düzenle">
                                             <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <form action="<?= e(url('/admin/cv/experience/' . $exp['id'] . '/delete')) ?>"
                                              method="POST"
                                              data-confirm="“<?= e($exp['role'] . ' - ' . $exp['company']) ?>” iş deneyimini silmek istediğinize emin misiniz?"
                                              data-confirm-title="İş Deneyimini Sil"
                                              class="inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="p-1.5 rounded border border-zinc-200 dark:border-zinc-700 text-red-600 hover:bg-red-50 dark:hover:bg-red-950/40" title="Sil">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- SEKME 2: EĞİTİM GEÇMİŞİ LİSTESİ -->
    <div x-show="tab === 'education'" style="display: none;" class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950/50 text-zinc-500 font-semibold">
                        <th class="py-3 px-4">Okul / Kurum & Bölüm</th>
                        <th class="py-3 px-4">Derece</th>
                        <th class="py-3 px-4">Yıllar</th>
                        <th class="py-3 px-4">Sıra</th>
                        <th class="py-3 px-4 text-right">İşlem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    <?php if (empty($educations)): ?>
                        <tr>
                            <td colspan="5" class="py-12 text-center text-zinc-500">Henüz eğitim bilgisi eklenmemiş.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($educations as $edu): ?>
                            <tr class="hover:bg-zinc-50/60 dark:hover:bg-zinc-800/30 transition-colors">
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-sm text-zinc-900 dark:text-zinc-100"><?= e($edu['institution']) ?></div>
                                    <div class="text-zinc-500"><?= e($edu['field_of_study'] ?: '—') ?></div>
                                    <?php if (!empty($edu['description'])): ?>
                                        <p class="text-zinc-400 text-[11px] truncate max-w-md mt-1"><?= e($edu['description']) ?></p>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 font-medium"><?= e($edu['degree']) ?></td>
                                <td class="py-3.5 px-4">
                                    <span class="px-2 py-0.5 rounded border border-zinc-200 dark:border-zinc-800 font-mono text-[11px]">
                                        <?= e($edu['start_date']) ?> — <?= !empty($edu['is_current']) ? 'Devam Ediyor' : e($edu['end_date'] ?: 'Devam') ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4"><?= (int) $edu['display_order'] ?></td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <a href="<?= e(url('/admin/cv/education/' . $edu['id'] . '/edit')) ?>"
                                           class="p-1.5 rounded border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800" title="Düzenle">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <form action="<?= e(url('/admin/cv/education/' . $edu['id'] . '/delete')) ?>"
                                              method="POST"
                                              data-confirm="“<?= e($edu['institution']) ?>” eğitim bilgisini silmek istediğinize emin misiniz?"
                                              data-confirm-title="Eğitim Bilgisini Sil"
                                              class="inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="p-1.5 rounded border border-zinc-200 dark:border-zinc-700 text-red-600 hover:bg-red-50 dark:hover:bg-red-950/40" title="Sil">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- SEKME 3: TEKNİK YETENEKLER LİSTESİ -->
    <div x-show="tab === 'skills'" style="display: none;" class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950/50 text-zinc-500 font-semibold">
                        <th class="py-3 px-4">Yetenek / Teknoloji</th>
                        <th class="py-3 px-4">Kategori / Grup</th>
                        <th class="py-3 px-4">Yetkinlik Düzeyi</th>
                        <th class="py-3 px-4">Sıra</th>
                        <th class="py-3 px-4 text-right">İşlem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    <?php if (empty($skills)): ?>
                        <tr>
                            <td colspan="5" class="py-12 text-center text-zinc-500">Henüz yetenek eklenmemiş.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($skills as $sk): ?>
                            <tr class="hover:bg-zinc-50/60 dark:hover:bg-zinc-800/30 transition-colors">
                                <td class="py-3.5 px-4 font-bold text-sm text-zinc-900 dark:text-zinc-100">
                                    <?= e($sk['name']) ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 font-medium">
                                        <i class="fa-solid fa-folder text-[10px] text-zinc-400"></i>
                                        <?= e($sk['category_name'] ?? $sk['category']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 w-56">
                                    <div class="flex items-center gap-2.5">
                                        <div class="flex-1 h-2 rounded-full bg-zinc-100 dark:bg-zinc-800 overflow-hidden">
                                            <div class="h-full bg-zinc-900 dark:bg-zinc-100 rounded-full" style="width: <?= (int) $sk['proficiency'] ?>%;"></div>
                                        </div>
                                        <span class="font-mono text-[11px] font-semibold w-9 text-right">%<?= (int) $sk['proficiency'] ?></span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4"><?= (int) $sk['display_order'] ?></td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <a href="<?= e(url('/admin/cv/skill/' . $sk['id'] . '/edit')) ?>"
                                           class="p-1.5 rounded border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800" title="Düzenle">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <form action="<?= e(url('/admin/cv/skill/' . $sk['id'] . '/delete')) ?>"
                                              method="POST"
                                              data-confirm="“<?= e($sk['name']) ?>” yeteneğini silmek istediğinize emin misiniz?"
                                              data-confirm-title="Yeteneği Sil"
                                              class="inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="p-1.5 rounded border border-zinc-200 dark:border-zinc-700 text-red-600 hover:bg-red-50 dark:hover:bg-red-950/40" title="Sil">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- SEKME 4: YETENEK KATEGORİLERİ / GRUPLARI LİSTESİ -->
    <div x-show="tab === 'categories'" style="display: none;" class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950/50 text-zinc-500 font-semibold">
                        <th class="py-3 px-4">Kategori / Grup Adı</th>
                        <th class="py-3 px-4">Bağlı Yetenekler</th>
                        <th class="py-3 px-4">Sıralama</th>
                        <th class="py-3 px-4 text-right">İşlem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="4" class="py-12 text-center text-zinc-500">Henüz yetenek grubu eklenmemiş.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categories as $cat): ?>
                            <tr class="hover:bg-zinc-50/60 dark:hover:bg-zinc-800/30 transition-colors">
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-sm text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                                        <i class="fa-solid fa-layer-group text-zinc-400"></i>
                                        <span><?= e($cat['name']) ?></span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 font-semibold text-[11px]">
                                        <i class="fa-solid fa-code text-[10px] text-zinc-400"></i>
                                        <?= (int) ($cat['skill_count'] ?? 0) ?> Yetenek
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-[11px]"><?= (int) $cat['display_order'] ?></td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <a href="<?= e(url('/admin/cv/category/' . $cat['id'] . '/edit')) ?>"
                                           class="p-1.5 rounded border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800" title="Düzenle">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <form action="<?= e(url('/admin/cv/category/' . $cat['id'] . '/delete')) ?>"
                                              method="POST"
                                              data-confirm="“<?= e($cat['name']) ?>” yetenek kategorisini silmek istediğinize emin misiniz?<?= ((int)($cat['skill_count'] ?? 0) > 0) ? ' (Bu kategoriye bağlı ' . (int)$cat['skill_count'] . ' yetenek bulunmaktadır)' : '' ?>"
                                              data-confirm-title="Kategoriyi Sil"
                                              class="inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="p-1.5 rounded border border-zinc-200 dark:border-zinc-700 text-red-600 hover:bg-red-50 dark:hover:bg-red-950/40" title="Sil">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
