<?php

namespace App\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\Project;
use App\Models\Setting;
use Core\Cache;
use Core\Controller;
use Core\Hook;
use Core\Response;
use Core\Router;

class SeoController extends Controller
{
    /** Sitemap protokolunun tek dosya icin izin verdigi en fazla adres. */
    private const SITEMAP_MAX_URLS = 50000;

    public function sitemap(): void
    {
        // Indeksleme kapaliyken harita yayimlanmaz; robots.txt de ona isaret etmez.
        if (!self::indexingAllowed()) {
            Router::abort(404);
        }

        if (Cache::isFeedsEnabled()) {
            $xml = Cache::remember('feeds:sitemap_xml', function (): string {
                return self::generateSitemapXml();
            }, null, 'feeds', 'XML Site Haritası (/sitemap.xml)');
        } else {
            $xml = self::generateSitemapXml();
        }

        Response::xml($xml);
    }

    public function rss(): void
    {
        if (Cache::isFeedsEnabled()) {
            $xml = Cache::remember('feeds:rss_xml', function (): string {
                return self::generateRssXml();
            }, null, 'feeds', 'RSS İçerik Beslemesi (/rss.xml)');
        } else {
            $xml = self::generateRssXml();
        }

        Response::xml($xml, 200, 'application/rss+xml');
    }

    public static function generateSitemapXml(): string
    {
        $posts = Post::sitemapEntries(self::SITEMAP_MAX_URLS);
        $latestPost = null;
        $postUrls = [];
        foreach ($posts as $post) {
            $modified = $post['updated_at'] ?: $post['created_at'];
            $latestPost = self::laterDate($latestPost, $modified);
            $postUrls[] = ['loc' => post_url($post), 'lastmod' => $modified];
        }

        // Ana sayfa ve blog listesi, son yazi degistiginde degisir.
        $urls = [];
        $urls[] = ['loc' => url('/'), 'lastmod' => $latestPost];
        $urls[] = ['loc' => url('/blog'), 'lastmod' => $latestPost];

        if (is_module_active('portfolio')) {
            $projectUrls = [];
            $latestProject = null;
            foreach (Project::publishedList() as $proj) {
                $modified = $proj['updated_at'] ?: $proj['created_at'];
                $latestProject = self::laterDate($latestProject, $modified);
                $projectUrls[] = ['loc' => url('/portfolio/' . $proj['slug']), 'lastmod' => $modified];
            }
            $urls[] = ['loc' => url('/portfolio'), 'lastmod' => $latestProject];
            array_push($urls, ...$projectUrls);
        }

        // CV ve iletisim sayfalari icin guvenilir bir degisiklik tarihi yok;
        // her istekte bugunu yazmak yerine lastmod hic verilmez.
        if (is_module_active('cv')) {
            $urls[] = ['loc' => url('/cv'), 'lastmod' => null];
        }

        if (is_module_active('contact')) {
            $urls[] = ['loc' => url('/contact'), 'lastmod' => null];
        }

        foreach (Category::sitemapEntries() as $cat) {
            $urls[] = [
                'loc'     => url('/category/' . $cat['slug']),
                'lastmod' => $cat['last_post_at'] ?: $cat['created_at'],
            ];
        }

        array_push($urls, ...$postUrls);

        // Eklentiler kendi adreslerini ekleyebilir: ['loc' => ..., 'lastmod' => ?string]
        $urls = Hook::applyFilters('sitemap_urls', $urls);
        $urls = is_array($urls) ? $urls : [];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        $seen = [];
        foreach ($urls as $u) {
            $loc = is_array($u) ? ($u['loc'] ?? null) : null;
            if (!is_string($loc) || $loc === '' || isset($seen[$loc])) {
                continue;
            }
            if (count($seen) >= self::SITEMAP_MAX_URLS) {
                break;
            }
            $seen[$loc] = true;

            $xml .= "  <url>\n";
            $xml .= '    <loc>' . e($loc) . "</loc>\n";
            $lastmod = self::formatLastmod($u['lastmod'] ?? null);
            if ($lastmod !== null) {
                $xml .= '    <lastmod>' . e($lastmod) . "</lastmod>\n";
            }
            $xml .= "  </url>\n";
        }
        $xml .= '</urlset>';

        return $xml;
    }

    private static function indexingAllowed(): bool
    {
        return Setting::get('seo_allow_index', '1') === '1';
    }

    private static function laterDate(?string $current, ?string $candidate): ?string
    {
        if (empty($candidate)) {
            return $current;
        }
        if ($current === null) {
            return $candidate;
        }
        return strtotime($candidate) > strtotime($current) ? $candidate : $current;
    }

    private static function formatLastmod(mixed $value): ?string
    {
        if (!is_string($value) || $value === '') {
            return null;
        }
        $ts = strtotime($value);
        return $ts === false ? null : date('Y-m-d', $ts);
    }

    public static function generateRssXml(): string
    {
        $siteTitle = Setting::get('site_title', 'Bvlog');
        $siteDesc = Setting::get('site_description', 'Minimalist Kişisel Blog');
        $posts = Post::publishedList(25, 0);

        $lastBuild = null;
        foreach ($posts as $post) {
            $lastBuild = self::laterDate($lastBuild, $post['updated_at'] ?: ($post['published_at'] ?: $post['created_at']));
        }
        $lastBuildTs = $lastBuild !== null ? strtotime($lastBuild) : false;

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
        $xml .= "  <channel>\n";
        $xml .= '    <title>' . e($siteTitle) . "</title>\n";
        $xml .= '    <link>' . e(url('/')) . "</link>\n";
        $xml .= '    <description>' . e($siteDesc) . "</description>\n";
        $xml .= '    <language>' . e(app_locale()) . "</language>\n";
        if ($lastBuildTs !== false) {
            $xml .= '    <lastBuildDate>' . e(date(DATE_RSS, $lastBuildTs)) . "</lastBuildDate>\n";
        }
        $xml .= '    <atom:link href="' . e(url('/rss.xml')) . '" rel="self" type="application/rss+xml" />' . "\n";

        foreach ($posts as $post) {
            $postUrl = post_url($post);
            $pubDate = date(DATE_RSS, strtotime($post['published_at'] ?: $post['created_at']));
            $summary = $post['summary'] ?? '';

            $xml .= "    <item>\n";
            $xml .= '      <title>' . e($post['title']) . "</title>\n";
            $xml .= '      <link>' . e($postUrl) . "</link>\n";
            $xml .= '      <guid isPermaLink="true">' . e($postUrl) . "</guid>\n";
            $xml .= '      <pubDate>' . e($pubDate) . "</pubDate>\n";
            foreach (($post['categories'] ?? []) as $cat) {
                if (!empty($cat['name'])) {
                    $xml .= '      <category>' . e($cat['name']) . "</category>\n";
                }
            }
            $xml .= '      <description>' . e($summary) . "</description>\n";
            $xml .= "    </item>\n";
        }

        $xml .= "  </channel>\n</rss>";

        return $xml;
    }

    public function robots(): void
    {
        // Indeksleme kapaliyken tarama engellenmez: arama motoru sayfalardaki
        // noindex isaretini ancak sayfayi tarayabilirse gorur.
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /install',
        ];
        if (self::indexingAllowed()) {
            $lines[] = '';
            $lines[] = 'Sitemap: ' . url('/sitemap.xml');
        }

        Response::text(implode("\n", $lines) . "\n");
    }
}
