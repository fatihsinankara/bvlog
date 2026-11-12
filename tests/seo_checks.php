<?php

declare(strict_types=1);

define('BVLOG_ROOT', dirname(__DIR__));
require_once BVLOG_ROOT . '/core/Autoloader.php';
Core\Autoloader::register();
require_once BVLOG_ROOT . '/core/Helpers.php';

use Core\Seo;

$failed = 0;

function check(bool $ok, string $label): void
{
    global $failed;
    if ($ok) {
        echo "ok  {$label}\n";
        return;
    }
    $failed++;
    echo "FAIL {$label}\n";
}

function request(string $uri): void
{
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = $uri;
    $query = (string) (parse_url($uri, PHP_URL_QUERY) ?? '');
    $_GET = [];
    parse_str($query, $_GET);
}

$_SERVER['HTTP_HOST'] = 'bvlog.test';
$_SERVER['HTTPS'] = 'on';

// J-1: JSON-LD cannot close its own <script> block.
$payload = ['headline' => 'Baslik </script><script>alert(1)</script>', 'note' => "a & b 'c' \"d\""];
$json = Seo::jsonLd($payload);
check(!str_contains($json, '<') && !str_contains($json, '>'), 'json-ld hex-escapes angle brackets');
check(!str_contains($json, '&') && !str_contains($json, "'"), 'json-ld hex-escapes ampersand and apostrophe');
check(json_decode($json, true) === $payload, 'json-ld round-trips to the same data');
check(Seo::jsonLd(['t' => "bozuk \xB1 utf8"]) !== '', 'json-ld survives invalid utf-8');

request('/blog/yazi');
$head = Seo::renderHead(['title' => 'X', 'schema' => $payload]);
check(substr_count(strtolower($head), '</script>') === 1, 'rendered head has a single closing script tag');

// S-1: canonical ignores tracking parameters and keeps only page > 1.
request('/blog/yazi?utm_source=x&fbclid=y');
check(Seo::canonicalUrl() === 'https://bvlog.test/blog/yazi', 'canonical drops tracking parameters');
request('/blog?q=test&page=3&ref=z');
check(Seo::canonicalUrl() === 'https://bvlog.test/blog?page=3', 'canonical keeps page above one');
request('/blog?page=1');
check(Seo::canonicalUrl() === 'https://bvlog.test/blog', 'canonical drops page=1');
request('/blog?page=abc');
check(Seo::canonicalUrl() === 'https://bvlog.test/blog', 'canonical drops non-numeric page');
request('/blog/?page[]=2');
check(Seo::canonicalUrl() === 'https://bvlog.test/blog', 'canonical ignores array page and trailing slash');
request('/');
check(Seo::canonicalUrl() === 'https://bvlog.test/', 'canonical of home page');
request('/blog?utm_medium=mail');
check(str_contains(Seo::renderHead([]), '<link rel="canonical" href="https://bvlog.test/blog">'), 'renderHead uses clean canonical');

// Pagination links never point at page=1.
request('/category/php?page=2&utm_source=x');
check(Seo::pageUrl(1) === 'https://bvlog.test/category/php', 'page one link has no page parameter');
check(Seo::pageUrl(3) === 'https://bvlog.test/category/php?page=3', 'later page link keeps page parameter');
check(Seo::pageUrl(1, ['q' => 'a b']) === 'https://bvlog.test/category/php?q=a+b', 'search term kept on page one');
check(Seo::pageUrl(2, ['q' => '']) === 'https://bvlog.test/category/php?page=2', 'empty search term dropped');

// S-9: document language comes from config/app.php.
$config = require BVLOG_ROOT . '/config/app.php';
check(app_locale() === str_replace('_', '-', (string) $config['locale']), 'app_locale follows config locale');

// Bulk updates must not move posts.updated_at (sitemap lastmod, dateModified).
$viewCounter = (string) file_get_contents(BVLOG_ROOT . '/core/ViewCounter.php');
check(str_contains($viewCounter, 'updated_at = updated_at'), 'view counter flush keeps updated_at');
$migrator = (string) file_get_contents(BVLOG_ROOT . '/core/Migrator.php');
check(str_contains($migrator, 'CHAR_LENGTH(`content`) / 1200)), updated_at = updated_at'), 'reading time backfill keeps updated_at');

echo $failed === 0 ? "ALL PASSED\n" : "{$failed} FAILED\n";
exit($failed === 0 ? 0 : 1);
