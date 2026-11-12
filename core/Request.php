<?php

namespace Core;

class Request
{
    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function isPost(): bool
    {
        return self::method() === 'POST';
    }

    public static function isAjax(): bool
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
            || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    }

    public static function path(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = '/' . trim($path, '/');
        return $path === '' ? '/' : $path;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::pick($_GET, $key, $default);
    }

    public static function post(string $key, mixed $default = null): mixed
    {
        return self::pick($_POST, $key, $default);
    }

    /**
     * Dizi degeri yalnizca varsayilan da dizi ise dondurulur. Aksi halde
     * ?q[]=x gibi girdiler dize bekleyen kodda TypeError uretiyordu.
     */
    private static function pick(array $source, string $key, mixed $default): mixed
    {
        if (!isset($source[$key])) {
            return $default;
        }
        $value = $source[$key];
        if (is_string($value)) {
            return trim($value);
        }
        return is_array($default) ? $value : $default;
    }

    public static function input(string $key, mixed $default = null): mixed
    {
        return self::post($key, self::get($key, $default));
    }

    public static function all(): array
    {
        return array_merge($_GET, $_POST);
    }

    public static function file(string $key): ?array
    {
        return $_FILES[$key] ?? null;
    }

    public static function ip(): string
    {
        return Security::getClientIp();
    }
}
