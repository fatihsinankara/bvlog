<?php

namespace Core;

class Csrf
{
    private const TOKEN_KEY = '_csrf_token';

    /** Oturumsuz jetonun gecerlilik suresi (saniye). */
    private const STATELESS_TTL = 172800;
    private const STATELESS_PREFIX = 's1';

    /**
     * Oturum aciksa (yonetici, form sonrasi bildirim vb.) oturuma bagli jeton
     * dondurur. Oturumu olmayan ziyaretcide oturum acmak yerine imzali, sureli
     * bir jeton uretilir; boylece herkese acik sayfalar Set-Cookie ve
     * Cache-Control: no-store olmadan sunulabilir.
     */
    public static function token(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE && !self::hasSessionCookie() && self::statelessPathAllowed()) {
            $token = self::statelessToken();
            if ($token !== null) {
                return $token;
            }
        }

        Session::start();
        if (!Session::has(self::TOKEN_KEY)) {
            Session::set(self::TOKEN_KEY, bin2hex(random_bytes(32)));
        }
        return (string) Session::get(self::TOKEN_KEY);
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function verify(?string $token = null): bool
    {
        if ($token === null) {
            $token = $_POST['_csrf_token']
                ?? $_SERVER['HTTP_X_CSRF_TOKEN']
                ?? null;
        }

        if (!is_string($token) || $token === '') {
            return false;
        }

        Session::start();
        $sessionToken = Session::get(self::TOKEN_KEY);
        if (is_string($sessionToken) && $sessionToken !== '' && hash_equals($sessionToken, $token)) {
            return true;
        }

        // Oturumsuz jeton yalnizca herkese acik formlarda ve yonetici oturumu
        // yokken kabul edilir. Yonetim islemleri her zaman oturum jetonu ister.
        if (Session::has('admin_user_id') || !self::statelessPathAllowed()) {
            return false;
        }
        return self::verifyStateless($token);
    }

    public static function rotate(): void
    {
        Session::start();
        Session::set(self::TOKEN_KEY, bin2hex(random_bytes(32)));
    }

    /**
     * Imzali jeton: s1.<son gecerlilik>.<HMAC-SHA256>. Son gecerlilik saat
     * basina yuvarlanir; ayni saat icinde ayni sayfa ayni jetonu tasir ve
     * ETag ile dogrulanabilir kalir.
     */
    public static function statelessToken(?int $now = null): ?string
    {
        $key = self::secretKey();
        if ($key === null) {
            return null;
        }
        $now ??= time();
        $expires = (intdiv($now, 3600) + 1) * 3600 + self::STATELESS_TTL;
        return self::STATELESS_PREFIX . '.' . $expires . '.' . self::sign($key, $expires);
    }

    public static function verifyStateless(string $token, ?int $now = null): bool
    {
        $key = self::secretKey();
        if ($key === null) {
            return false;
        }
        $parts = explode('.', $token);
        if (count($parts) !== 3 || $parts[0] !== self::STATELESS_PREFIX || !ctype_digit($parts[1])) {
            return false;
        }
        $now ??= time();
        $expires = (int) $parts[1];
        if ($expires < $now || $expires > $now + self::STATELESS_TTL + 3600) {
            return false;
        }
        return hash_equals(self::sign($key, $expires), $parts[2]);
    }

    private static function sign(string $key, int $expires): string
    {
        return hash_hmac('sha256', 'bvlog-csrf|' . self::STATELESS_PREFIX . '|' . $expires, $key);
    }

    private static function hasSessionCookie(): bool
    {
        return isset($_COOKIE['BVLOG_SESSION']) && $_COOKIE['BVLOG_SESSION'] !== '';
    }

    private static function statelessPathAllowed(): bool
    {
        $path = Request::path();
        return !str_starts_with($path, '/admin') && !str_starts_with($path, '/install');
    }

    /**
     * Imza anahtari storage/cache/csrf.key dosyasinda tutulur (web'den erisime
     * kapali dizin). Yoksa olusturulur; yazilamazsa oturumlu jetona donulur.
     */
    private static function secretKey(): ?string
    {
        static $key = null;
        if ($key !== null) {
            return $key !== '' ? $key : null;
        }

        $file = BVLOG_ROOT . '/storage/cache/csrf.key';
        $value = is_file($file) ? trim((string) @file_get_contents($file)) : '';
        if (!preg_match('/^[0-9a-f]{64}$/', $value)) {
            $dir = dirname($file);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $fresh = bin2hex(random_bytes(32));
            // x kipi: ayni anda iki istek anahtar olusturursa ikincisi ilkini okur.
            $fp = @fopen($file, 'x');
            if ($fp !== false) {
                fwrite($fp, $fresh);
                fclose($fp);
                @chmod($file, 0600);
            }
            $value = is_file($file) ? trim((string) @file_get_contents($file)) : '';
        }

        $key = preg_match('/^[0-9a-f]{64}$/', $value) ? $value : '';
        return $key !== '' ? $key : null;
    }
}
