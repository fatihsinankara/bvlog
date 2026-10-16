<?php

use Core\Hook;
use Core\Request;

Hook::addAction('footer_scripts', function () {
    $path = Request::path();
    if (!str_starts_with($path, '/blog/') || $path === '/blog') {
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
