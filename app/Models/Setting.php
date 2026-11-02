<?php

namespace App\Models;

use Core\Cache;
use Core\Database;
use Core\Model;

class Setting extends Model
{
    protected static string $table = 'settings';
    private static ?array $cache = null;

    /**
     * Arka plan görevlerinde güncellendiğinde ön yüz sayfa önbelleklerini
     * sıfırlamaması gereken dahili durum anahtarları.
     */
    private const INTERNAL_STATE_KEYS = [
        'cron_task_states',
        'cron_last_summary',
        'cron_history',
        'cron_token',
        'cache_last_cleared_at',
        'db_version',
    ];

    public static function loadAll(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $cachedOnDisk = Cache::getRaw('settings', 'settings:all');
        if (is_array($cachedOnDisk) && ($cachedOnDisk['cache_enabled'] ?? '1') === '1') {
            self::$cache = $cachedOnDisk;
            return self::$cache;
        }

        self::$cache = [];
        $rows = Database::fetchAll('SELECT key_name, value FROM settings');
        foreach ($rows as $row) {
            self::$cache[$row['key_name']] = $row['value'];
        }

        if ((self::$cache['cache_enabled'] ?? '1') === '1') {
            $ttl = max(60, (int) (self::$cache['cache_ttl'] ?? 3600));
            Cache::putRaw('settings', 'settings:all', self::$cache, $ttl, 'Tüm Site ve Modül Ayarları');
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

        // Ayar dosya önbelleğini güncelle veya temizle
        if (self::$cache !== null && (self::$cache['cache_enabled'] ?? '1') === '1') {
            $ttl = max(60, (int) (self::$cache['cache_ttl'] ?? 3600));
            Cache::putRaw('settings', 'settings:all', self::$cache, $ttl, 'Tüm Site ve Modül Ayarları');
        } else {
            Cache::forgetRaw('settings', 'settings:all');
        }

        // İçerik/görünüm ayarları değiştiyse ön yüz önbellek gruplarını otomatik tazele
        if (!in_array($key, self::INTERNAL_STATE_KEYS, true) && Cache::isAutoFlushEnabled()) {
            foreach (['home', 'blog', 'portfolio', 'cv', 'feeds'] as $grp) {
                Cache::flushGroup($grp);
            }
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
        Cache::forgetRaw('settings', 'settings:all');
    }
}
