<?php

namespace App\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\Project;
use App\Models\Setting;
use Core\Controller;
use Core\Response;

class SeoController extends Controller
{
    public function sitemap(): void
    {
        $urls = [];
        $urls[] = ['loc' => url('/'), 'priority' => '1.0', 'changefreq' => 'daily', 'lastmod' => date('Y-m-d')];
        $urls[] = ['loc' => url('/blog'), 'priority' => '0.9', 'changefreq' => 'daily', 'lastmod' => date('Y-m-d')];

        if (is_module_active('portfolio')) {
            $urls[] = ['loc' => url('/portfolio'), 'priority' => '0.8', 'changefreq' => 'weekly', 'lastmod' => date('Y-m-d')];
            foreach (Project::publishedList() as $proj) {
                $urls[] = [
                    'loc'        => url('/portfolio/' . $proj['slug']),
                    'priority'   => '0.7',
                    'changefreq' => 'monthly',
                    'lastmod'    => date('Y-m-d', strtotime($proj['updated_at'] ?: $proj['created_at'])),
                ];
            }
        }

        if (is_module_active('cv')) {
            $urls[] = ['loc' => url('/cv'), 'priority' => '0.8', 'changefreq' => 'monthly', 'lastmod' => date('Y-m-d')];
        }

        if (is_module_active('contact')) {
            $urls[] = ['loc' => url('/contact'), 'priority' => '0.6', 'changefreq' => 'yearly', 'lastmod' => date('Y-m-d')];
        }

        foreach (Category::all() as $cat) {
            $urls[] = [
                'loc'        => url('/category/' . $cat['slug']),
                'priority'   => '0.7',
                'changefreq' => 'weekly',
                'lastmod'    => date('Y-m-d', strtotime($cat['created_at'])),
            ];
        }

        foreach (Post::publishedList(500, 0) as $post) {
            $urls[] = [
                'loc'        => post_url($post),
                'priority'   => '0.8',
                'changefreq' => 'weekly',
                'lastmod'    => date('Y-m-d', strtotime($post['updated_at'] ?: $post['created_at'])),
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>' . e($u['loc']) . "</loc>\n";
            $xml .= '    <lastmod>' . e($u['lastmod']) . "</lastmod>\n";
            $xml .= '    <changefreq>' . e($u['changefreq']) . "</changefreq>\n";
            $xml .= '    <priority>' . e($u['priority']) . "</priority>\n";
            $xml .= "  </url>\n";
        }
        $xml .= '</urlset>';

        Response::xml($xml);
    }

    public function rss(): void
    {
        $siteTitle = Setting::get('site_title', 'Bvlog');
        $siteDesc = Setting::get('site_description', 'Minimalist Kişisel Blog');
        $posts = Post::publishedList(25, 0);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
        $xml .= "  <channel>\n";
        $xml .= '    <title>' . e($siteTitle) . "</title>\n";
        $xml .= '    <link>' . e(url('/')) . "</link>\n";
        $xml .= '    <description>' . e($siteDesc) . "</description>\n";
        $xml .= "    <language>tr</language>\n";
        $xml .= '    <atom:link href="' . e(url('/rss.xml')) . '" rel="self" type="application/rss+xml" />' . "\n";

        foreach ($posts as $post) {
            $postUrl = post_url($post);
            $pubDate = date(DATE_RSS, strtotime($post['published_at'] ?: $post['created_at']));
            $summary = $post['summary'] ?: mb_substr(strip_tags($post['content']), 0, 240);

            $xml .= "    <item>\n";
            $xml .= '      <title>' . e($post['title']) . "</title>\n";
            $xml .= '      <link>' . e($postUrl) . "</link>\n";
            $xml .= '      <guid isPermaLink="true">' . e($postUrl) . "</guid>\n";
            $xml .= '      <pubDate>' . e($pubDate) . "</pubDate>\n";
            $xml .= '      <description>' . e($summary) . "</description>\n";
            $xml .= "    </item>\n";
        }

        $xml .= "  </channel>\n</rss>";
        Response::xml($xml);
    }

    public function robots(): void
    {
        $allow = Setting::get('seo_allow_index', '1') === '1';
        $lines = ["User-agent: *"];
        if ($allow) {
            $lines[] = "Allow: /";
            $lines[] = "Disallow: /admin";
            $lines[] = "Disallow: /install";
        } else {
            $lines[] = "Disallow: /";
        }
        $lines[] = "";
        $lines[] = "Sitemap: " . url('/sitemap.xml');

        Response::text(implode("\n", $lines));
    }
}
