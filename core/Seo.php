<?php

namespace Core;

class Seo
{
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

        $canonical = $meta['canonical'] ?? current_url();
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
            $jsonLd = json_encode($meta['schema'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            $out[] = '<script type="application/ld+json">' . $jsonLd . '</script>';
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
            $jsonLd = json_encode($defaultSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $out[] = '<script type="application/ld+json">' . $jsonLd . '</script>';
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
