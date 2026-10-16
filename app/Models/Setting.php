<?php

namespace App\Models;

use Core\Database;
use Core\Model;

class Setting extends Model
{
    protected static string $table = 'settings';
    private static ?array $cache = null;

    public static function loadAll(): array
    {
        if (self::$cache === null) {
            self::$cache = [];
            $rows = Database::fetchAll('SELECT key_name, value FROM settings');
            foreach ($rows as $row) {
                self::$cache[$row['key_name']] = $row['value'];
            }
        }
        return self::$cache;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::loadAll();
        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $strVal = is_array($value) || is_object($value)
            ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : (string) $value;

        Database::query(
            'INSERT INTO settings (key_name, value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE value = :v2',
            ['k' => $key, 'v' => $strVal, 'v2' => $strVal]
        );

        if (self::$cache !== null) {
            self::$cache[$key] = $strVal;
        }
    }

    public static function getJson(string $key, array $default = []): array
    {
        $raw = self::get($key);
        if (empty($raw)) {
            return $default;
        }
        $decoded = json_decode((string) $raw, true);
        return is_array($decoded) ? $decoded : $default;
    }

    public static function setJson(string $key, array $data): void
    {
        self::set($key, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public static function clearCache(): void
    {
        self::$cache = null;
    }
}
