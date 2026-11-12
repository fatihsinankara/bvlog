<?php

declare(strict_types=1);

// Session tests need headers that have not been sent yet.
ob_start();

define('BVLOG_ROOT', dirname(__DIR__));
require_once BVLOG_ROOT . '/core/Autoloader.php';
Core\Autoloader::register();
require_once BVLOG_ROOT . '/core/Helpers.php';

use Core\Csrf;
use Core\HttpCache;
use Core\ImageProcessor;

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
$_SERVER['REQUEST_METHOD'] = 'GET';

$keyFile = BVLOG_ROOT . '/storage/cache/csrf.key';
$keyExisted = is_file($keyFile);
$uploadDir = BVLOG_ROOT . '/storage/uploads/_arch_test_' . bin2hex(random_bytes(4));

try {
    // P-1: public pages get a signed token without opening a session.
    $_SERVER['REQUEST_URI'] = '/iletisim';
    $token = Csrf::token();
    check(str_starts_with($token, 's1.'), 'anonymous public page gets stateless token');
    check(session_status() !== PHP_SESSION_ACTIVE, 'stateless token does not start a session');
    check(Csrf::verifyStateless($token), 'stateless token verifies');
    check(is_file($keyFile) && preg_match('/^[0-9a-f]{64}$/', trim((string) file_get_contents($keyFile))) === 1, 'signing key stored');

    $hour = 3600 * 500000;
    check(Csrf::statelessToken($hour + 5) === Csrf::statelessToken($hour + 3000), 'token stable within the hour (ETag friendly)');
    check(Csrf::statelessToken($hour + 5) !== Csrf::statelessToken($hour + 3605), 'token changes next hour');

    $now = $hour + 100;
    $fresh = (string) Csrf::statelessToken($now);
    check(Csrf::verifyStateless($fresh, $now + 86400), 'token valid after one day');
    check(!Csrf::verifyStateless($fresh, $now + 4 * 86400), 'expired token rejected');
    check(!Csrf::verifyStateless((string) Csrf::statelessToken($now + 10 * 86400), $now), 'far-future expiry rejected');
    $last = substr($fresh, -1) === 'a' ? 'b' : 'a';
    check(!Csrf::verifyStateless(substr($fresh, 0, -1) . $last, $now), 'tampered signature rejected');
    [, $exp, $sig] = explode('.', $fresh);
    check(!Csrf::verifyStateless('s1.' . ((int) $exp + 3600) . '.' . $sig, $now), 'tampered expiry rejected');
    check(!Csrf::verifyStateless('s2.' . $exp . '.' . $sig, $now), 'unknown prefix rejected');
    check(!Csrf::verifyStateless('garbage', $now), 'malformed token rejected');

    $_SERVER['REQUEST_URI'] = '/admin/posts/1/update';
    check(!Csrf::verify($token), 'stateless token rejected on admin path');

    $_SERVER['REQUEST_URI'] = '/iletisim';
    check(Csrf::verify($token), 'stateless token accepted on public form');
    $_SESSION['admin_user_id'] = 1;
    check(!Csrf::verify($token), 'stateless token rejected while admin is logged in');
    unset($_SESSION['admin_user_id']);

    $sessionToken = Csrf::token();
    check(!str_starts_with($sessionToken, 's1.') && strlen($sessionToken) === 64, 'active session gets session token');
    check(Csrf::verify($sessionToken), 'session token verifies');
    check(!Csrf::verify(''), 'empty token rejected');

    // P-1: ETag helpers.
    $etag = HttpCache::etagFor('<html>a</html>');
    check(preg_match('/^W\/"[0-9a-f]{32}"$/', $etag) === 1, 'weak etag format');
    check($etag !== HttpCache::etagFor('<html>b</html>'), 'etag changes with body');
    check(HttpCache::matches($etag, $etag), 'exact etag matches');
    check(HttpCache::matches(substr($etag, 2), $etag), 'strong form of weak etag matches');
    check(HttpCache::matches('"x", ' . $etag, $etag), 'etag list matches');
    check(HttpCache::matches('*', $etag), 'wildcard matches');
    check(!HttpCache::matches('"other"', $etag), 'different etag does not match');
    check(!HttpCache::matches('', $etag) && !HttpCache::matches(['x'], $etag), 'empty or array header does not match');

    // P-5: srcset from width variants stored next to the original.
    mkdir($uploadDir, 0755, true);
    $rel = '/storage/uploads/' . basename($uploadDir) . '/orig.webp';
    // Minimal lossless WebP header, 2000x1000 (enough for getimagesize).
    $bits = (2000 - 1) | ((1000 - 1) << 14);
    $vp8l = "\x2f" . pack('V', $bits) . str_repeat("\x00", 11);
    file_put_contents(BVLOG_ROOT . $rel, 'RIFF' . pack('V', 4 + 8 + strlen($vp8l)) . 'WEBP' . 'VP8L' . pack('V', strlen($vp8l)) . $vp8l);
    check(ImageProcessor::dimensions($rel) === [2000, 1000], 'test webp readable');

    check(ImageProcessor::variantPath('/a/b.webp', 480) === '/a/b-480w.webp', 'variant path naming');
    check(ImageProcessor::srcsetAttributes($rel, '100vw') === '', 'no srcset without variants');

    touch(BVLOG_ROOT . ImageProcessor::variantPath($rel, 480));
    touch(BVLOG_ROOT . ImageProcessor::variantPath($rel, 1080));
    $attrs = ImageProcessor::srcsetAttributes($rel, '(min-width: 56rem) 848px, 100vw');
    check(str_contains($attrs, 'orig-480w.webp 480w') && str_contains($attrs, 'orig-1080w.webp 1080w'), 'srcset lists existing variants');
    check(!str_contains($attrs, '768w') && str_contains($attrs, 'orig.webp 2000w'), 'srcset skips missing variant, ends with original');
    check(str_contains($attrs, 'sizes="(min-width: 56rem) 848px, 100vw"'), 'sizes attribute written');
    check(str_contains($attrs, 'https://bvlog.test/storage/uploads/'), 'srcset uses absolute urls');
    check(ImageProcessor::srcsetAttributes('/storage/uploads/../../config/app.php', '100vw') === '', 'srcset rejects traversal');
    check(ImageProcessor::srcsetAttributes('https://cdn.example/x.webp', '100vw') === '', 'srcset ignores remote images');

    $html = ImageProcessor::enhanceContentImages('<img src="' . $rel . '" alt="a"><img src="' . $rel . '" srcset="x.webp 1w" alt="b">');
    check(substr_count($html, 'srcset=') === 2 && str_contains($html, 'orig-480w.webp 480w'), 'content image gets srcset, existing srcset kept');
    check(str_contains($html, 'sizes="' . ImageProcessor::CONTENT_SIZES . '"'), 'content image gets default sizes');

    check(ImageProcessor::generateVariants('/config/app.php') === 0, 'variants not generated outside uploads');
    check(ImageProcessor::generateVariants(ImageProcessor::variantPath($rel, 480)) === 0, 'variants not generated from a variant');

    ImageProcessor::deleteVariants($rel);
    check(!is_file(BVLOG_ROOT . ImageProcessor::variantPath($rel, 480)) && !is_file(BVLOG_ROOT . ImageProcessor::variantPath($rel, 1080)), 'variants deleted');
    check(is_file(BVLOG_ROOT . $rel), 'original kept when variants deleted');

    if (function_exists('imagewebp')) {
        $img = imagecreatetruecolor(1200, 600);
        $real = '/storage/uploads/' . basename($uploadDir) . '/real.webp';
        imagewebp($img, BVLOG_ROOT . $real);
        imagedestroy($img);
        check(ImageProcessor::generateVariants($real) === 3, 'gd: 480/768/1080 generated for 1200px image');
        $v = @getimagesize(BVLOG_ROOT . ImageProcessor::variantPath($real, 768));
        check(is_array($v) && $v[0] === 768 && $v[1] === 384, 'gd: variant keeps aspect ratio');
        check(ImageProcessor::generateVariants($real) === 0, 'gd: existing variants skipped');
    } else {
        echo "skip gd variant generation (no GD/WebP)\n";
    }
} finally {
    foreach (glob($uploadDir . '/*') ?: [] as $file) {
        @unlink($file);
    }
    @rmdir($uploadDir);
    if (!$keyExisted) {
        @unlink($keyFile);
    }
}

echo $failed === 0 ? "ALL PASSED\n" : "{$failed} FAILED\n";
ob_end_flush();
exit($failed === 0 ? 0 : 1);
