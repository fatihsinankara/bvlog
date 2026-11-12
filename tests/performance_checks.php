<?php

declare(strict_types=1);

define('BVLOG_ROOT', dirname(__DIR__));
require_once BVLOG_ROOT . '/core/Autoloader.php';
Core\Autoloader::register();
require_once BVLOG_ROOT . '/core/Helpers.php';

use Core\ImageProcessor;
use Core\ViewCounter;

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

$_SERVER['HTTP_HOST'] = 'bvlog.test';
$_SERVER['HTTPS'] = 'on';

// P-11: bots, empty user agents and prefetches are not counted as views.
$chrome = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36';
check(ViewCounter::isHumanRequest(['HTTP_USER_AGENT' => $chrome]), 'desktop browser counted');
check(ViewCounter::isHumanRequest(['HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148']), 'mobile browser counted');
check(!ViewCounter::isHumanRequest(['HTTP_USER_AGENT' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)']), 'googlebot skipped');
check(!ViewCounter::isHumanRequest(['HTTP_USER_AGENT' => 'facebookexternalhit/1.1']), 'link preview skipped');
check(!ViewCounter::isHumanRequest(['HTTP_USER_AGENT' => 'curl/8.4.0']), 'curl skipped');
check(!ViewCounter::isHumanRequest(['HTTP_USER_AGENT' => 'Mozilla/5.0 HeadlessChrome/120.0']), 'headless browser skipped');
check(!ViewCounter::isHumanRequest([]), 'missing user agent skipped');
check(!ViewCounter::isHumanRequest(['HTTP_USER_AGENT' => $chrome, 'HTTP_SEC_PURPOSE' => 'prefetch;prerender']), 'speculative prefetch skipped');

// P-5: image dimensions come only from files under storage/uploads.
$dir = BVLOG_ROOT . '/storage/uploads/perf-check-' . bin2hex(random_bytes(3));
mkdir($dir, 0755, true);
$png = $dir . '/pixel.png';
// 3x2 PNG
file_put_contents($png, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAMAAAACCAYAAACddGYaAAAAEUlEQVR4nGP4z8DwHwYZkDkAm6wL9ZWU4/AAAAAASUVORK5CYII='));
$rel = substr($png, strlen(BVLOG_ROOT));

try {
    check(ImageProcessor::dimensions($rel) === [3, 2], 'local upload dimensions read');
    check(ImageProcessor::dimensions('https://bvlog.test' . $rel) === [3, 2], 'absolute local url dimensions read');
    check(ImageProcessor::dimensions('/storage/uploads/../../config/app.php') === null, 'path traversal rejected');
    check(ImageProcessor::dimensions('/config/app.php') === null, 'non-upload path rejected');
    check(ImageProcessor::dimensions('/storage/uploads/missing.webp') === null, 'missing file gives null');
    check(ImageProcessor::sizeAttributes($rel, 1080) === 'width="3" height="2"', 'size attributes from file');
    check(ImageProcessor::sizeAttributes('/storage/uploads/missing.webp', 1080) === 'width="1080"', 'size attributes fall back to width');

    $html = '<p>a</p><img src="' . $rel . '" alt="x"><img src="https://cdn.example/x.jpg" loading="eager" width="10">';
    $out = ImageProcessor::enhanceContentImages($html);
    check(substr_count($out, 'decoding="async"') === 2, 'content images decode async');
    check(substr_count($out, 'loading="lazy"') === 1 && str_contains($out, 'loading="eager"'), 'existing loading attribute kept');
    check(str_contains($out, 'width="3" height="2"'), 'local content image gets dimensions');
    check(substr_count($out, 'width=') === 2, 'existing width not duplicated');
    check(ImageProcessor::enhanceContentImages('<p>metin</p>') === '<p>metin</p>', 'html without images unchanged');
} finally {
    @unlink($png);
    @rmdir($dir);
}

echo $failed === 0 ? "ALL PASSED\n" : "{$failed} FAILED\n";
exit($failed === 0 ? 0 : 1);
