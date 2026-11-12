<?php

namespace Core;

class Seo
{
    /** JSON-LD ciktisinin <script> blogunu kapatamamasi icin gereken bayraklar. */
    private const JSON_LD_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        | JSON_INVALID_UTF8_SUBSTITUTE;

    /**
     * Istegin kanonik adresi: sorgu dizesi atilir, yalnizca 1'den buyuk
     * `page` degeri korunur. utm_*, fbclid gibi parametreler ayri adres uretmez.
     */
    public static function canonicalUrl(): string
    {
        $url = url(Request::path());
        $page = self::pageParam();
        return $page !== null && $page > 1 ? $url . '?page=' . $page : $url;
    }

    /**
     * `?page=1` iceren GET isteklerini parametresiz adrese 301 ile yonlendirir.
     * Diger sorgu parametreleri (or. arama terimi) korunur.
     */
    public static function redirectFirstPage(): void
    {
        if (Request::method() !== 'GET' || self::pageParam() !== 1) {
            return;
        }
        $path = Request::path();
        if (str_starts_with($path, '/admin') || str_starts_with($path, '/install')) {
            return;
        }

        $query = $_GET;
        unset($query['page']);
        $qs = http_build_query($query);
        Response::redirect($path . ($qs !== '' ? '?' . $qs : ''), 301);
    }

    /**
     * Gecerli yolun N. sayfasi. 1. sayfada `page` parametresi yazilmaz;
     * bos degerli parametreler atilir.
     */
    public static function pageUrl(int $page, array $query = []): string
    {
        $query = array_filter($query, static fn ($v) => $v !== null && $v !== '');
        unset($query['page']);
        if ($page > 1) {
            $query['page'] = $page;
        }
        $qs = http_build_query($query);
        return url(Request::path()) . ($qs !== '' ? '?' . $qs : '');
    }

    public static function jsonLd(array $data): string
    {
        return (string) json_encode($data, self::JSON_LD_FLAGS);
    }

    private static function pageParam(): ?int
    {
        $page = $_GET['page'] ?? null;
        if (!is_string($page) || !preg_match('/^[1-9][0-9]{0,8}$/', $page)) {
            return null;
        }
        return (int) $page;
    }

    public static function renderHead(array $meta = []): string
    {
        $siteTitle = setting('site_title', 'Bvlog');
        $siteTagline = setting('site_tagline', 'Kişisel Blog & Portfolyo');
        $siteDesc = setting('site_description', 'Minimalist yazılım, teknoloji ve kişisel notlar.');
        $defaultOgImage = setting('og_image', '');
        $allowIndex = setting('seo_allow_index', '1') === '1';

        $title = !empty($meta['title'])
            ? $meta['title'] . ' — ' . $siteTitle
            : $siteTitle . ($siteTagline ? ' — ' . $siteTagline : '');

        $title = Hook::applyFilters('page_title', $title, $meta);

        $description = !empty($meta['description']) ? $meta['description'] : $siteDesc;
        $description = mb_substr(trim(strip_tags((string) $description)), 0, 165);

        $canonical = $meta['canonical'] ?? self::canonicalUrl();
        $ogType = $meta['type'] ?? 'website';
        $ogImage = !empty($meta['image']) ? url($meta['image']) : (!empty($defaultOgImage) ? url($defaultOgImage) : '');
        $robots = $allowIndex ? ($meta['robots'] ?? 'index, follow') : 'noindex, nofollow';

        $out = [];
        $out[] = '<title>' . e($title) . '</title>';
        $out[] = '<meta name="description" content="' . e($description) . '">';
        $out[] = '<meta name="robots" content="' . e($robots) . '">';
        $out[] = '<link rel="canonical" href="' . e($canonical) . '">';

        // RSS Feed link
        $out[] = '<link rel="alternate" type="application/rss+xml" title="' . e($siteTitle) . ' RSS" href="' . e(url('/rss.xml')) . '">';

        // Open Graph
        $out[] = '<meta property="og:site_name" content="' . e($siteTitle) . '">';
        $out[] = '<meta property="og:type" content="' . e($ogType) . '">';
        $out[] = '<meta property="og:title" content="' . e($title) . '">';
        $out[] = '<meta property="og:description" content="' . e($description) . '">';
        $out[] = '<meta property="og:url" content="' . e($canonical) . '">';
        if ($ogImage !== '') {
            $out[] = '<meta property="og:image" content="' . e($ogImage) . '">';
        }

        // Twitter Card
        $out[] = '<meta name="twitter:card" content="summary_large_image">';
        $out[] = '<meta name="twitter:title" content="' . e($title) . '">';
        $out[] = '<meta name="twitter:description" content="' . e($description) . '">';
        if ($ogImage !== '') {
            $out[] = '<meta name="twitter:image" content="' . e($ogImage) . '">';
        }

        // Schema.org JSON-LD
        if (!empty($meta['schema'])) {
            $out[] = '<script type="application/ld+json">' . self::jsonLd($meta['schema']) . '</script>';
        } else {
            // Varsayılan WebSite şeması
            $defaultSchema = [
                '@context' => 'https://schema.org',
                '@type'    => 'WebSite',
                'name'     => $siteTitle,
                'url'      => url('/'),
                'potentialAction' => [
                    '@type'       => 'SearchAction',
                    'target'      => url('/blog?q={search_term_string}'),
                    'query-input' => 'required name=search_term_string',
                ],
            ];
            $out[] = '<script type="application/ld+json">' . self::jsonLd($defaultSchema) . '</script>';
        }

        return implode("\n    ", $out) . "\n";
    }

    public static function buildArticleSchema(array $post, array $author): array
    {
        $siteTitle = setting('site_title', 'Bvlog');
        $postUrl = post_url($post);
        $image = !empty($post['cover_image']) ? url($post['cover_image']) : '';

        $schema = [
            '@context'      => 'https://schema.org',
            '@type'         => 'BlogPosting',
            'headline'      => $post['seo_title'] ?: $post['title'],
            'description'   => $post['seo_description'] ?: ($post['summary'] ?: mb_substr(strip_tags($post['content']), 0, 160)),
            'datePublished' => date('c', strtotime($post['published_at'] ?: $post['created_at'])),
            'dateModified'  => date('c', strtotime($post['updated_at'] ?: $post['created_at'])),
            'author'        => [
                '@type' => 'Person',
                'name'  => $author['name'] ?? setting('author_name', 'Yazar'),
                'url'   => url('/'),
            ],
            'publisher'     => [
                '@type' => 'Organization',
                'name'  => $siteTitle,
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id'   => $postUrl,
            ],
        ];

        if ($image !== '') {
            $schema['image'] = [$image];
        }

        return $schema;
    }

    public static function buildPersonSchema(array $author, array $skills = []): array
    {
        $sameAs = array_values(array_filter([
            setting('social_github'),
            setting('social_linkedin'),
            setting('social_twitter'),
            setting('social_instagram'),
            setting('social_youtube'),
        ]));

        return [
            '@context'    => 'https://schema.org',
            '@type'       => 'Person',
            'name'        => $author['name'] ?? setting('author_name', 'Yazar'),
            'jobTitle'    => $author['role_title'] ?? setting('author_title', 'Yazılım Geliştirici'),
            'description' => $author['bio'] ?? setting('author_bio', ''),
            'url'         => url('/cv'),
            'sameAs'      => $sameAs,
            'knowsAbout'  => array_map(fn($s) => $s['name'], $skills),
        ];
    }
}
