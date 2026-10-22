<div class="grid grid-cols-1 md:grid-cols-12 gap-10 items-start">
    <div class="md:col-span-5 space-y-5">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight">İletişim</h1>
            <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-2 leading-relaxed">
                Yeni projeler, teknik danışmanlık, iş birliği teklifleri veya sorularınız için aşağıdaki formu doldurabilir ya da doğrudan iletişim kanallarımdan ulaşabilirsiniz.
            </p>
        </div>

        <div class="space-y-3 pt-2 text-sm">
            <?php if ($em = setting('social_email')): ?>
                <div class="flex items-center gap-3 p-3.5 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/40">
                    <i class="fa-regular fa-envelope text-zinc-500 w-5 text-center"></i>
                    <div>
                        <div class="text-[11px] font-bold uppercase text-zinc-400">E-posta</div>
                        <a href="mailto:<?= e($em) ?>" class="font-medium hover:underline"><?= e($em) ?></a>
                    </div>
                </div>
            <?php endif; ?>

            <div class="flex flex-wrap gap-2 pt-2">
                <?php if ($gh = setting('social_github')): ?>
                    <a href="<?= e($gh) ?>" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg border border-zinc-200 dark:border-zinc-800 text-xs font-semibold hover:bg-zinc-100 dark:hover:bg-zinc-900">
                        <i class="fa-brands fa-github"></i><span>GitHub</span>
                    </a>
                <?php endif; ?>
                <?php if ($li = setting('social_linkedin')): ?>
                    <a href="<?= e($li) ?>" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg border border-zinc-200 dark:border-zinc-800 text-xs font-semibold hover:bg-zinc-100 dark:hover:bg-zinc-900">
                        <i class="fa-brands fa-linkedin"></i><span>LinkedIn</span>
                    </a>
                <?php endif; ?>
                <?php if ($tw = setting('social_twitter')): ?>
                    <a href="<?= e($tw) ?>" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg border border-zinc-200 dark:border-zinc-800 text-xs font-semibold hover:bg-zinc-100 dark:hover:bg-zinc-900">
                        <i class="fa-brands fa-x-twitter"></i><span>X / Twitter</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="md:col-span-7">
        <form action="<?= e(url('/contact')) ?>" method="POST" class="p-6 sm:p-8 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/40 dark:bg-zinc-900/30 space-y-4">
            <?= csrf_field() ?>
            <input type="text" name="company_hp" value="" class="hidden" tabindex="-1" autocomplete="off">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold mb-1.5">Adınız Soyadınız *</label>
                    <input type="text" name="name" required maxlength="100"
                           class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 focus:outline-none focus:border-zinc-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1.5">E-posta Adresiniz *</label>
                    <input type="email" name="email" required maxlength="150"
                           class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 focus:outline-none focus:border-zinc-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1.5">Konu *</label>
                <input type="text" name="subject" required maxlength="200"
                       class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 focus:outline-none focus:border-zinc-500">
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1.5">Mesajınız *</label>
                <textarea name="message" rows="5" required maxlength="4000"
                          class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 focus:outline-none focus:border-zinc-500"></textarea>
            </div>

            <?php \Core\Hook::doAction('render_captcha', 'contact'); ?>

            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 text-xs font-semibold hover:opacity-90 transition-opacity">
                <i class="fa-regular fa-paper-plane"></i>
                <span>Mesajı Gönder</span>
            </button>
        </form>
    </div>
</div>
