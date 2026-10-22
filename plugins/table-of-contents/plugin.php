<?php

use Core\Hook;

/**
 * Otomatik İçindekiler Tablosu (TOC) Eklentisi
 *
 * Blog yazılarındaki H2 ve H3 başlıklarını otomatik tarayarak
 * daraltılabilir, şık ve pürüzsüz kaydırmalı bir İçindekiler fihristi oluşturur.
 */

Hook::addAction('post_before_content', function ($post) {
    echo '<div id="bvlog-toc-wrapper" class="print:hidden"></div>';
});

Hook::addAction('footer_scripts', function () {
    echo <<<'HTML'
<script>
(function () {
    var wrapper = document.getElementById('bvlog-toc-wrapper');
    if (!wrapper) return;

    var article = document.querySelector('.bvlog-article-content');
    if (!article) return;

    var headings = article.querySelectorAll('h2, h3');
    if (headings.length < 2) return;

    function slugify(text) {
        var trMap = { 'ç': 'c', 'Ç': 'c', 'ğ': 'g', 'Ğ': 'g', 'ı': 'i', 'İ': 'i', 'ö': 'o', 'Ö': 'o', 'ş': 's', 'Ş': 's', 'ü': 'u', 'Ü': 'u' };
        var clean = text.replace(/[çÇğĞıİöÖşŞüÜ]/g, function (match) {
            return trMap[match] || match;
        });
        return clean.toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .trim()
            .replace(/\s+/g, '-');
    }

    var listHtml = '';
    headings.forEach(function (heading, index) {
        if (!heading.id) {
            var slug = slugify(heading.textContent);
            heading.id = slug ? (slug + '-' + (index + 1)) : ('bolum-' + (index + 1));
        }

        var isH3 = heading.tagName.toLowerCase() === 'h3';
        var linkClasses = isH3
            ? 'bvlog-toc-link flex items-center gap-1.5 ml-4 pl-2.5 text-xs text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 py-1 rounded border-l border-zinc-200 dark:border-zinc-800 transition-colors'
            : 'bvlog-toc-link flex items-center gap-2 text-xs sm:text-sm text-zinc-700 dark:text-zinc-300 hover:text-zinc-950 dark:hover:text-zinc-100 font-medium py-1 px-2 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800/60 transition-colors';

        var prefixIcon = isH3
            ? '<i class="fa-solid fa-angle-right text-[9px] text-zinc-400 shrink-0"></i>'
            : '<span class="w-1.5 h-1.5 rounded-full bg-zinc-400 dark:bg-zinc-500 shrink-0"></span>';

        listHtml += '<a href="#' + heading.id + '" data-target="' + heading.id + '" class="' + linkClasses + '">'
            + prefixIcon
            + '<span class="truncate">' + heading.textContent.trim() + '</span>'
            + '</a>';
    });

    var isCollapsed = localStorage.getItem('bvlog_toc_collapsed') === '1';

    wrapper.innerHTML = ''
        + '<div class="my-6 p-4 sm:p-5 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/70 dark:bg-zinc-900/40 text-sm">'
        + '  <div class="flex items-center justify-between cursor-pointer select-none" id="bvlog-toc-toggle" role="button" aria-expanded="' + (!isCollapsed) + '">'
        + '    <div class="flex items-center gap-2 font-bold text-zinc-900 dark:text-zinc-100">'
        + '      <i class="fa-solid fa-list-ul text-xs text-zinc-400"></i>'
        + '      <span>İçindekiler</span>'
        + '      <span class="text-xs font-normal text-zinc-400 font-mono">(' + headings.length + ' bölüm)</span>'
        + '    </div>'
        + '    <button type="button" class="text-xs text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100 font-medium flex items-center gap-1.5 transition-colors">'
        + '      <span id="bvlog-toc-toggle-text">' + (isCollapsed ? 'Göster' : 'Gizle') + '</span>'
        + '      <i id="bvlog-toc-toggle-icon" class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200" style="' + (isCollapsed ? 'transform: rotate(-90deg);' : '') + '"></i>'
        + '    </button>'
        + '  </div>'
        + '  <nav id="bvlog-toc-list" class="mt-3.5 pt-3 border-t border-zinc-200/80 dark:border-zinc-800/80 space-y-0.5" style="' + (isCollapsed ? 'display: none;' : '') + '">'
        +      listHtml
        + '  </nav>'
        + '</div>';

    // Daraltma / Genişletme Tetikleyicisi
    var toggleBtn = document.getElementById('bvlog-toc-toggle');
    var navList = document.getElementById('bvlog-toc-list');
    var toggleText = document.getElementById('bvlog-toc-toggle-text');
    var toggleIcon = document.getElementById('bvlog-toc-toggle-icon');

    if (toggleBtn && navList) {
        toggleBtn.addEventListener('click', function () {
            var currentlyHidden = navList.style.display === 'none';
            if (currentlyHidden) {
                navList.style.display = 'block';
                toggleText.textContent = 'Gizle';
                toggleIcon.style.transform = 'rotate(0deg)';
                localStorage.setItem('bvlog_toc_collapsed', '0');
            } else {
                navList.style.display = 'none';
                toggleText.textContent = 'Göster';
                toggleIcon.style.transform = 'rotate(-90deg)';
                localStorage.setItem('bvlog_toc_collapsed', '1');
            }
        });
    }

    // Pürüzsüz & Başlık Ofsetli Kaydırma (Header'ın başlığı örtmesini engeller)
    wrapper.querySelectorAll('.bvlog-toc-link').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            var targetId = this.getAttribute('data-target');
            var targetEl = document.getElementById(targetId);
            if (targetEl) {
                var headerOffset = 80;
                var elementPosition = targetEl.getBoundingClientRect().top;
                var offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
                if (history.replaceState) {
                    history.replaceState(null, null, '#' + targetId);
                }
            }
        });
    });

    // Aktif Başlık Takipçisi (Scroll Spy)
    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    var currentId = entry.target.id;
                    wrapper.querySelectorAll('.bvlog-toc-link').forEach(function (l) {
                        if (l.getAttribute('data-target') === currentId) {
                            l.classList.add('text-blue-600', 'dark:text-blue-400', 'font-semibold', 'bg-blue-50/60', 'dark:bg-blue-950/30');
                        } else {
                            l.classList.remove('text-blue-600', 'dark:text-blue-400', 'font-semibold', 'bg-blue-50/60', 'dark:bg-blue-950/30');
                        }
                    });
                }
            });
        }, {
            rootMargin: '0px 0px -70% 0px',
            threshold: 0
        });

        headings.forEach(function (h) {
            observer.observe(h);
        });
    }
})();
</script>
HTML;
});
