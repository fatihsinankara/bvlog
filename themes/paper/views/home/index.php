<?php
use Core\View;

$authorName   = $author['name'] ?? setting('author_name', 'Yazar');
$authorTitle  = $author['role_title'] ?? setting('author_title', '');
$authorBio    = $author['bio'] ?? setting('author_bio', '');
$authorAvatar = $author['avatar'] ?? '';
?>

<div class="space-y-14">
    <!-- Minimal Yazar İmzası / Selamlama -->
    <section class="space-y-4 pb-10 border-b border-zinc-200 dark:border-zinc-800">
        <div class="flex items-center gap-4">
            <?php if (!empty($authorAvatar)): ?>
                <img src="<?= e(url($authorAvatar)) ?>"
                     alt="<?= e($authorName) ?>"
                     fetchpriority="high"
                     width="56" height="56"
                     class="w-14 h-14 rounded-full object-cover border border-zinc-200 dark:border-zinc-800 shrink-0">
            <?php endif; ?>
            <div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-zinc-900 dark:text-zinc-100">
                    <?= e($authorName) ?>
                </h1>
                <?php if (!empty($authorTitle)): ?>
                    <p class="text-xs text-zinc-400 font-medium mt-0.5"><?= e($authorTitle) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($authorBio)): ?>
            <p class="text-sm sm:text-base text-zinc-600 dark:text-zinc-400 leading-relaxed max-w-2xl">
                <?= e($authorBio) ?>
            </p>
        <?php endif; ?>

        <div class="pt-1 flex flex-wrap items-center gap-4 text-xs font-medium">
            <a href="<?= e(url('/blog')) ?>" class="text-zinc-900 dark:text-zinc-100 underline underline-offset-4 decoration-2 hover:opacity-75">
                Tüm Makaleleri Oku &rarr;
            </a>
            <?php if (is_module_active('contact')): ?>
                <span class="text-zinc-300 dark:text-zinc-700">&middot;</span>
                <a href="<?= e(url('/contact')) ?>" class="text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100">
                    İletişime Geç
                </a>
            <?php endif; ?>
            <span class="text-zinc-300 dark:text-zinc-700">&middot;</span>
            <a href="<?= e(url('/rss.xml')) ?>" target="_blank" class="text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 flex items-center gap-1">
                <i class="fa-solid fa-rss text-[10px]"></i>
                <span>RSS</span>
            </a>
        </div>
    </section>

    <!-- Öne Çıkan Notlar (Varsa) -->
    <?php if (!empty($featuredPosts)): ?>
        <section class="space-y-4">
            <div class="text-[11px] font-bold uppercase tracking-wider text-zinc-400">
                Öne Çıkan Yazılar
            </div>
            <div class="grid grid-cols-1 gap-3">
                <?php foreach ($featuredPosts as $fPost): ?>
                    <article class="p-4 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50/40 dark:bg-zinc-900/30 hover:border-zinc-400 dark:hover:border-zinc-600 transition-colors">
                        <div class="flex items-center gap-2 mb-1.5 text-xs text-zinc-400">
                            <?php if (!empty($fPost['category_name'])): ?>
                                <span class="text-zinc-700 dark:text-zinc-300 font-semibold"><?= e($fPost['category_name']) ?></span>
                                <span>&middot;</span>
                            <?php endif; ?>
                            <span><?= e(format_date($fPost['published_at'] ?: $fPost['created_at'])) ?></span>
                            <span>&middot;</span>
                            <span><?= reading_time($fPost['content']) ?> dk</span>
                        </div>
                        <h2 class="text-base sm:text-lg font-bold text-zinc-900 dark:text-zinc-100">
                            <a href="<?= e(post_url($fPost)) ?>" class="hover:underline">
                                <?= e($fPost['title']) ?>
                            </a>
                        </h2>
                        <?php if (!empty($fPost['summary'])): ?>
                            <p class="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400 mt-1 line-clamp-2 leading-relaxed">
                                <?= e($fPost['summary']) ?>
                            </p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- Son Yazılar (Editoryal Kronolojik Akış) -->
    <section class="space-y-6">
        <div class="flex items-center justify-between pb-2 border-b border-zinc-200 dark:border-zinc-800">
            <h2 class="text-xs font-bold uppercase tracking-wider text-zinc-400">
                Son Yazılar
            </h2>
            <a href="<?= e(url('/blog')) ?>" class="text-xs font-medium text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100 hover:underline">
                Tüm Arşiv &rarr;
            </a>
        </div>

        <?php if (!empty($latestPosts)): ?>
            <div class="divide-y divide-zinc-100 dark:divide-zinc-900">
                <?php foreach ($latestPosts as $post): ?>
                    <article class="py-5 first:pt-0 group">
                        <div class="flex flex-col sm:flex-row sm:items-baseline justify-between gap-1 mb-1.5">
                            <h3 class="text-base sm:text-lg font-bold text-zinc-900 dark:text-zinc-100 group-hover:text-zinc-600 dark:group-hover:text-zinc-300 transition-colors">
                                <a href="<?= e(post_url($post)) ?>">
                                    <?= e($post['title']) ?>
                                </a>
                            </h3>
                            <time class="text-xs font-mono text-zinc-400 shrink-0 sm:ml-4" datetime="<?= e(date('Y-m-d', strtotime($post['published_at'] ?: $post['created_at']))) ?>">
                                <?= e(format_date($post['published_at'] ?: $post['created_at'])) ?>
                            </time>
                        </div>
                        <?php if (!empty($post['summary'])): ?>
                            <p class="text-xs sm:text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed line-clamp-2 mb-2">
                                <?= e($post['summary']) ?>
                            </p>
                        <?php endif; ?>
                        <div class="flex items-center gap-3 text-[11px] text-zinc-400">
                            <?php if (!empty($post['category_name'])): ?>
                                <a href="<?= e(url('/category/' . $post['category_slug'])) ?>" class="hover:underline font-medium text-zinc-600 dark:text-zinc-400">
                                    #<?= e($post['category_name']) ?>
                                </a>
                                <span>&middot;</span>
                            <?php endif; ?>
                            <span><?= reading_time($post['content']) ?> dk okuma</span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="py-10 text-center text-xs text-zinc-400">
                Henüz yayınlanmış yazı bulunmuyor.
            </div>
        <?php endif; ?>
    </section>

    <!-- Seçkin Portfolyo Projeleri (Modül Aktifse Sade Liste) -->
    <?php if (is_module_active('portfolio') && !empty($featuredProjects)): ?>
        <section class="space-y-4 pt-4 border-t border-zinc-200 dark:border-zinc-800">
            <div class="flex items-center justify-between pb-1">
                <h2 class="text-xs font-bold uppercase tracking-wider text-zinc-400">
                    Seçkin Projeler
                </h2>
                <a href="<?= e(url('/portfolio')) ?>" class="text-xs font-medium text-zinc-500 hover:underline">
                    Tümü &rarr;
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <?php foreach ($featuredProjects as $proj): ?>
                    <a href="<?= e(url('/portfolio/' . $proj['slug'])) ?>"
                       class="p-4 rounded-lg border border-zinc-200 dark:border-zinc-800 hover:border-zinc-400 dark:hover:border-zinc-600 transition-colors block space-y-1">
                        <div class="font-bold text-sm text-zinc-900 dark:text-zinc-100">
                            <?= e($proj['title']) ?>
                        </div>
                        <?php if (!empty($proj['summary'])): ?>
                            <p class="text-xs text-zinc-500 line-clamp-2 leading-relaxed">
                                <?= e($proj['summary']) ?>
                            </p>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>
