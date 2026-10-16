<?php
use Core\View;

$authorName   = $author['name'] ?? setting('author_name', 'Yazar');
$authorTitle  = $author['role_title'] ?? setting('author_title', 'Yazılım Geliştirici');
$authorBio    = $author['bio'] ?? setting('author_bio', '');
$authorAvatar = $author['avatar'] ?? '';
?>

<div class="space-y-14">
    <?php foreach ($sections as $section): ?>
        <?php if (empty($section['enabled'])) continue; ?>

        <?php if ($section['id'] === 'hero' && $heroStyle !== 'none'): ?>
            <!-- HERO BÖLÜMÜ -->
            <?php if ($heroStyle === 'minimal_bio'): ?>
                <section class="pb-10 border-b border-zinc-200 dark:border-zinc-800">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-6">
                        <?php if (!empty($authorAvatar)): ?>
                            <img src="<?= e(url($authorAvatar)) ?>"
                                 alt="<?= e($authorName) ?>"
                                 fetchpriority="high"
                                 width="88" height="88"
                                 class="w-20 h-20 sm:w-22 sm:h-22 rounded-full object-cover border border-zinc-200 dark:border-zinc-800 shrink-0">
                        <?php else: ?>
                            <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full border border-zinc-200 dark:border-zinc-800 bg-zinc-100 dark:bg-zinc-900 flex items-center justify-center text-2xl font-bold shrink-0">
                                <?= e(mb_substr($authorName, 0, 1)) ?>
                            </div>
                        <?php endif; ?>

                        <div class="space-y-2.5 flex-1">
                            <div class="flex flex-wrap items-center gap-2.5">
                                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight"><?= e($authorName) ?></h1>
                                <?php if (!empty($authorTitle)): ?>
                                    <span class="px-2.5 py-0.5 text-xs font-medium rounded border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400">
                                        <?= e($authorTitle) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($authorBio)): ?>
                                <p class="text-sm sm:text-base text-zinc-600 dark:text-zinc-400 leading-relaxed max-w-2xl">
                                    <?= e($authorBio) ?>
                                </p>
                            <?php endif; ?>

                            <div class="pt-2 flex flex-wrap items-center gap-3">
                                <a href="<?= e(url('/blog')) ?>"
                                   class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90 transition-opacity">
                                    <span>Yazıları Oku</span>
                                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                </a>
                                <?php if (is_module_active('portfolio')): ?>
                                    <a href="<?= e(url('/portfolio')) ?>"
                                       class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-zinc-200 dark:border-zinc-800 text-xs font-semibold hover:bg-zinc-100 dark:hover:bg-zinc-900 transition-colors">
                                        <i class="fa-solid fa-briefcase text-zinc-500"></i>
                                        <span>Portfolyo</span>
                                    </a>
                                <?php endif; ?>
                                <?php if (is_module_active('cv')): ?>
                                    <a href="<?= e(url('/cv')) ?>"
                                       class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-zinc-200 dark:border-zinc-800 text-xs font-semibold hover:bg-zinc-100 dark:hover:bg-zinc-900 transition-colors">
                                        <i class="fa-solid fa-id-card text-zinc-500"></i>
                                        <span>Özgeçmiş (CV)</span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </section>

            <?php elseif ($heroStyle === 'featured_post' && !empty($latestPosts[0])): ?>
                <?php $heroPost = $featuredPosts[0] ?? $latestPosts[0]; ?>
                <section class="pb-10 border-b border-zinc-200 dark:border-zinc-800">
                    <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 p-6 sm:p-8 bg-zinc-50 dark:bg-zinc-900/50">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="px-2 py-0.5 text-[11px] font-bold uppercase tracking-wider rounded bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900">Öne Çıkan</span>
                            <?= View::renderPartial('components/category-badge', [
                                'name'  => $heroPost['category_name'] ?? '',
                                'slug'  => $heroPost['category_slug'] ?? '',
                                'icon'  => $heroPost['category_icon'] ?? '',
                                'color' => $heroPost['category_color'] ?? '#18181b',
                            ]) ?>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-bold tracking-tight mb-3">
                            <a href="<?= e(url('/blog/' . $heroPost['slug'])) ?>" class="hover:underline">
                                <?= e($heroPost['title']) ?>
                            </a>
                        </h1>
                        <?php if (!empty($heroPost['summary'])): ?>
                            <p class="text-sm sm:text-base text-zinc-600 dark:text-zinc-400 mb-4 leading-relaxed">
                                <?= e($heroPost['summary']) ?>
                            </p>
                        <?php endif; ?>
                        <div class="flex items-center gap-4 text-xs text-zinc-500">
                            <span><i class="fa-regular fa-calendar mr-1"></i><?= e(format_date($heroPost['published_at'] ?: $heroPost['created_at'])) ?></span>
                            <span><i class="fa-regular fa-clock mr-1"></i><?= reading_time($heroPost['content']) ?> dk okuma</span>
                        </div>
                    </div>
                </section>

            <?php elseif ($heroStyle === 'split'): ?>
                <section class="pb-10 border-b border-zinc-200 dark:border-zinc-800 grid grid-cols-1 md:grid-cols-12 gap-8 items-start">
                    <div class="md:col-span-5 space-y-3">
                        <h1 class="text-2xl font-bold tracking-tight"><?= e($authorName) ?></h1>
                        <?php if (!empty($authorTitle)): ?>
                            <p class="text-xs font-semibold text-zinc-500 uppercase tracking-wider"><?= e($authorTitle) ?></p>
                        <?php endif; ?>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed"><?= e($authorBio) ?></p>
                        <div class="pt-2 flex items-center gap-3 text-sm">
                            <a href="<?= e(url('/blog')) ?>" class="font-semibold underline underline-offset-4">Blog</a>
                            <?php if (is_module_active('portfolio')): ?>
                                <a href="<?= e(url('/portfolio')) ?>" class="font-semibold underline underline-offset-4">Portfolyo</a>
                            <?php endif; ?>
                            <?php if (is_module_active('cv')): ?>
                                <a href="<?= e(url('/cv')) ?>" class="font-semibold underline underline-offset-4">CV</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="md:col-span-7 space-y-3 border-l-0 md:border-l border-zinc-200 dark:border-zinc-800 md:pl-8">
                        <div class="text-xs font-bold uppercase tracking-wider text-zinc-400">Son Notlar</div>
                        <?php foreach (array_slice($latestPosts, 0, 3) as $p): ?>
                            <div class="py-1.5">
                                <a href="<?= e(url('/blog/' . $p['slug'])) ?>" class="text-sm font-semibold hover:underline block"><?= e($p['title']) ?></a>
                                <span class="text-xs text-zinc-500"><?= e(format_date($p['published_at'] ?: $p['created_at'])) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

        <?php elseif ($section['id'] === 'featured_posts' && !empty($featuredPosts)): ?>
            <!-- ÖNE ÇIKAN YAZILAR -->
            <section class="space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        <i class="fa-solid fa-star text-amber-500 mr-1.5"></i>Öne Çıkan Yazılar
                    </h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($featuredPosts as $fp): ?>
                        <article class="p-5 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/60 dark:bg-zinc-900/40 hover:border-zinc-400 dark:hover:border-zinc-700 transition-colors flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between gap-2 mb-2.5">
                                    <?= View::renderPartial('components/category-badge', [
                                        'name'  => $fp['category_name'] ?? '',
                                        'slug'  => $fp['category_slug'] ?? '',
                                        'icon'  => $fp['category_icon'] ?? '',
                                        'color' => $fp['category_color'] ?? '#18181b',
                                    ]) ?>
                                    <span class="text-xs text-zinc-500"><?= reading_time($fp['content']) ?> dk okuma</span>
                                </div>
                                <h3 class="text-base font-bold tracking-tight mb-2">
                                    <a href="<?= e(url('/blog/' . $fp['slug'])) ?>" class="hover:underline">
                                        <?= e($fp['title']) ?>
                                    </a>
                                </h3>
                                <?php if (!empty($fp['summary'])): ?>
                                    <p class="text-xs text-zinc-600 dark:text-zinc-400 line-clamp-2 leading-relaxed">
                                        <?= e($fp['summary']) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="mt-4 pt-3 border-t border-zinc-200/70 dark:border-zinc-800 flex items-center justify-between text-xs text-zinc-500">
                                <span><?= e(format_date($fp['published_at'] ?: $fp['created_at'])) ?></span>
                                <a href="<?= e(url('/blog/' . $fp['slug'])) ?>" class="font-semibold text-zinc-900 dark:text-zinc-100 hover:underline">Oku &rarr;</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

        <?php elseif ($section['id'] === 'latest_posts'): ?>
            <!-- SON BLOG YAZILARI (LİSTE VEYA KART GÖRÜNÜMÜ) -->
            <section class="space-y-6">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <h2 class="text-lg font-bold tracking-tight">Son Yazılar</h2>
                    <!-- Kategori Hızlı Filtreleri -->
                    <?php if (!empty($categories)): ?>
                        <div class="flex flex-wrap items-center gap-1.5">
                            <?php foreach ($categories as $cat): ?>
                                <?= View::renderPartial('components/category-badge', [
                                    'name'  => $cat['name'],
                                    'slug'  => $cat['slug'],
                                    'icon'  => $cat['icon'] ?? '',
                                    'color' => $cat['color'] ?? '#18181b',
                                ]) ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (empty($latestPosts)): ?>
                    <div class="p-8 rounded-xl border border-zinc-200 dark:border-zinc-800 text-center text-sm text-zinc-500">
                        Henüz yayınlanmış bir blog yazısı bulunmuyor.
                    </div>
                <?php elseif ($postStyle === 'grid'): ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <?php foreach ($latestPosts as $post): ?>
                            <article class="rounded-xl border border-zinc-200 dark:border-zinc-800 overflow-hidden flex flex-col bg-white dark:bg-zinc-900/40 hover:border-zinc-400 dark:hover:border-zinc-700 transition-colors">
                                <?php if (!empty($post['cover_image'])): ?>
                                    <a href="<?= e(url('/blog/' . $post['slug'])) ?>" class="block aspect-[16/9] overflow-hidden bg-zinc-100 dark:bg-zinc-900 border-b border-zinc-100 dark:border-zinc-800">
                                        <img src="<?= e(url($post['cover_image'])) ?>"
                                             alt="<?= e($post['title']) ?>"
                                             loading="lazy"
                                             class="w-full h-full object-cover">
                                    </a>
                                <?php endif; ?>
                                <div class="p-5 flex-1 flex flex-col justify-between">
                                    <div>
                                        <div class="flex items-center justify-between gap-2 mb-2.5">
                                            <?= View::renderPartial('components/category-badge', [
                                                'name'  => $post['category_name'] ?? '',
                                                'slug'  => $post['category_slug'] ?? '',
                                                'icon'  => $post['category_icon'] ?? '',
                                                'color' => $post['category_color'] ?? '#18181b',
                                            ]) ?>
                                            <span class="text-xs text-zinc-500"><?= e(format_date($post['published_at'] ?: $post['created_at'])) ?></span>
                                        </div>
                                        <h3 class="text-base font-bold tracking-tight mb-2">
                                            <a href="<?= e(url('/blog/' . $post['slug'])) ?>" class="hover:underline"><?= e($post['title']) ?></a>
                                        </h3>
                                        <?php if (!empty($post['summary'])): ?>
                                            <p class="text-xs text-zinc-600 dark:text-zinc-400 line-clamp-3 leading-relaxed"><?= e($post['summary']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mt-4 pt-3 border-t border-zinc-100 dark:border-zinc-800 flex items-center justify-between text-xs text-zinc-500">
                                        <span><i class="fa-regular fa-clock mr-1"></i><?= reading_time($post['content']) ?> dk okuma</span>
                                        <a href="<?= e(url('/blog/' . $post['slug'])) ?>" class="font-semibold text-zinc-900 dark:text-zinc-100">Devamını Oku &rarr;</a>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <!-- Minimalist Tek Sütun Okuma Listesi (Varsayılan) -->
                    <div class="divide-y divide-zinc-200 dark:divide-zinc-800/80 border-t border-b border-zinc-200 dark:border-zinc-800">
                        <?php foreach ($latestPosts as $post): ?>
                            <article class="py-6 flex flex-col sm:flex-row sm:items-start justify-between gap-5">
                                <div class="space-y-2 flex-1">
                                    <div class="flex flex-wrap items-center gap-2.5 text-xs text-zinc-500">
                                        <time><?= e(format_date($post['published_at'] ?: $post['created_at'])) ?></time>
                                        <span>&middot;</span>
                                        <span><?= reading_time($post['content']) ?> dk okuma</span>
                                        <?php if (!empty($post['category_name'])): ?>
                                            <span>&middot;</span>
                                            <?= View::renderPartial('components/category-badge', [
                                                'name'  => $post['category_name'],
                                                'slug'  => $post['category_slug'],
                                                'icon'  => $post['category_icon'] ?? '',
                                                'color' => $post['category_color'] ?? '#18181b',
                                            ]) ?>
                                        <?php endif; ?>
                                    </div>
                                    <h3 class="text-lg sm:text-xl font-bold tracking-tight">
                                        <a href="<?= e(url('/blog/' . $post['slug'])) ?>" class="hover:underline">
                                            <?= e($post['title']) ?>
                                        </a>
                                    </h3>
                                    <?php if (!empty($post['summary'])): ?>
                                        <p class="text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed">
                                            <?= e($post['summary']) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($post['cover_image'])): ?>
                                    <a href="<?= e(url('/blog/' . $post['slug'])) ?>" class="sm:w-36 sm:h-24 rounded-lg overflow-hidden border border-zinc-200 dark:border-zinc-800 shrink-0 block">
                                        <img src="<?= e(url($post['cover_image'])) ?>"
                                             alt="<?= e($post['title']) ?>"
                                             loading="lazy"
                                             class="w-full h-full object-cover">
                                    </a>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="pt-2">
                    <a href="<?= e(url('/blog')) ?>" class="inline-flex items-center gap-2 text-xs font-semibold text-zinc-900 dark:text-zinc-100 hover:underline">
                        <span>Tüm Blog Yazılarını Gör</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            </section>

        <?php elseif ($section['id'] === 'featured_projects' && is_module_active('portfolio') && !empty($featuredProjects)): ?>
            <!-- SEÇKİN PORTFOLYO PROJELERİ -->
            <section class="space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold tracking-tight">Seçkin Projeler</h2>
                    <a href="<?= e(url('/portfolio')) ?>" class="text-xs font-semibold hover:underline">Tüm Projeler &rarr;</a>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <?php foreach ($featuredProjects as $proj): ?>
                        <div class="p-5 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/40 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between gap-2 mb-2">
                                    <h3 class="text-base font-bold tracking-tight">
                                        <a href="<?= e(url('/portfolio/' . $proj['slug'])) ?>" class="hover:underline"><?= e($proj['title']) ?></a>
                                    </h3>
                                    <div class="flex items-center gap-2 text-xs">
                                        <?php if (!empty($proj['github_url'])): ?>
                                            <a href="<?= e($proj['github_url']) ?>" target="_blank" rel="noopener noreferrer" class="text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100" title="GitHub"><i class="fa-brands fa-github"></i></a>
                                        <?php endif; ?>
                                        <?php if (!empty($proj['demo_url'])): ?>
                                            <a href="<?= e($proj['demo_url']) ?>" target="_blank" rel="noopener noreferrer" class="text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100" title="Canlı Demo"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if (!empty($proj['summary'])): ?>
                                    <p class="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed mb-3"><?= e($proj['summary']) ?></p>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($proj['tech_stack'])): ?>
                                <div class="flex flex-wrap gap-1.5 pt-2">
                                    <?php foreach (array_filter(array_map('trim', explode(',', $proj['tech_stack']))) as $tech): ?>
                                        <span class="px-2 py-0.5 text-[11px] font-medium rounded border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400">
                                            <?= e($tech) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

        <?php elseif ($section['id'] === 'cv_skills_summary' && is_module_active('cv') && (!empty($skillsGrouped) || !empty($experiences))): ?>
            <!-- YETENEK & DENEYİM ÖZETİ -->
            <section class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/30 space-y-5">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-bold tracking-tight">Teknik Yetenekler & Özgeçmiş</h2>
                    <a href="<?= e(url('/cv')) ?>" class="text-xs font-semibold hover:underline">Tam Özgeçmişi İncele &rarr;</a>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <?php foreach ($skillsGrouped as $catName => $items): ?>
                        <div class="space-y-2">
                            <div class="text-xs font-bold uppercase tracking-wider text-zinc-400"><?= e($catName) ?></div>
                            <div class="flex flex-wrap gap-1.5">
                                <?php foreach ($items as $sk): ?>
                                    <span class="px-2.5 py-1 text-xs font-medium rounded border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                                        <?= e($sk['name']) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

        <?php elseif ($section['id'] === 'contact_cta' && is_module_active('contact')): ?>
            <!-- İLETİŞİM ÇAĞRI KUTUSU -->
            <section class="p-6 sm:p-8 rounded-xl border border-zinc-200 dark:border-zinc-800 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="space-y-1">
                    <h2 class="text-base font-bold">Bir proje fikriniz veya sorunuz mu var?</h2>
                    <p class="text-xs text-zinc-500">Yeni projeler, danışmanlık veya teknoloji sohbetleri için her zaman ulaşabilirsiniz.</p>
                </div>
                <a href="<?= e(url('/contact')) ?>"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90 shrink-0">
                    <i class="fa-regular fa-paper-plane"></i>
                    <span>İletişime Geç</span>
                </a>
            </section>
        <?php endif; ?>

    <?php endforeach; ?>
</div>
