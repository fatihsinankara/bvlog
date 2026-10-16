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

    private static function loadData(): array
    {
        $file = self::getStoragePath();
        if (!file_exists($file)) {
            return [];
        }
        $data = json_decode((string) file_get_contents($file), true);
        return is_array($data) ? $data : [];
    }

    private static function saveData(array $data): void
    {
        file_put_contents(self::getStoragePath(), json_encode($data), LOCK_EX);
    }

    public static function tooManyAttempts(string $key, int $maxAttempts = 5, int $decaySeconds = 900): bool
    {
        $data = self::loadData();
        $now = time();

        if (!isset($data[$key])) {
            return false;
        }

        if ($now - $data[$key]['first_attempt'] > $decaySeconds) {
            unset($data[$key]);
            self::saveData($data);
            return false;
        }

        return $data[$key]['attempts'] >= $maxAttempts;
    }

    public static function hit(string $key, int $decaySeconds = 900): int
    {
        $data = self::loadData();
        $now = time();

        if (!isset($data[$key]) || ($now - $data[$key]['first_attempt'] > $decaySeconds)) {
            $data[$key] = [
                'attempts' => 1,
                'first_attempt' => $now,
            ];
        } else {
            $data[$key]['attempts']++;
        }

        self::saveData($data);
        return $data[$key]['attempts'];
    }

    public static function clear(string $key): void
    {
        $data = self::loadData();
        if (isset($data[$key])) {
            unset($data[$key]);
            self::saveData($data);
        }
    }

    public static function availableIn(string $key, int $decaySeconds = 900): int
    {
        $data = self::loadData();
        if (!isset($data[$key])) {
            return 0;
        }
        $remaining = ($data[$key]['first_attempt'] + $decaySeconds) - time();
        return max(0, $remaining);
    }
}
