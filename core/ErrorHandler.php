<?php

namespace Core;

use Throwable;
use ErrorException;

class ErrorHandler
{
    private static bool $debug = false;

    public static function register(bool $debug = false): void
    {
        self::$debug = $debug;

        error_reporting(E_ALL);
        ini_set('display_errors', $debug ? '1' : '0');

        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handleError(int $severity, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        throw new ErrorException($message, 0, $severity, $file, $line);
    }

    public static function handleException(Throwable $e): void
    {
        self::logException($e);

        if (!headers_sent()) {
            http_response_code(500);
        }

        if (Request::isAjax()) {
            Response::json([
                'success' => false,
                'message' => self::$debug ? $e->getMessage() : 'Sunucu hatası oluştu. Lütfen tekrar deneyin.',
                'debug'   => self::$debug ? [
                    'file'  => $e->getFile(),
                    'line'  => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ] : null,
            ], 500);
            return;
        }

        if (self::$debug) {
            self::renderDebug($e);
            return;
        }

        try {
            View::render('errors/500', [
                'code'    => 500,
                'message' => 'Beklenmeyen bir hata oluştu. Sorun günlük kayıtlarımıza iletildi.',
                'meta'    => ['title' => '500 Sunucu Hatası'],
            ]);
        } catch (Throwable) {
            echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>500 Sunucu Hatası</title></head><body style="font-family:sans-serif;text-align:center;padding:50px;"><h2>500 — Sunucu Hatası</h2><p>Beklenmedik bir hata oluştu. Lütfen daha sonra tekrar deneyin.</p></body></html>';
        }
    }

    public static function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE], true)) {
            $e = new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']);
            self::handleException($e);
        }
    }

    private static function logException(Throwable $e): void
    {
        $logDir = BVLOG_ROOT . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . '/error.log';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        $entry = sprintf(
            "[%s] [%s %s] [IP: %s] %s: %s in %s:%d\nStack trace:\n%s\n\n",
            date('Y-m-d H:i:s'),
            $method,
            $uri,
            $ip,
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );

        @file_put_contents($logFile, $entry, FILE_APPEND);
    }

    private static function renderDebug(Throwable $e): void
    {
        $class = get_class($e);
        $message = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        $file = htmlspecialchars($e->getFile(), ENT_QUOTES, 'UTF-8');
        $line = $e->getLine();
        $trace = htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8');

        echo <<<HTML
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uygulama Hatası (Hata Ayıklama Modu)</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #09090b; color: #f4f4f5; padding: 30px; margin: 0; line-height: 1.5; }
        .box { max-width: 900px; margin: 0 auto; background: #18181b; border: 1px solid #27272a; border-radius: 12px; padding: 24px; }
        h1 { font-size: 1.25rem; color: #ef4444; margin-top: 0; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; background: #27272a; font-size: 0.8rem; font-family: monospace; color: #a1a1aa; }
        .msg { font-size: 1.1rem; font-weight: 600; margin: 16px 0; word-break: break-word; }
        .loc { font-size: 0.85rem; color: #a1a1aa; font-family: monospace; }
        pre { background: #09090b; padding: 16px; border-radius: 8px; overflow-x: auto; font-size: 0.8rem; color: #d4d4d8; border: 1px solid #27272a; }
    </style>
</head>
<body>
    <div class="box">
        <span class="badge">{$class}</span>
        <h1>Bvlog Çalışma Zamanı Hatası</h1>
        <div class="msg">{$message}</div>
        <div class="loc">Dosya: {$file} (Satır: {$line})</div>
        <h3 style="margin-top:24px; font-size:0.9rem; color:#a1a1aa;">Yığın İzi (Stack Trace):</h3>
        <pre>{$trace}</pre>
    </div>
</body>
</html>
HTML;
    }
}
