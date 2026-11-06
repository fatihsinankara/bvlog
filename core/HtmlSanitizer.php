<?php

namespace Core;

class HtmlSanitizer
{
    private const DROP_TAGS = [
        'script', 'style', 'object', 'embed', 'applet', 'form', 'meta', 'base',
        'link', 'svg', 'math', 'template', 'noscript', 'iframe', 'frame', 'frameset',
        'textarea', 'input', 'button', 'select', 'option',
    ];

    private const ALLOWED_TAGS = [
        'p', 'br', 'hr', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'del', 'ins', 'sub', 'sup', 'mark',
        'blockquote', 'pre', 'code', 'kbd', 'abbr',
        'ul', 'ol', 'li',
        'a', 'img', 'figure', 'figcaption',
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'caption',
        'div', 'span', 'section', 'article',
        'details', 'summary',
        'iframe',
    ];

    private const IFRAME_HOSTS = [
        'youtube.com',
        'www.youtube.com',
        'youtube-nocookie.com',
        'www.youtube-nocookie.com',
        'player.vimeo.com',
        'open.spotify.com',
        'codepen.io',
        'platform.twitter.com',
    ];

    public static function clean(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $wrapped = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body><div id="bvlog-sanitizer-root">'
            . $html
            . '</div></body></html>';
        $dom->loadHTML($wrapped, LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $dom->getElementById('bvlog-sanitizer-root');
        if (!$root) {
            return '';
        }

        $cleanDom = new \DOMDocument('1.0', 'UTF-8');
        $cleanRoot = $cleanDom->createElement('div');
        $cleanDom->appendChild($cleanRoot);
        self::copyChildren($root, $cleanRoot, $cleanDom);

        $out = '';
        foreach ($cleanRoot->childNodes as $child) {
            $out .= $cleanDom->saveHTML($child);
        }

        return self::parseSmartEmbeds($out);
    }

    private static function copyChildren(\DOMNode $source, \DOMNode $target, \DOMDocument $cleanDom): void
    {
        foreach (iterator_to_array($source->childNodes) as $child) {
            self::copyNode($child, $target, $cleanDom);
        }
    }

    private static function copyNode(\DOMNode $node, \DOMNode $target, \DOMDocument $cleanDom): void
    {
        if ($node instanceof \DOMText) {
            $target->appendChild($cleanDom->createTextNode($node->nodeValue ?? ''));
            return;
        }

        if (!$node instanceof \DOMElement) {
            return;
        }

        $tag = strtolower($node->tagName);
        if (in_array($tag, self::DROP_TAGS, true) && $tag !== 'iframe') {
            return;
        }

        if (!in_array($tag, self::ALLOWED_TAGS, true)) {
            self::copyChildren($node, $target, $cleanDom);
            return;
        }

        if ($tag === 'iframe') {
            $iframe = self::buildIframe($node, $cleanDom);
            if ($iframe) {
                $target->appendChild($iframe);
            }
            return;
        }

        $el = $cleanDom->createElement($tag);
        self::copyAttributes($node, $el, $tag);
        $target->appendChild($el);
        if (!in_array($tag, ['br', 'hr', 'img'], true)) {
            self::copyChildren($node, $el, $cleanDom);
        }
    }

    private static function copyAttributes(\DOMElement $from, \DOMElement $to, string $tag): void
    {
        $allowed = match ($tag) {
            'a' => ['href', 'title', 'target', 'rel', 'class', 'id'],
            'img' => ['src', 'alt', 'title', 'width', 'height', 'loading', 'class'],
            'td', 'th' => ['colspan', 'rowspan', 'class'],
            'pre', 'code', 'div', 'span', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p' => ['class', 'id', 'style'],
            default => ['class', 'id'],
        };

        if ($tag === 'div') {
            $allowed[] = 'contenteditable';
        }

        foreach ($allowed as $name) {
            if (!$from->hasAttribute($name)) {
                continue;
            }
            $value = trim($from->getAttribute($name));
            $safe = self::filterAttribute($tag, $name, $value);
            if ($safe !== null && $safe !== '') {
                $to->setAttribute($name, $safe);
            }
        }

        if ($tag === 'a' && strtolower($to->getAttribute('target')) === '_blank') {
            $to->setAttribute('rel', 'noopener noreferrer');
        }
    }

    private static function filterAttribute(string $tag, string $name, string $value): ?string
    {
        if ($value === '' || preg_match('/[\x00-\x1F]/', $value)) {
            return null;
        }

        return match ($name) {
            'href' => self::safeUrl($value, false),
            'src' => self::safeUrl($value, $tag === 'img'),
            'title', 'alt' => mb_substr($value, 0, 300),
            'target' => $value === '_blank' ? '_blank' : null,
            'rel' => preg_match('/^(noopener|noreferrer|nofollow)( (noopener|noreferrer|nofollow))*$/', $value) ? $value : null,
            'class' => self::safeClass($value),
            'id' => preg_match('/^[A-Za-z][A-Za-z0-9_:\-.]{0,80}$/', $value) ? $value : null,
            'style' => self::safeStyle($value),
            'width', 'height', 'colspan', 'rowspan' => ctype_digit($value) && (int) $value > 0 && (int) $value < 5000 ? $value : null,
            'loading' => in_array($value, ['lazy', 'eager'], true) ? $value : null,
            'contenteditable' => $value === 'false' ? 'false' : null,
            default => null,
        };
    }

    private static function safeClass(string $value): ?string
    {
        if (strlen($value) > 500 || !preg_match('/^[A-Za-z0-9_\-:\s.\/\[\]%]+$/', $value)) {
            return null;
        }
        return $value;
    }

    private static function safeStyle(string $value): ?string
    {
        if (strlen($value) > 240) {
            return null;
        }
        if (preg_match('/expression|url\s*\(|javascript:|vbscript:|@import|behavior|-moz-binding/i', $value)) {
            return null;
        }
        if (!preg_match('/^[a-z0-9#%(),.\-\s:;]+$/i', $value)) {
            return null;
        }
        return $value;
    }

    private static function safeUrl(string $value, bool $allowDataImage): ?string
    {
        $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $decoded = trim($decoded);
        $collapsed = strtolower((string) preg_replace('/[\x00-\x20]+/', '', $decoded));

        if (
            str_starts_with($collapsed, 'javascript:') ||
            str_starts_with($collapsed, 'vbscript:') ||
            str_starts_with($collapsed, 'data:')
        ) {
            if ($allowDataImage && str_starts_with($collapsed, 'data:image/') && !str_contains($collapsed, 'data:image/svg')) {
                return mb_substr($decoded, 0, 200000);
            }
            return null;
        }

        if (str_starts_with($decoded, '#') || str_starts_with($decoded, '/') || str_starts_with($decoded, './') || str_starts_with($decoded, '../')) {
            if (str_starts_with($decoded, '//') || str_starts_with($decoded, '/\\')) {
                return null;
            }
            return $decoded;
        }

        if (preg_match('#^mailto:#i', $decoded)) {
            return filter_var(substr($decoded, 7), FILTER_VALIDATE_EMAIL) ? $decoded : null;
        }

        if (!preg_match('#^https?://#i', $decoded)) {
            return null;
        }

        $host = parse_url($decoded, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            return null;
        }

        return $decoded;
    }

    private static function buildIframe(\DOMElement $node, \DOMDocument $cleanDom): ?\DOMElement
    {
        $src = self::safeUrl(trim($node->getAttribute('src')), false);
        if ($src === null || !self::iframeHostAllowed($src)) {
            return null;
        }

        $iframe = $cleanDom->createElement('iframe');
        $iframe->setAttribute('src', $src);
        $iframe->setAttribute('loading', 'lazy');
        $class = self::safeClass($node->getAttribute('class'));
        if ($class) {
            $iframe->setAttribute('class', $class);
        }
        if ($node->hasAttribute('allowfullscreen')) {
            $iframe->setAttribute('allowfullscreen', 'allowfullscreen');
        }
        $allow = strtolower(trim($node->getAttribute('allow')));
        if ($allow !== '' && preg_match('/^[a-z0-9;\- ]+$/', $allow)) {
            $iframe->setAttribute('allow', $allow);
        }
        return $iframe;
    }

    private static function iframeHostAllowed(string $src): bool
    {
        $host = strtolower((string) parse_url($src, PHP_URL_HOST));
        return in_array($host, self::IFRAME_HOSTS, true);
    }

    public static function parseSmartEmbeds(string $html): string
    {
        $html = preg_replace_callback(
            '/<p>\s*(?:<a[^>]*>)?https?:\/\/(?:www\.)?(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})(?:[^\s<]*)(?:<\/a>)?\s*<\/p>/i',
            function ($m) {
                $id = htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
                return '<div class="my-6 aspect-video w-full overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-950"><iframe src="https://www.youtube-nocookie.com/embed/' . $id . '" class="w-full h-full border-0" allowfullscreen loading="lazy"></iframe></div>';
            },
            $html
        );

        $html = preg_replace_callback(
            '/<p>\s*(?:<a[^>]*>)?https?:\/\/open\.spotify\.com\/(track|album|playlist|episode)\/([a-zA-Z0-9]+)(?:[^\s<]*)(?:<\/a>)?\s*<\/p>/i',
            function ($m) {
                $type = htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
                $id = htmlspecialchars($m[2], ENT_QUOTES, 'UTF-8');
                return '<div class="my-6 w-full overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-800"><iframe src="https://open.spotify.com/embed/' . $type . '/' . $id . '" class="w-full h-20 border-0" loading="lazy" allow="encrypted-media"></iframe></div>';
            },
            $html
        );

        $html = preg_replace_callback(
            '/<p>\s*(?:<a[^>]*>)?https?:\/\/codepen\.io\/([a-zA-Z0-9_-]+)\/pen\/([a-zA-Z0-9_-]+)(?:[^\s<]*)(?:<\/a>)?\s*<\/p>/i',
            function ($m) {
                $user = htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
                $pen = htmlspecialchars($m[2], ENT_QUOTES, 'UTF-8');
                return '<div class="my-6 aspect-video w-full overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-800"><iframe src="https://codepen.io/' . $user . '/embed/' . $pen . '?default-tab=result" class="w-full h-full border-0" loading="lazy"></iframe></div>';
            },
            $html
        );

        $html = preg_replace_callback(
            '/<p>\s*(?:<a[^>]*>)?(https?:\/\/(?:www\.)?(?:twitter\.com|x\.com)\/([a-zA-Z0-9_]+)\/status\/([0-9]+))(?:[^\s<]*)(?:<\/a>)?\s*<\/p>/i',
            function ($m) {
                $url = htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
                $user = htmlspecialchars($m[2], ENT_QUOTES, 'UTF-8');
                return '<div class="my-6 p-4 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/60 not-prose"><div class="flex items-center justify-between"><span class="text-sm font-semibold text-zinc-900 dark:text-zinc-100"><i class="fa-brands fa-x-twitter mr-2"></i>@' . $user . ' gönderisi</span><a href="' . $url . '" target="_blank" rel="noopener noreferrer" class="text-xs font-medium px-2.5 py-1 rounded border border-zinc-300 dark:border-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-800 transition-colors">X üzerinde görüntüle <i class="fa-solid fa-arrow-up-right-from-square ml-1"></i></a></div></div>';
            },
            $html
        );

        $html = preg_replace_callback(
            '/<p>\s*(?:<a[^>]*>)?(https?:\/\/gist\.github\.com\/([a-zA-Z0-9_-]+)\/([a-f0-9]+))(?:[^\s<]*)(?:<\/a>)?\s*<\/p>/i',
            function ($m) {
                $url = htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
                $user = htmlspecialchars($m[2], ENT_QUOTES, 'UTF-8');
                $gistId = htmlspecialchars($m[3], ENT_QUOTES, 'UTF-8');
                return '<div class="my-6 p-4 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/60 not-prose"><div class="flex items-center justify-between"><span class="text-sm font-semibold text-zinc-900 dark:text-zinc-100"><i class="fa-brands fa-github mr-2"></i>GitHub Gist (@' . $user . ' / ' . substr($gistId, 0, 8) . ')</span><a href="' . $url . '" target="_blank" rel="noopener noreferrer" class="text-xs font-medium px-2.5 py-1 rounded border border-zinc-300 dark:border-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-800 transition-colors">Gist\'i Aç <i class="fa-solid fa-arrow-up-right-from-square ml-1"></i></a></div></div>';
            },
            $html
        );

        return $html ?? '';
    }
}
