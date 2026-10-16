<?php

use Core\Hook;

Hook::addAction('footer_scripts', function () {
    echo <<<'HTML'
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.prose pre').forEach(function (pre) {
        pre.style.position = 'relative';
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'absolute top-2 right-2 px-2 py-1 text-xs font-medium rounded border border-zinc-700 bg-zinc-800 text-zinc-200 hover:bg-zinc-700 transition-colors';
        btn.innerHTML = '<i class="fa-regular fa-copy mr-1"></i>Kopyala';
        btn.addEventListener('click', function () {
            var code = pre.querySelector('code') ? pre.querySelector('code').innerText : pre.innerText;
            navigator.clipboard.writeText(code).then(function () {
                btn.innerHTML = '<i class="fa-solid fa-check mr-1"></i>Kopyalandı';
                setTimeout(function () {
                    btn.innerHTML = '<i class="fa-regular fa-copy mr-1"></i>Kopyala';
                }, 2000);
            });
        });
        pre.appendChild(btn);
    });
});
</script>
HTML;
});
