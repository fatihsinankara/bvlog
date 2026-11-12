<?php

namespace Core;

/**
 * Oturumsuz GET isteklerinde HTML yanitina ETag ve dogrulama gerektiren
 * Cache-Control ekler. Tarayici ayni sayfayi tekrar istediginde icerik
 * degismemisse govdesiz 304 doner; geri/ileri onbellegi de kullanilabilir.
 *
 * Oturum acik olan istekler (yonetici, form sonrasi bildirim) PHP'nin
 * varsayilan no-store davranisini korur.
 */
class HttpCache
{
    private static ?int $level = null;

    public static function begin(): void
    {
        $method = Request::method();
        if (($method !== 'GET' && $method !== 'HEAD') || self::$level !== null) {
            return;
        }

        ob_start();
        self::$level = ob_get_level();
        register_shutdown_function([self::class, 'finish']);
    }

    public static function finish(): void
    {
        // exit ic ice bir tamponun icinde cagrildiysa govde eksik olabilir; dokunma.
        if (self::$level === null || ob_get_level() !== self::$level || headers_sent()) {
            return;
        }
        if (session_status() === PHP_SESSION_ACTIVE || http_response_code() !== 200) {
            return;
        }

        $hasCookie = false;
        foreach (headers_list() as $header) {
            $name = strtolower(strtok($header, ':'));
            if ($name === 'content-type' && stripos($header, 'text/html') === false) {
                return;
            }
            if ($name === 'cache-control' || $name === 'etag') {
                return;
            }
            if ($name === 'set-cookie') {
                $hasCookie = true;
            }
        }

        $body = ob_get_contents();
        if (!is_string($body) || $body === '') {
            return;
        }

        $etag = self::etagFor($body);
        header('Cache-Control: ' . ($hasCookie ? 'private' : 'public') . ', max-age=0, must-revalidate');
        header('ETag: ' . $etag);

        if (self::matches($_SERVER['HTTP_IF_NONE_MATCH'] ?? '', $etag)) {
            ob_clean();
            http_response_code(304);
        }
    }

    public static function etagFor(string $body): string
    {
        return 'W/"' . substr(hash('sha256', $body), 0, 32) . '"';
    }

    public static function matches(mixed $ifNoneMatch, string $etag): bool
    {
        if (!is_string($ifNoneMatch) || $ifNoneMatch === '') {
            return false;
        }
        $bare = static fn (string $tag): string => preg_replace('/^W\//', '', trim($tag)) ?? '';
        foreach (explode(',', $ifNoneMatch) as $candidate) {
            $candidate = trim($candidate);
            if ($candidate === '*' || $bare($candidate) === $bare($etag)) {
                return true;
            }
        }
        return false;
    }
}
