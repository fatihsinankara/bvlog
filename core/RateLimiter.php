<?php

namespace Core;

class RateLimiter
{
    private static function getStoragePath(): string
    {
        $dir = BVLOG_ROOT . '/storage/logs';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir . '/ratelimit.json';
    }

    /**
     * Dosya tabanlı atomik kilit mekanizması ve çöp toplama (garbage collection)
     */
    private static function withLock(callable $callback): mixed
    {
        $file = self::getStoragePath();
        $handle = @fopen($file, 'c+');
        if (!$handle) {
            $dummy = [];
            return $callback($dummy);
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
        $data = self::readData();
        $now = time();

        if (!isset($data[$key])) {
            return false;
        }

        if ($now - $data[$key]['first_attempt'] > $decaySeconds) {
            return false;
        }

        return $data[$key]['attempts'] >= $maxAttempts;
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
        });
    }

    public static function clear(string $key): void
    {
        self::withLock(function (array &$data) use ($key) {
            unset($data[$key]);
            return null;
        });
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
}
