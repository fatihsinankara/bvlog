<?php

namespace Core;

class Response
{
    public static function redirect(string $path, int $code = 302): never
    {
        $target = self::safeTarget($path);
        header('Location: ' . $target, true, $code);
        exit;
    }

    /**
     * Yalnızca aynı sitenin yoluna veya aynı hosttaki mutlak adrese izin verir.
     */
    public static function safeTarget(string $path): string
    {
        $path = str_replace(["\r", "\n", "\0"], '', trim($path));
        if ($path === '') {
            return url('/');
        }

        if (preg_match('#^https?://#i', $path)) {
            $host = parse_url($path, PHP_URL_HOST);
            $our = parse_url(base_url(), PHP_URL_HOST);
            if (is_string($host) && is_string($our) && strcasecmp($host, $our) === 0) {
                return $path;
            }
            return url('/');
        }

        if (str_starts_with($path, '//') || str_starts_with($path, '/\\')) {
            return url('/');
        }

        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        return url($path);
    }

    public static function sameOriginPath(string $fallback = '/'): string
    {
        $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        $parts = parse_url($referer);
        if (!is_array($parts) || empty($parts['path']) || !is_string($parts['path'])) {
            return $fallback;
        }

        $host = (string) ($parts['host'] ?? '');
        $our = (string) (parse_url(base_url(), PHP_URL_HOST) ?? '');
        if ($host !== '' && strcasecmp($host, $our) !== 0) {
            return $fallback;
        }

        $path = $parts['path'];
        if (!str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return $fallback;
        }

        $query = '';
        if (!empty($parts['query']) && is_string($parts['query']) && !preg_match('/[\r\n]/', $parts['query'])) {
            $query = '?' . $parts['query'];
        }

        return $path . $query;
    }

    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function xml(string $xml, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/xml; charset=UTF-8');
        echo $xml;
        exit;
    }

    public static function text(string $text, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: text/plain; charset=UTF-8');
        echo $text;
        exit;
    }
}
