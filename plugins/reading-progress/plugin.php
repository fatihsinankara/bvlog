<?php

use Core\Hook;

// Yazi adresi kalici baglanti yapisina gore /blog/ ile baslamayabilir;
// bu yuzden yol yerine yazi sayfasinin calistirdigi kancaya bakilir.
$bvlogReadingProgressOnPost = false;

Hook::addAction('post_before_content', function () use (&$bvlogReadingProgressOnPost) {
    $bvlogReadingProgressOnPost = true;
});

Hook::addAction('footer_scripts', function () use (&$bvlogReadingProgressOnPost) {
    if (!$bvlogReadingProgressOnPost) {
        return;
    }

    echo <<<'HTML'
<div id="bvlog-reading-bar" class="fixed top-0 left-0 z-50 h-0.5 bg-zinc-900 dark:bg-zinc-100 transition-all duration-100" style="width: 0%;"></div>
<script>
document.addEventListener('scroll', function () {
    var bar = document.getElementById('bvlog-reading-bar');
    if (!bar) return;
    var scrollTop = window.scrollY || document.documentElement.scrollTop;
    var docHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
    var pct = docHeight > 0 ? Math.min(100, Math.max(0, (scrollTop / docHeight) * 100)) : 0;
    bar.style.width = pct + '%';
}, { passive: true });
</script>
HTML;
});
