<?php

/**
 * Bvlog — Zamanlanmış Görevler (Cron) CLI & HTTP Giriş Betiği
 *
 * cPanel / Plesk / SSH Crontab Kullanımı:
 * * * * * * /usr/local/bin/php -q /tam/sunucu/yolu/cron.php >/dev/null 2>&1
 */

declare(strict_types=1);

define('BVLOG_ROOT', __DIR__);

require_once BVLOG_ROOT . '/core/Autoloader.php';
Core\Autoloader::register();
require_once BVLOG_ROOT . '/core/Helpers.php';

$appConfig = require BVLOG_ROOT . '/config/app.php';
date_default_timezone_set($appConfig['timezone'] ?? 'Europe/Istanbul');
Core\ErrorHandler::register((bool) ($appConfig['debug'] ?? false));

if (!is_installed()) {
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "Bvlog kurulumu henüz tamamlanmamış.\n");
    } else {
        http_response_code(503);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'error' => 'Sistem henüz kurulmamış.'], JSON_UNESCAPED_UNICODE);
    }
    exit(1);
}

// Veritabanı sürüm yükseltmelerini ve eklentileri yükle
Core\Migrator::checkAndUpgrade($appConfig['version'] ?? '1.3.2');
Core\PluginManager::boot();
Core\Hook::doAction('app_init');

// Eğer tarayıcı/HTTP üzerinden doğrudan cron.php çağrıldıysa gizli anahtarı doğrula
if (PHP_SAPI !== 'cli') {
    $providedToken = (string) ($_GET['token'] ?? $_SERVER['HTTP_X_CRON_TOKEN'] ?? '');
    $expectedToken = Core\Cron::getToken();

    if ($providedToken === '' || !hash_equals($expectedToken, $providedToken)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => false,
            'error'   => 'Geçersiz veya eksik Cron güvenlik anahtarı (token).',
        ], JSON_UNESCAPED_UNICODE);
        exit(1);
    }

    $result = Core\Cron::run(false, null, 'http');
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit(0);
}

// CLI Üzerinden Çalıştırma
$forceAll = in_array('--force', $argv ?? [], true) || in_array('-f', $argv ?? [], true);
$result = Core\Cron::run($forceAll, null, 'cli');

echo sprintf(
    "[%s] Bvlog Cron tamamlandı (%d ms) — Çalışan görev: %d, Atlanan: %d\n",
    $result['ran_at'],
    $result['duration_ms'] ?? 0,
    count($result['executed'] ?? []),
    count($result['skipped'] ?? [])
);

foreach ($result['executed'] ?? [] as $task) {
    echo sprintf("  - [%s] %s: %s (%d ms)\n", strtoupper($task['status']), $task['title'], $task['message'], $task['duration_ms']);
}

exit($result['success'] ? 0 : 1);
