<?php
$authorName   = $author['name'] ?? setting('author_name', 'Yazar');
$authorTitle  = $author['role_title'] ?? setting('author_title', 'Yazılım Geliştirici');
$authorBio    = $author['bio'] ?? setting('author_bio', '');
$authorAvatar = $author['avatar'] ?? '';
$cvPdf        = $author['cv_pdf'] ?? '';
?>

<div class="space-y-10">
    <!-- CV Üst Başlık & Yazdır / İndir Aksiyonları -->
    <div class="pb-8 border-b border-zinc-200 dark:border-zinc-800 flex flex-col sm:flex-row sm:items-center justify-between gap-6">
        <div class="flex items-start sm:items-center gap-5">
            <?php if (!empty($authorAvatar)): ?>
                <img src="<?= e(url($authorAvatar)) ?>"
                     alt="<?= e($authorName) ?>"
                     fetchpriority="high"
                     width="80" height="80"
                     class="w-16 h-16 sm:w-20 sm:h-20 rounded-full object-cover border border-zinc-200 dark:border-zinc-800 shrink-0">
            <?php else: ?>
                <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full border border-zinc-200 dark:border-zinc-800 bg-zinc-100 dark:bg-zinc-900 flex items-center justify-center text-2xl font-bold shrink-0">
                    <?= e(mb_substr($authorName, 0, 1)) ?>
                </div>
            <?php endif; ?>

            <div>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight"><?= e($authorName) ?></h1>
                <p class="text-sm font-medium text-zinc-500 mt-0.5"><?= e($authorTitle) ?></p>
                <div class="flex flex-wrap items-center gap-3 mt-2 text-xs text-zinc-500">
                    <?php if ($em = setting('social_email')): ?>
                        <a href="mailto:<?= e($em) ?>" class="hover:text-zinc-900 dark:hover:text-zinc-100"><i class="fa-regular fa-envelope mr-1"></i><?= e($em) ?></a>
                    <?php endif; ?>
                    <?php if ($gh = setting('social_github')): ?>
                        <a href="<?= e($gh) ?>" target="_blank" rel="noopener noreferrer" class="hover:text-zinc-900 dark:hover:text-zinc-100"><i class="fa-brands fa-github mr-1"></i>GitHub</a>
                    <?php endif; ?>
                    <?php if ($li = setting('social_linkedin')): ?>
                        <a href="<?= e($li) ?>" target="_blank" rel="noopener noreferrer" class="hover:text-zinc-900 dark:hover:text-zinc-100"><i class="fa-brands fa-linkedin mr-1"></i>LinkedIn</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2 print:hidden shrink-0">
            <?php if (!empty($cvPdf)): ?>
                <a href="<?= e(url($cvPdf)) ?>" download
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90">
                    <i class="fa-solid fa-file-pdf"></i>
                    <span>PDF İndir</span>
                </a>
            <?php endif; ?>
            <button type="button" onclick="window.print()"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-zinc-200 dark:border-zinc-800 text-xs font-semibold hover:bg-zinc-100 dark:hover:bg-zinc-900 transition-colors">
                <i class="fa-solid fa-print"></i>
                <span>Yazdır / PDF Kaydet</span>
            </button>
        </div>
    </div>

    <!-- Özet Biyografi -->
    <?php if (!empty($authorBio)): ?>
        <section class="space-y-2">
            <h2 class="text-xs font-bold uppercase tracking-wider text-zinc-400">Hakkımda</h2>
            <p class="text-sm sm:text-base text-zinc-700 dark:text-zinc-300 leading-relaxed"><?= e($authorBio) ?></p>
        </section>
    <?php endif; ?>

    <!-- İş Deneyimleri -->
    <?php if (!empty($experiences)): ?>
        <section class="space-y-5">
            <h2 class="text-xs font-bold uppercase tracking-wider text-zinc-400 border-b border-zinc-200 dark:border-zinc-800 pb-2">
                <i class="fa-solid fa-briefcase mr-1.5"></i>İş Deneyimleri
            </h2>
            <div class="space-y-6">
                <?php foreach ($experiences as $exp): ?>
                    <div class="relative pl-5 border-l-2 border-zinc-200 dark:border-zinc-800 space-y-1.5">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                            <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100">
                                <?= e($exp['role']) ?>
                                <span class="font-normal text-zinc-500">— <?= e($exp['company']) ?></span>
                            </h3>
                            <span class="text-xs font-medium px-2.5 py-0.5 rounded border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 shrink-0">
                                <?= e($exp['start_date']) ?> — <?= !empty($exp['is_current']) ? 'Devam Ediyor' : e($exp['end_date']) ?>
                            </span>
                        </div>
                        <?php if (!empty($exp['location'])): ?>
                            <div class="text-xs text-zinc-500"><i class="fa-solid fa-location-dot mr-1"></i><?= e($exp['location']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($exp['description'])): ?>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed whitespace-pre-line pt-1"><?= e($exp['description']) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- Eğitim Geçmişi -->
    <?php if (!empty($educations)): ?>
        <section class="space-y-5">
            <h2 class="text-xs font-bold uppercase tracking-wider text-zinc-400 border-b border-zinc-200 dark:border-zinc-800 pb-2">
                <i class="fa-solid fa-graduation-cap mr-1.5"></i>Eğitim
            </h2>
            <div class="space-y-6">
                <?php foreach ($educations as $edu): ?>
                    <div class="relative pl-5 border-l-2 border-zinc-200 dark:border-zinc-800 space-y-1">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                            <h3 class="text-base font-bold">
                                <?= e($edu['institution']) ?>
                            </h3>
                            <span class="text-xs font-medium px-2.5 py-0.5 rounded border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 shrink-0">
                                <?= e($edu['start_date']) ?> — <?= !empty($edu['is_current']) ? 'Devam Ediyor' : e($edu['end_date']) ?>
                            </span>
                        </div>
                        <div class="text-xs font-medium text-zinc-700 dark:text-zinc-300">
                            <?= e($edu['degree']) ?><?= !empty($edu['field_of_study']) ? ' · ' . e($edu['field_of_study']) : '' ?>
                        </div>
                        <?php if (!empty($edu['description'])): ?>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed pt-1"><?= e($edu['description']) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- Yetenekler -->
    <?php if (!empty($skillsGrouped)): ?>
        <section class="space-y-5">
            <h2 class="text-xs font-bold uppercase tracking-wider text-zinc-400 border-b border-zinc-200 dark:border-zinc-800 pb-2">
                <i class="fa-solid fa-code mr-1.5"></i>Teknik Yetenekler & Uzmanlıklar
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <?php foreach ($skillsGrouped as $catName => $items): ?>
                    <div class="p-5 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/40 dark:bg-zinc-900/30 space-y-3">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-500"><?= e($catName) ?></h3>
                        <div class="space-y-2.5">
                            <?php foreach ($items as $sk): ?>
                                <div>
                                    <div class="flex items-center justify-between text-xs font-medium mb-1">
                                        <span><?= e($sk['name']) ?></span>
                                        <span class="text-zinc-500">%<?= (int) $sk['proficiency'] ?></span>
                                    </div>
                                    <div class="w-full h-1.5 rounded-full bg-zinc-200 dark:bg-zinc-800 overflow-hidden">
                                        <div class="h-full bg-zinc-900 dark:bg-zinc-100 rounded-full" style="width: <?= (int) $sk['proficiency'] ?>%;"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>
