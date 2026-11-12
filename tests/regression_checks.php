<?php

declare(strict_types=1);

define('BVLOG_ROOT', dirname(__DIR__));
require_once BVLOG_ROOT . '/core/Autoloader.php';
Core\Autoloader::register();
require_once BVLOG_ROOT . '/core/Helpers.php';

use Core\Database;
use Core\Request;

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

// Database::lastInsertId() bulten modellerinde ham INSERT sonrasi kullaniliyor.
if (extension_loaded('pdo_sqlite')) {
    $pdo = new PDO('sqlite::memory:', null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('CREATE TABLE t (id INTEGER PRIMARY KEY AUTOINCREMENT, v TEXT)');
    (new ReflectionProperty(Database::class, 'instance'))->setValue(null, $pdo);
    Database::query('INSERT INTO t (v) VALUES (:v)', ['v' => 'a']);
    Database::query('INSERT INTO t (v) VALUES (:v)', ['v' => 'b']);
    check(Database::lastInsertId() === 2, 'Database::lastInsertId returns id of raw insert');
    (new ReflectionProperty(Database::class, 'instance'))->setValue(null, null);
} else {
    echo "skip Database::lastInsertId (pdo_sqlite yok)\n";
}

// excerpt(): HTML'den duz metin ozeti.
check(function_exists('excerpt'), 'excerpt helper exists');
check(excerpt('<p>Kısa <b>metin</b></p>', 50) === 'Kısa metin', 'excerpt strips tags and keeps short text');
check(excerpt("<p>a&amp;b</p>\n\n<p>c</p>", 50) === 'a&b c', 'excerpt decodes entities and collapses whitespace');
$long = excerpt('<p>' . str_repeat('kelime ', 40) . '</p>', 30);
check(mb_strlen($long) <= 31 && str_ends_with($long, '…'), 'excerpt truncates to limit with ellipsis');
check(!str_contains($long, 'kelim…'), 'excerpt cuts on word boundary');
check(excerpt(str_repeat('ğ', 20), 5) === 'ğğğğğ…', 'excerpt is multibyte safe');

// Bulten baglantilari: url()/post_url() zaten mutlak adres dondurur.
$_SERVER['HTTP_HOST'] = 'bvlog.test';
$_SERVER['HTTPS'] = 'on';
$verify = url('/newsletter/verify?token=abc');
check($verify === 'https://bvlog.test/newsletter/verify?token=abc', 'url() returns single absolute address');
$newsletterFiles = [
    'plugins/newsletter/plugin.php',
    'plugins/newsletter/NewsletterMailer.php',
    'plugins/newsletter/Controllers/NewsletterAdminController.php',
];
$doublePrefix = 0;
foreach ($newsletterFiles as $file) {
    $doublePrefix += preg_match_all(
        '/base_url\(\)\s*,\s*\'\/\'\)\s*\.\s*(url|post_url)\(/',
        (string) file_get_contents(BVLOG_ROOT . '/' . $file)
    );
}
check($doublePrefix === 0, 'newsletter links do not prepend base_url() to url()/post_url()');

// Tek tikla abonelikten cikma (RFC 8058) oturumsuz POST oldugu icin CSRF'ten muaf olmali.
$newsletterPlugin = (string) file_get_contents(BVLOG_ROOT . '/plugins/newsletter/plugin.php');
check(str_contains($newsletterPlugin, "Router::csrfExempt('/newsletter/unsubscribe')"), 'newsletter registers unsubscribe as CSRF exempt');
Core\Router::csrfExempt('/ornek/yol/');
check(Core\Router::isCsrfExempt('/ornek/yol'), 'csrf exemption normalizes slashes');
check(!Core\Router::isCsrfExempt('/ornek'), 'csrf exemption matches exact path only');

// Request: dize bekleyen alanlara dizi gelirse varsayilan doner.
$_GET = ['q' => ['x'], 'page' => ['2'], 'name' => '  Ali  ', 'ids' => ['1', '2']];
$_POST = ['email' => ['a@b.c'], 'tags' => ['php']];
check(Request::get('q', '') === '', 'array query value falls back to string default');
check(Request::get('page', 1) === 1, 'array query value falls back to int default');
check(Request::get('q') === null, 'array query value falls back to null default');
check(Request::get('name', '') === 'Ali', 'string value is trimmed');
check(Request::get('ids', []) === ['1', '2'], 'array value kept when default is array');
check(Request::post('email', '') === '', 'array post value falls back to string default');
check(Request::post('tags', []) === ['php'], 'array post value kept when default is array');
check(Request::input('email', 'x') === 'x', 'input() falls back for array value');
$_GET = [];
$_POST = [];

// Okuma cubugu yol onekine degil, yazi sayfasi kancasina bagli.
$readingPlugin = (string) file_get_contents(BVLOG_ROOT . '/plugins/reading-progress/plugin.php');
check(!str_contains($readingPlugin, "'/blog/'"), 'reading progress does not depend on /blog/ prefix');
check(str_contains($readingPlugin, "'post_before_content'"), 'reading progress listens to post_before_content');

// Paper temasi: view dosyalari namespace disinda calisir, Request tam adla cagrilmali.
$paperIndex = (string) file_get_contents(BVLOG_ROOT . '/themes/paper/views/blog/index.php');
$usesRequest = preg_match('/(?<![\\\\\w])Request::/', $paperIndex) === 1;
$importsRequest = str_contains($paperIndex, 'use Core\\Request;');
check(!$usesRequest || $importsRequest, 'paper blog index does not call unqualified Request');

echo $failed === 0 ? "ALL PASSED\n" : "{$failed} FAILED\n";
exit($failed === 0 ? 0 : 1);
