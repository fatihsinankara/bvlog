<?php

namespace Core;

class RateLimiter
{
    private static ?string $baseDir = null;

    public static function setBaseDir(?string $dir): void
    {
        self::$baseDir = $dir;
    }

    private static function getStoragePath(): string
    {
        $dir = self::$baseDir ?? (BVLOG_ROOT . '/storage/logs');
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return $dir . '/ratelimit.json';
        }
        return $dir . '/ratelimit.json';
    }

    /**
     * Dosya tabanlı atomik kilit. Kilit alınamazsa $failValue döner (istek sayılmaz, geçiş yok).
     */
    private static function withLock(callable $callback, mixed $failValue): mixed
    {
        $file = self::getStoragePath();
        $handle = @fopen($file, 'c+');
        if (!$handle) {
            return $failValue;
        }

        flock($handle, LOCK_EX);

        clearstatcache(true, $file);
        $size = filesize($file);
        $content = $size > 0 ? fread($handle, $size) : '';
        $data = !empty($content) ? json_decode($content, true) : [];
        if (!is_array($data)) {
            $data = [];
        }

        $result = $callback($data);

        // 24 saatten eski kayıtları temizle
        $now = time();
        foreach ($data as $k => $item) {
            if (!isset($item['first_attempt']) || ($now - $item['first_attempt'] > 86400)) {
                unset($data[$k]);
            }
        }

        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, json_encode($data, JSON_UNESCAPED_SLASHES));
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);

        return $result;
    }

    private static function readData(): array
    {
        $file = self::getStoragePath();
        if (!file_exists($file)) {
            return [];
        }
        $data = json_decode((string) @file_get_contents($file), true);
        return is_array($data) ? $data : [];
    }

    public static function tooManyAttempts(string $key, int $maxAttempts = 5, int $decaySeconds = 900): bool
    {
        return (bool) self::withLock(function (array &$data) use ($key, $maxAttempts, $decaySeconds) {
            $now = time();
            if (!isset($data[$key])) {
                return false;
            }
            if ($now - $data[$key]['first_attempt'] > $decaySeconds) {
                return false;
            }
            return $data[$key]['attempts'] >= $maxAttempts;
        }, true);
    }

    public static function hit(string $key, int $decaySeconds = 900): int
    {
        return (int) self::withLock(function (array &$data) use ($key, $decaySeconds) {
            $now = time();
            if (!isset($data[$key]) || ($now - $data[$key]['first_attempt'] > $decaySeconds)) {
                $data[$key] = [
                    'attempts'      => 1,
                    'first_attempt' => $now,
                ];
            } else {
                $data[$key]['attempts']++;
            }
            return $data[$key]['attempts'];
        }, PHP_INT_MAX);
    }

    public static function clear(string $key): void
    {
        self::withLock(function (array &$data) use ($key) {
            unset($data[$key]);
            return null;
        }, null);
    }

    public static function availableIn(string $key, int $decaySeconds = 900): int
    {
        $data = self::readData();
        if (!isset($data[$key])) {
            return 0;
        }
        $remaining = ($data[$key]['first_attempt'] + $decaySeconds) - time();
        return max(0, $remaining);
    }

    public static function check(string $action, string $ip, int $maxAttempts = 5, int $decaySeconds = 900): bool
    {
        $key = $action . ':' . $ip;
        if (self::tooManyAttempts($key, $maxAttempts, $decaySeconds)) {
            return false;
        }
        self::hit($key, $decaySeconds);
        return true;
    }
}
