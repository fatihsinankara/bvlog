<?php

namespace Core;

class HtmlSanitizer
{
    public static function clean(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        // 1. İframe etiketlerinde sadece güvenilir embed kaynaklarına izin ver
        $html = preg_replace_callback('/<iframe\b([^>]*)><\/iframe>/is', function ($matches) {
            $attrs = $matches[1];
            if (preg_match('/src\s*=\s*(["\'])(https:\/\/(?:www\.)?(?:youtube\.com|youtube-nocookie\.com|player\.vimeo\.com|open\.spotify\.com|codepen\.io|platform\.twitter\.com)\/[^"\']+)\1/i', $attrs, $srcMatch)) {
                $safeSrc = htmlspecialchars($srcMatch[2], ENT_QUOTES, 'UTF-8');
                return '<iframe src="' . $safeSrc . '" class="w-full h-full border-0 rounded-lg" allowfullscreen loading="lazy"></iframe>';
            }
            return '';
        }, $html);

        // 2. Tehlikeli script, style, object, embed, applet, meta, form, svg, math, template etiketlerini tamamen kaldır
        $dangerTags = 'script|style|object|embed|applet|form|meta|base|link|svg|math|template|noscript';
        $dangerPattern = '/<(' . $dangerTags . ')\b[^>]*>.*?<\/\1>/is';
        while (preg_match($dangerPattern, $html)) {
            $html = preg_replace($dangerPattern, '', $html);
        }
        $html = preg_replace('/<(' . $dangerTags . ')\b[^>]*\/?>/is', '', $html);

        // 3. on* event handler niteliklerini (onload, onerror, onclick vb.) temizle (boşluklu veya bölü işaretli)
        $onPattern = '/(?:\s|\/)+on[a-z0-9_-]+\s*=\s*(["\']).*?\1/is';
        while (preg_match($onPattern, $html)) {
            $html = preg_replace($onPattern, '', $html);
        }
        $html = preg_replace('/(?:\s|\/)+on[a-z0-9_-]+\s*=\s*[^\s>]+/is', '', $html);

        // 4. Tehlikeli URL protokollerini (javascript:, vbscript:, data:text/html vb.) denetle
        $html = preg_replace_callback('/(href|src|xlink:href|action)\s*=\s*(["\'])(.*?)\2/is', function ($matches) {
            $attr = $matches[1];
            $quote = $matches[2];
            $val = trim($matches[3]);

            $decoded = html_entity_decode($val, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $cleanProto = strtolower(preg_replace('/[\x00-\x20\s]+/', '', $decoded));

            if (
                str_starts_with($cleanProto, 'javascript:') ||
                str_starts_with($cleanProto, 'vbscript:') ||
                (str_starts_with($cleanProto, 'data:') && !str_starts_with($cleanProto, 'data:image/'))
            ) {
                return $attr . '=' . $quote . '#' . $quote;
            }

            return $matches[0];
        }, $html);

        return self::parseSmartEmbeds($html);
    }

    public static function parseSmartEmbeds(string $html): string
    {
        // Tek başına paragraf içinde duran YouTube linklerini responsive embed yap
        $html = preg_replace_callback(
            '/<p>\s*(?:<a[^>]*>)?https?:\/\/(?:www\.)?(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})(?:[^\s<]*)(?:<\/a>)?\s*<\/p>/i',
            function ($m) {
                $id = htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
                return '<div class="my-6 aspect-video w-full overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-950"><iframe src="https://www.youtube-nocookie.com/embed/' . $id . '" class="w-full h-full border-0" allowfullscreen loading="lazy"></iframe></div>';
            },
            $html
        );

        // Tek başına paragraf içinde duran Spotify linklerini embed yap
        $html = preg_replace_callback(
            '/<p>\s*(?:<a[^>]*>)?https?:\/\/open\.spotify\.com\/(track|album|playlist|episode)\/([a-zA-Z0-9]+)(?:[^\s<]*)(?:<\/a>)?\s*<\/p>/i',
            function ($m) {
                $type = htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
                $id = htmlspecialchars($m[2], ENT_QUOTES, 'UTF-8');
                return '<div class="my-6 w-full overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-800"><iframe src="https://open.spotify.com/embed/' . $type . '/' . $id . '" class="w-full h-20 border-0" loading="lazy" allow="encrypted-media"></iframe></div>';
            },
            $html
        );

        // Tek başına paragraf içinde duran CodePen linklerini embed yap
        $html = preg_replace_callback(
            '/<p>\s*(?:<a[^>]*>)?https?:\/\/codepen\.io\/([a-zA-Z0-9_-]+)\/pen\/([a-zA-Z0-9_-]+)(?:[^\s<]*)(?:<\/a>)?\s*<\/p>/i',
            function ($m) {
                $user = htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
                $pen = htmlspecialchars($m[2], ENT_QUOTES, 'UTF-8');
                return '<div class="my-6 aspect-video w-full overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-800"><iframe src="https://codepen.io/' . $user . '/embed/' . $pen . '?default-tab=result" class="w-full h-full border-0" loading="lazy"></iframe></div>';
            },
            $html
        );

        // Tek başına paragraf içinde duran Twitter / X linklerini şık kart yap
        $html = preg_replace_callback(
            '/<p>\s*(?:<a[^>]*>)?(https?:\/\/(?:www\.)?(?:twitter\.com|x\.com)\/([a-zA-Z0-9_]+)\/status\/([0-9]+))(?:[^\s<]*)(?:<\/a>)?\s*<\/p>/i',
            function ($m) {
                $url = htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
                $user = htmlspecialchars($m[2], ENT_QUOTES, 'UTF-8');
                return '<div class="my-6 p-4 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/60 not-prose"><div class="flex items-center justify-between"><span class="text-sm font-semibold text-zinc-900 dark:text-zinc-100"><i class="fa-brands fa-x-twitter mr-2"></i>@' . $user . ' gönderisi</span><a href="' . $url . '" target="_blank" rel="noopener noreferrer" class="text-xs font-medium px-2.5 py-1 rounded border border-zinc-300 dark:border-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-800 transition-colors">X üzerinde görüntüle <i class="fa-solid fa-arrow-up-right-from-square ml-1"></i></a></div></div>';
            },
            $html
        );

        // Tek başına paragraf içinde duran GitHub Gist linklerini şık kart yap
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

        return $html;
    }
}
