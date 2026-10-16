<?php

namespace Core;

class Csrf
{
    private const TOKEN_KEY = '_csrf_token';

    public static function token(): string
    {
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
        Session::start();
        $sessionToken = Session::get(self::TOKEN_KEY);
        if (!$sessionToken) {
            return false;
        }

        if ($token === null) {
            $token = $_POST['_csrf_token']
                ?? $_SERVER['HTTP_X_CSRF_TOKEN']
                ?? null;
        }

        if (!is_string($token) || empty($token)) {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }
}
