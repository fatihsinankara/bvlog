<?php
use Core\View;
?>
<article class="space-y-10">
    <!-- Üst Gezinme & Meta Bilgileri -->
    <header class="space-y-4 pb-8 border-b border-zinc-200 dark:border-zinc-800">
        <div class="flex flex-wrap items-center gap-2 text-xs text-zinc-400">
            <a href="<?= e(url('/blog')) ?>" class="text-zinc-900 dark:text-zinc-100 font-semibold hover:underline">&larr; Yazılar</a>
            <span>&middot;</span>
            <?php if (!empty($post['category_name'])): ?>
                <a href="<?= e(url('/category/' . $post['category_slug'])) ?>" class="hover:underline font-medium text-zinc-600 dark:text-zinc-300">
                    #<?= e($post['category_name']) ?>
                </a>
                <span>&middot;</span>
            <?php endif; ?>
            <time datetime="<?= e(date('Y-m-d', strtotime($post['published_at'] ?: $post['created_at']))) ?>">
                <?= e(format_date($post['published_at'] ?: $post['created_at'])) ?>
            </time>
            <span>&middot;</span>
            <span><?= reading_time($post['content']) ?> dk okuma</span>
            <span>&middot;</span>
            <span><?= (int) $post['views_count'] ?> okuma</span>
        </div>

        <h1 class="text-2xl sm:text-4xl font-black tracking-tight leading-tight text-zinc-900 dark:text-zinc-100">
            <?= e($post['title']) ?>
        </h1>

        <?php if (!empty($post['summary'])): ?>
            <p class="text-base sm:text-lg text-zinc-600 dark:text-zinc-400 leading-relaxed">
                <?= e($post['summary']) ?>
            </p>
        <?php endif; ?>
    </header>

    <!-- Kapak Görseli (Gölgesiz, Düz Kenarlık) -->
    <?php if (!empty($post['cover_image'])): ?>
        <figure class="rounded-lg overflow-hidden border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
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
    <div class="bvlog-article-content prose prose-zinc dark:prose-invert max-w-none text-zinc-800 dark:text-zinc-200">
        <?= $post['content'] ?>
    </div>

    <!-- Etiketler & Paylaşım Çubuğu -->
    <div class="pt-8 border-t border-zinc-200 dark:border-zinc-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-xs">
        <!-- Etiketler -->
        <div class="flex flex-wrap items-center gap-1.5">
            <?php if (!empty($tags)): ?>
                <?php foreach ($tags as $tag): ?>
                    <span class="px-2 py-0.5 rounded border border-zinc-200 dark:border-zinc-800 text-zinc-600 dark:text-zinc-400">
                        #<?= e($tag['name']) ?>
                    </span>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Sade Paylaşım Butonları -->
        <div class="flex items-center gap-3 shrink-0 text-xs" x-data="{ copied: false }">
            <button type="button"
                    @click="navigator.clipboard.writeText(window.location.href); copied = true; setTimeout(() => copied = false, 2000)"
                    class="text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100 flex items-center gap-1.5 transition-colors">
                <i class="fa-solid" :class="copied ? 'fa-check text-emerald-600' : 'fa-link'"></i>
                <span x-text="copied ? 'Kopyalandı!' : 'Bağlantıyı Kopyala'"></span>
            </button>
            <span class="text-zinc-300 dark:text-zinc-700">&middot;</span>
            <a href="https://twitter.com/intent/tweet?text=<?= urlencode($post['title']) ?>&url=<?= urlencode(url(Request::path())) ?>"
               target="_blank" rel="noopener noreferrer"
               class="text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100 flex items-center gap-1.5 transition-colors">
                <i class="fa-brands fa-x-twitter"></i>
                <span>Paylaş</span>
            </a>
        </div>
    </div>

    <!-- Yazar İmzası (Gölgesiz, Düz Kutu) -->
    <?php if (!empty($author)): ?>
        <div class="p-5 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/30 flex items-center gap-4 text-xs">
            <?php if (!empty($author['avatar'])): ?>
                <img src="<?= e(url($author['avatar'])) ?>" alt="<?= e($author['name']) ?>" class="w-12 h-12 rounded-full object-cover border border-zinc-200 dark:border-zinc-800 shrink-0">
            <?php endif; ?>
            <div class="space-y-1">
                <div class="font-bold text-sm text-zinc-900 dark:text-zinc-100"><?= e($author['name']) ?></div>
                <?php if (!empty($author['bio'])): ?>
                    <p class="text-zinc-600 dark:text-zinc-400 leading-relaxed"><?= e($author['bio']) ?></p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Yorumlar Bölümü -->
    <?php if (is_module_active('comments')): ?>
        <section id="comments" class="pt-8 border-t border-zinc-200 dark:border-zinc-800 space-y-8">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100">
                    Yorumlar (<?= count($comments) ?>)
                </h3>
            </div>

            <!-- Onaylanmış Yorumlar -->
            <?php if (!empty($comments)): ?>
                <div class="divide-y divide-zinc-100 dark:divide-zinc-900 space-y-4">
                    <?php foreach ($comments as $c): ?>
                        <div class="pt-4 first:pt-0 space-y-1.5 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-zinc-900 dark:text-zinc-100"><?= e($c['author_name']) ?></span>
                                <time class="text-zinc-400 font-mono text-[11px]"><?= e(format_date($c['created_at'])) ?></time>
                            </div>
                            <p class="text-zinc-700 dark:text-zinc-300 leading-relaxed whitespace-pre-line"><?= e($c['content']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-xs text-zinc-400 py-3">Bu yazıya henüz yorum yapılmamış. İlk yorumu siz bırakın.</p>
            <?php endif; ?>

            <!-- Minimalist Yorum Formu -->
            <form action="<?= e(post_url($post) . '/comment') ?>" method="POST" class="p-5 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50/30 dark:bg-zinc-900/20 space-y-4">
                <?= csrf_field() ?>
                <div class="text-xs font-bold text-zinc-900 dark:text-zinc-100 uppercase tracking-wider">Yorum Gönder</div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold mb-1">Adınız *</label>
                        <input type="text" name="author_name" required maxlength="100"
                               class="w-full px-3 py-2 text-xs rounded border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 focus:outline-none focus:border-zinc-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">E-posta Adresiniz * <span class="text-zinc-400 font-normal">(Yayınlanmaz)</span></label>
                        <input type="email" name="author_email" required maxlength="150"
                               class="w-full px-3 py-2 text-xs rounded border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 focus:outline-none focus:border-zinc-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1">Yorumunuz *</label>
                    <textarea name="content" rows="4" required maxlength="2000"
                              placeholder="Düşüncelerinizi paylaşın..."
                              class="w-full px-3 py-2 text-xs rounded border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 focus:outline-none focus:border-zinc-500 leading-relaxed"></textarea>
                </div>

                <button type="submit"
                        class="px-5 py-2 rounded bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90 transition-opacity">
                    Yorumu Yayınla
                </button>
            </form>
        </section>
    <?php endif; ?>
</article>
