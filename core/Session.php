<?php

namespace Core;

class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || ($_SERVER['SERVER_PORT'] ?? 80) == 443;

            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'domain'   => '',
                'secure'   => $isSecure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);

            session_name('BVLOG_SESSION');
            session_start();
            self::touchActivity();
        }
    }

    private static function touchActivity(): void
    {
        $now = time();
        $last = (int) ($_SESSION['_last_activity'] ?? $now);
        $idleLimit = 60 * 60 * 12;
        if (isset($_SESSION['admin_user_id']) && ($now - $last) > $idleLimit) {
            unset($_SESSION['admin_user_id'], $_SESSION['admin_user_name']);
        }
        $_SESSION['_last_activity'] = $now;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return $default;
        }
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
    }

    public static function flash(string $key, mixed $value = null): mixed
    {
        if ($value !== null) {
            $_SESSION['_flash'][$key] = $value;
            return null;
        }

        $val = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $val;
    }

    public static function hasFlash(string $key): bool
    {
        return isset($_SESSION['_flash'][$key]);
    }
}
