<?php

declare(strict_types=1);

define('BVLOG_ROOT', dirname(__DIR__));
require_once BVLOG_ROOT . '/core/Autoloader.php';
Core\Autoloader::register();
require_once BVLOG_ROOT . '/core/Helpers.php';

use Core\HtmlSanitizer;
use Core\RateLimiter;
use Core\Response;
use Core\Security;

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

$clean = HtmlSanitizer::clean('<p>Merhaba <strong>dünya</strong></p><script>alert(1)</script><img src=x onerror=alert(1)><a href="javascript:alert(1)">x</a><iframe src="https://evil.example/a"></iframe><iframe src="https://www.youtube.com/embed/abcdefghijk" allowfullscreen></iframe>');
check(!str_contains(strtolower($clean), '<script'), 'script tag dropped');
check(!str_contains(strtolower($clean), 'onerror'), 'event handler dropped');
check(!str_contains(strtolower($clean), 'javascript:'), 'javascript url dropped');
check(!str_contains($clean, 'evil.example'), 'foreign iframe dropped');
check(str_contains($clean, 'youtube.com/embed/abcdefghijk'), 'youtube iframe kept');
check(str_contains($clean, 'Merhaba'), 'text kept');
check(str_contains($clean, 'dünya'), 'utf-8 kept');

$spoofed = Security::resolveClientIp([
    'REMOTE_ADDR' => '203.0.113.8',
    'HTTP_CF_CONNECTING_IP' => '198.51.100.4',
    'HTTP_X_FORWARDED_FOR' => '198.51.100.9',
], ['104.16.0.0/13']);
check($spoofed === '203.0.113.8', 'untrusted peer ignores forwarded headers');

$cloudflare = Security::resolveClientIp([
    'REMOTE_ADDR' => '104.16.1.2',
    'HTTP_CF_CONNECTING_IP' => '198.51.100.20',
    'HTTP_X_FORWARDED_FOR' => '203.0.113.50',
], ['104.16.0.0/13']);
check($cloudflare === '198.51.100.20', 'cloudflare peer keeps CF-Connecting-IP');

$direct = Security::resolveClientIp([
    'REMOTE_ADDR' => '198.51.100.7',
    'HTTP_X_FORWARDED_FOR' => '1.2.3.4',
], []);
check($direct === '198.51.100.7', 'empty trust list uses remote address');

$_SERVER['HTTP_HOST'] = 'bvlog.test';
$_SERVER['HTTPS'] = 'on';
$external = Response::safeTarget('https://evil.example/phish');
check(!str_contains($external, 'evil.example'), 'external redirect blocked');
$local = Response::safeTarget('/admin/login');
check(str_contains($local, '/admin/login'), 'local redirect kept');
$protocolRelative = Response::safeTarget('//evil.example');
check(!str_contains($protocolRelative, 'evil.example'), 'protocol-relative redirect blocked');

$dir = sys_get_temp_dir() . '/bvlog-rl-' . bin2hex(random_bytes(4));
mkdir($dir);
RateLimiter::setBaseDir($dir);
check(RateLimiter::tooManyAttempts('t', 2, 60) === false, 'fresh key is allowed');
RateLimiter::hit('t', 60);
RateLimiter::hit('t', 60);
check(RateLimiter::tooManyAttempts('t', 2, 60) === true, 'limit trips under lock');
$blocker = sys_get_temp_dir() . '/bvlog-notdir-' . bin2hex(random_bytes(3));
file_put_contents($blocker, 'x');
RateLimiter::setBaseDir($blocker);
check(RateLimiter::tooManyAttempts('x', 5, 60) === true, 'unwritable limiter fails closed');
RateLimiter::setBaseDir(null);

echo $failed === 0 ? "ALL PASSED\n" : "{$failed} FAILED\n";
exit($failed === 0 ? 0 : 1);
