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

// TLS: sertifika hatasinda dogrulamasiz tekrar deneme olmamali.
$tlsOff = 0;
foreach (['core/Updater.php', 'plugins/captcha/CaptchaService.php', 'plugins/smtp/SmtpClient.php'] as $file) {
    $tlsOff += preg_match_all(
        '/VERIFYPEER\s*,\s*false|VERIFYHOST\s*,\s*0|verify_peer\'?\s*=>\s*false/i',
        (string) file_get_contents(BVLOG_ROOT . '/' . $file)
    );
}
check($tlsOff === 0, 'no code path disables TLS verification');

// Guncelleme paketi yalnizca GitHub'dan ve HTTPS ile.
check(Core\Updater::isAllowedDownloadUrl('https://github.com/o/r/releases/download/v1/bvlog.zip'), 'github release url allowed');
check(Core\Updater::isAllowedDownloadUrl('https://api.github.com/repos/o/r/zipball/v1'), 'github zipball url allowed');
check(!Core\Updater::isAllowedDownloadUrl('http://github.com/o/r/a.zip'), 'plain http download rejected');
check(!Core\Updater::isAllowedDownloadUrl('https://evil.example/a.zip'), 'foreign host download rejected');
check(!Core\Updater::isAllowedDownloadUrl('https://github.com.evil.example/a.zip'), 'lookalike host rejected');
check(!Core\Updater::isAllowedDownloadUrl('https://user@github.com/a.zip'), 'credentials in download url rejected');
check(!Core\Updater::isAllowedDownloadUrl('file:///etc/passwd'), 'file scheme rejected');

// Gizli veriler disk onbellegine ve public view verisine gitmemeli.
if (extension_loaded('pdo_sqlite')) {
    $pdo = new PDO('sqlite::memory:', null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('CREATE TABLE settings (key_name TEXT PRIMARY KEY, value TEXT)');
    $pdo->exec("INSERT INTO settings VALUES ('site_title', 'Test'), ('cache_enabled', '1'),
        ('smtp_password', 'smtp-secret-1'), ('cron_token', 'cron-secret-2'),
        ('captcha_cf_secret_key', 'cf-secret-3'), ('captcha_rc_secret_key', 'rc-secret-4')");
    $pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, username TEXT, email TEXT, password_hash TEXT,
        role_title TEXT, bio TEXT, long_bio TEXT, avatar TEXT, cv_pdf TEXT, created_at TEXT, updated_at TEXT)');
    $pdo->exec("INSERT INTO users VALUES (1, 'Yazar', 'admin', 'admin@example.com', '\$2y\$hash', 'Dev', 'Bio', '', '', '', '', '')");
    $pdo->exec('CREATE TABLE comments (id INTEGER PRIMARY KEY, post_id INT, author_name TEXT, author_email TEXT,
        content TEXT, status TEXT, ip_address TEXT, created_at TEXT)');
    $pdo->exec("INSERT INTO comments VALUES (1, 7, 'Okur', 'okur@example.com', 'Selam', 'approved', '203.0.113.5', '')");
    (new ReflectionProperty(Core\Database::class, 'instance'))->setValue(null, $pdo);

    App\Models\Setting::clearCache();
    $all = App\Models\Setting::loadAll();
    $onDisk = json_encode(Core\Cache::getRaw('settings', 'settings:all'));
    check(!str_contains((string) $onDisk, 'secret') && !isset($all['smtp_password']), 'secret settings kept out of settings cache');
    check(App\Models\Setting::get('smtp_password') === 'smtp-secret-1', 'secret setting still readable');
    check(App\Models\Setting::get('cron_token') === 'cron-secret-2', 'cron token still readable');
    App\Models\Setting::clearCache();
    check(App\Models\Setting::get('captcha_cf_secret_key') === 'cf-secret-3', 'secret readable when settings come from disk cache');

    App\Models\User::clearAuthorCache();
    $author = App\Models\User::firstAuthor() ?? [];
    check(($author['name'] ?? '') === 'Yazar', 'author profile loaded');
    check(!array_key_exists('password_hash', $author) && !array_key_exists('email', $author) && !array_key_exists('username', $author), 'author row has no credentials');

    $comments = App\Models\Comment::approvedForPost(7);
    check(($comments[0]['author_name'] ?? '') === 'Okur', 'approved comments loaded');
    check(!array_key_exists('author_email', $comments[0]) && !array_key_exists('ip_address', $comments[0]), 'public comments omit email and ip');

    App\Models\Setting::clearCache();
    App\Models\User::clearAuthorCache();
    Core\Cache::forgetRaw('settings', 'settings:all');
    Core\Cache::forgetRaw('settings', 'settings:first_author_public');
    (new ReflectionProperty(Core\Database::class, 'instance'))->setValue(null, null);
} else {
    echo "skip cache secret checks (pdo_sqlite yok)\n";
}

// Eklenti hatalari: uyari karantinaya yol acmamali; captcha filtresi hata verirse public form reddedilmeli.
$rlDir = sys_get_temp_dir() . '/bvlog-rl-' . bin2hex(random_bytes(4));
mkdir($rlDir);
RateLimiter::setBaseDir($rlDir);
Core\PluginManager::handleRuntimeError('ornek', new ErrorException('uyari', 0, E_WARNING, __FILE__, __LINE__));
check(RateLimiter::tooManyAttempts('plugin_runtime_error:ornek', 1, 600) === false, 'plugin warning is logged, not counted');
Core\PluginManager::handleRuntimeError('ornek', new RuntimeException('hata'));
check(RateLimiter::tooManyAttempts('plugin_runtime_error:ornek', 1, 600) === true, 'plugin error is counted toward quarantine');
check(RateLimiter::tooManyAttempts('plugin_runtime_error:ornek', 3, 600) === false, 'single plugin error does not reach quarantine limit');

Core\Hook::addFilter('verify_captcha', function (): bool {
    throw new RuntimeException('captcha servisi coktu');
});
check(captcha_verify('contact') === false, 'failing captcha filter rejects public form');
check(captcha_verify('comment') === false, 'failing captcha filter rejects comment form');
check(captcha_verify('login') === true, 'login stays reachable when captcha filter fails');
RateLimiter::setBaseDir(null);

// TOC: baslik metni HTML olarak eklenmemeli.
$toc = (string) file_get_contents(BVLOG_ROOT . '/plugins/table-of-contents/plugin.php');
check(!preg_match('/\+\s*heading\.textContent/', $toc) && str_contains($toc, 'label.textContent'), 'toc builds links with textContent');

echo $failed === 0 ? "ALL PASSED\n" : "{$failed} FAILED\n";
exit($failed === 0 ? 0 : 1);
