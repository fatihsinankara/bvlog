<?php

declare(strict_types=1);

// Sunucu duzeyindeki PHP ayarlarinin depoda dogru tanimlandigini kontrol eder.

$root = dirname(__DIR__);
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

$ini = @parse_ini_file($root . '/.user.ini');
check(is_array($ini), '.user.ini parses');
check(is_array($ini) && in_array($ini['session.upload_progress.enabled'] ?? null, ['0', ''], true), '.user.ini disables session.upload_progress');

$htaccess = (string) file_get_contents($root . '/.htaccess');
foreach (['mod_php.c', 'mod_php7.c', 'Litespeed'] as $module) {
    $pattern = '/<IfModule ' . preg_quote($module, '/') . '>\s*php_flag session\.upload_progress\.enabled off\s*<\/IfModule>/';
    check(preg_match($pattern, $htaccess) === 1, ".htaccess disables upload progress for {$module}");
}
check(str_contains($htaccess, 'Options -Indexes'), '.htaccess already needs AllowOverride Options (php_flag adds no new requirement)');

$installer = (string) file_get_contents($root . '/app/Controllers/InstallController.php');
check(str_contains($installer, "version_compare(PHP_VERSION, '8.2.0', '>=')"), 'installer requires PHP 8.2');

$updater = (string) file_get_contents($root . '/core/Updater.php');
check(str_contains($updater, "\$normalized === '.user.ini' && is_file(BVLOG_ROOT . '/.user.ini')"), 'updater keeps an existing .user.ini');

echo $failed === 0 ? "ALL PASSED\n" : "{$failed} FAILED\n";
exit($failed === 0 ? 0 : 1);
