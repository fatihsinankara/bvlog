<?php
use Core\View;
?>
<article class="space-y-8">
    <!-- Üst Meta & Başlık -->
    <header class="space-y-4 pb-6 border-b border-zinc-200 dark:border-zinc-800">
        <div class="flex flex-wrap items-center gap-2.5 text-xs text-zinc-500">
            <a href="<?= e(url('/blog')) ?>" class="hover:text-zinc-900 dark:hover:text-zinc-100 font-medium">&larr; Blog</a>
            <span>&middot;</span>
            <?php if (!empty($post['category_name'])): ?>
                <?= View::renderPartial('components/category-badge', [
                    'name'  => $post['category_name'],
                    'slug'  => $post['category_slug'],
                    'icon'  => $post['category_icon'] ?? '',
                    'color' => $post['category_color'] ?? '#18181b',
                ]) ?>
                <span>&middot;</span>
            <?php endif; ?>
            <time datetime="<?= e(date('Y-m-d', strtotime($post['published_at'] ?: $post['created_at']))) ?>">
                <i class="fa-regular fa-calendar mr-1"></i><?= e(format_date($post['published_at'] ?: $post['created_at'])) ?>
            </time>
            <span>&middot;</span>
            <span><i class="fa-regular fa-clock mr-1"></i><?= reading_time($post['content']) ?> dk okuma</span>
            <span>&middot;</span>
            <span><i class="fa-regular fa-eye mr-1"></i><?= (int) $post['views_count'] ?> görüntülenme</span>
        </div>

        <h1 class="text-2xl sm:text-4xl font-bold tracking-tight leading-tight text-zinc-900 dark:text-zinc-100">
            <?= e($post['title']) ?>
        </h1>

        <?php if (!empty($post['summary'])): ?>
            <p class="text-base sm:text-lg text-zinc-600 dark:text-zinc-400 leading-relaxed">
                <?= e($post['summary']) ?>
            </p>
        <?php endif; ?>
    </header>

    <!-- Öne Çıkan Görsel (1080px WebP, LCP yüksek öncelikli) -->
    <?php if (!empty($post['cover_image'])): ?>
        <figure class="rounded-xl overflow-hidden border border-zinc-200 dark:border-zinc-800 bg-zinc-100 dark:bg-zinc-900">
            <img src="<?= e(url($post['cover_image'])) ?>"
                 alt="<?= e($post['title']) ?>"
                 fetchpriority="high"
                 width="1080"
                 class="w-full h-auto object-cover">
        </figure>
    <?php endif; ?>

    <!-- Makale Öncesi Kanca (Koleksiyon / Seri Kutusu vb.) -->
    <?php \Core\Hook::doAction('post_before_content', $post); ?>

    <!-- Makale Gövdesi -->
    <div class="bvlog-article-content prose prose-zinc dark:prose-invert max-w-none">
        <?= $post['content'] ?>
    </div>

    <!-- Etiketler -->
    <?php if (!empty($tags)): ?>
        <div class="pt-6 border-t border-zinc-200 dark:border-zinc-800 flex flex-wrap items-center gap-2">
            <span class="text-xs font-semibold text-zinc-400 mr-1"><i class="fa-solid fa-hashtag mr-1"></i>Etiketler:</span>
            <?php foreach ($tags as $tag): ?>
                <span class="px-2.5 py-1 text-xs font-medium rounded border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300">
                    <?= e($tag['name']) ?>
                </span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Yazar Kutusu -->
    <div class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/70 dark:bg-zinc-900/50 flex flex-col sm:flex-row items-start sm:items-center gap-4">
        <?php $avatar = $post['author_avatar'] ?? ($author['avatar'] ?? ''); ?>
        <?php $aName = $post['author_name'] ?: ($author['name'] ?? setting('author_name', 'Yazar')); ?>
        <?php if (!empty($avatar)): ?>
            <img src="<?= e(url($avatar)) ?>" alt="<?= e($aName) ?>" loading="lazy" class="w-14 h-14 rounded-full object-cover border border-zinc-200 dark:border-zinc-800 shrink-0">
        <?php else: ?>
            <div class="w-14 h-14 rounded-full border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 flex items-center justify-center text-lg font-bold shrink-0">
                <?= e(mb_substr($aName, 0, 1)) ?>
            </div>
        <?php endif; ?>
        <div class="space-y-1 flex-1">
            <div class="flex items-center gap-2">
                <h3 class="text-sm font-bold"><?= e($aName) ?></h3>
                <span class="text-xs text-zinc-500"><?= e($post['author_title'] ?: setting('author_title', '')) ?></span>
            </div>
            <p class="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed">
                <?= e($post['author_bio'] ?: setting('author_bio', '')) ?>
            </p>
        </div>
    </div>

    <!-- Yorumlar Bölümü -->
    <?php if (is_module_active('comments')): ?>
        <section id="comments" class="pt-8 border-t border-zinc-200 dark:border-zinc-800 space-y-6">
            <h2 class="text-lg font-bold tracking-tight">
                Yorumlar (<?= count($comments) ?>)
            </h2>

            <?php if (!empty($comments)): ?>
                <div class="space-y-4">
                    <?php foreach ($comments as $c): ?>
                        <div class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/40 space-y-2">
                            <div class="flex items-center justify-between text-xs">
                                <strong class="font-semibold text-zinc-900 dark:text-zinc-100"><?= e($c['author_name']) ?></strong>
                                <time class="text-zinc-500"><?= e(format_date($c['created_at'], true)) ?></time>
                            </div>
                            <p class="text-sm text-zinc-700 dark:text-zinc-300 leading-relaxed whitespace-pre-line"><?= e($c['content']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-xs text-zinc-500">Henüz yorum yapılmamış. İlk görüşü siz paylaşın!</p>
            <?php endif; ?>

            <!-- Yorum Formu -->
            <form action="<?= e(post_url($post) . '/comment') ?>" method="POST" class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/40 dark:bg-zinc-900/30 space-y-4">
                <?= csrf_field() ?>
                <input type="text" name="website_hp" value="" class="hidden" tabindex="-1" autocomplete="off">
                <h3 class="text-sm font-bold">Yorum Bırakın</h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold mb-1.5">Adınız Soyadınız *</label>
                        <input type="text" name="author_name" required maxlength="100"
                               class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 focus:outline-none focus:border-zinc-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1.5">E-posta Adresiniz * (Yayınlanmaz)</label>
                        <input type="email" name="author_email" required maxlength="150"
                               class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 focus:outline-none focus:border-zinc-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1.5">Yorumunuz *</label>
                    <textarea name="content" rows="4" required maxlength="2000"
                              class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 focus:outline-none focus:border-zinc-500"></textarea>
                </div>

                <button type="submit" class="px-5 py-2 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90 transition-opacity">
                    Yorumu Gönder
                </button>
            </form>
        </section>
    <?php endif; ?>
</article>
