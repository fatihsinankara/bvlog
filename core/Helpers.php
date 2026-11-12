<?php

use Core\Security;
use Core\Csrf;
use Core\Session;
use Core\Hook;
use App\Models\Setting;

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return Security::escape((string) ($value ?? ''));
    }
}

if (!function_exists('is_installed')) {
    function is_installed(): bool
    {
        return file_exists(BVLOG_ROOT . '/storage/installed.lock');
    }
}

if (!function_exists('normalize_site_url')) {
    function normalize_site_url(string $url): string
    {
        $url = trim($url);
        if ($url === '' || preg_match('/[\r\n\s]/', $url)) {
            return '';
        }
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }
        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['host']) || !preg_match('/^[A-Za-z0-9.\-]+$/', (string) $parts['host'])) {
            return '';
        }
        $scheme = strtolower((string) $parts['scheme']) === 'http' ? 'http' : 'https';
        $port = isset($parts['port']) ? ':' . (int) $parts['port'] : '';
        $path = isset($parts['path']) ? rtrim((string) $parts['path'], '/') : '';
        return $scheme . '://' . $parts['host'] . $port . $path;
    }
}

if (!function_exists('base_url')) {
    function base_url(): string
    {
        $configured = function_exists('setting') ? (string) setting('site_url', '') : '';
        $normalized = normalize_site_url($configured);
        if ($normalized !== '') {
            return $normalized;
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['SERVER_PORT'] ?? 80) == 443 ? 'https' : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        if (!preg_match('/^[A-Za-z0-9.\-:\[\]]+$/', $host)) {
            $host = 'localhost';
        }
        return "{$scheme}://{$host}";
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        return rtrim(base_url(), '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('post_url')) {
    function post_url(array|string $post, ?string $categorySlug = null): string
    {
        $slug = is_array($post) ? ($post['slug'] ?? '') : (string) $post;
        $catSlug = is_array($post) ? ($post['category_slug'] ?? $categorySlug ?? '') : ($categorySlug ?? '');

        $structure = setting('permalink_structure', 'default');

        return match ($structure) {
            'category_slug' => !empty($catSlug)
                ? url('/' . ltrim($catSlug, '/') . '/' . ltrim($slug, '/'))
                : url('/blog/' . ltrim($slug, '/')),
            'blog_category_slug' => !empty($catSlug)
                ? url('/blog/' . ltrim($catSlug, '/') . '/' . ltrim($slug, '/'))
                : url('/blog/' . ltrim($slug, '/')),
            'post_only' => url('/' . ltrim($slug, '/')),
            default => url('/blog/' . ltrim($slug, '/')),
        };
    }
}

if (!function_exists('current_url')) {
    function current_url(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        return rtrim(base_url(), '/') . $uri;
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        $cleanPath = ltrim($path, '/');
        $fullPath = BVLOG_ROOT . '/assets/' . $cleanPath;
        $version = is_file($fullPath) ? '?v=' . filemtime($fullPath) : '';
        return url('assets/' . $cleanPath) . $version;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Csrf::token();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return Csrf::field();
    }
}

if (!function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        if (!is_installed()) {
            return $default;
        }
        try {
            return Setting::get($key, $default);
        } catch (\Throwable $e) {
            return $default;
        }
    }
}

if (!function_exists('is_module_active')) {
    function is_module_active(string $module): bool
    {
        return match ($module) {
            'blog'      => true,
            'portfolio' => setting('module_portfolio', '1') === '1',
            'cv'        => setting('module_cv', '1') === '1',
            'comments'  => setting('module_comments', '1') === '1',
            'contact'   => setting('module_contact', '1') === '1',
            default     => false,
        };
    }
}

if (!function_exists('slugify')) {
    function slugify(string $text): string
    {
        $trMap = [
            'ş' => 's', 'Ş' => 's',
            'ı' => 'i', 'İ' => 'i', 'I' => 'i',
            'ğ' => 'g', 'Ğ' => 'g',
            'ü' => 'u', 'Ü' => 'u',
            'ö' => 'o', 'Ö' => 'o',
            'ç' => 'c', 'Ç' => 'c',
        ];
        $text = strtr($text, $trMap);
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text) ?? '';
        $text = preg_replace('/[\s-]+/', '-', trim($text)) ?? '';
        return $text !== '' ? $text : 'icerik-' . substr(bin2hex(random_bytes(4)), 0, 6);
    }
}

if (!function_exists('reading_time')) {
    function reading_time(string $content): int
    {
        $clean = strip_tags($content);
        preg_match_all('/\p{L}+/u', $clean, $matches);
        $words = !empty($matches[0]) ? count($matches[0]) : str_word_count($clean);
        $minutes = (int) ceil($words / 200);
        $minutes = max(1, $minutes);
        return (int) Hook::applyFilters('reading_time', $minutes, $content);
    }
}

if (!function_exists('excerpt')) {
    function excerpt(string $content, int $limit = 160): string
    {
        $text = html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
        if ($limit <= 0 || mb_strlen($text, 'UTF-8') <= $limit) {
            return $text;
        }
        $cut = mb_substr($text, 0, $limit, 'UTF-8');
        $space = mb_strrpos($cut, ' ', 0, 'UTF-8');
        if ($space !== false && $space > (int) ($limit * 0.6)) {
            $cut = mb_substr($cut, 0, $space, 'UTF-8');
        }
        return rtrim($cut, " \t\n\r\0\x0B.,;:-") . '…';
    }
}

if (!function_exists('post_reading_minutes')) {
    function post_reading_minutes(array $post): int
    {
        $stored = (int) ($post['reading_minutes'] ?? 0);
        if ($stored > 0) {
            return $stored;
        }
        if (!empty($post['content'])) {
            return reading_time((string) $post['content']);
        }
        return 1;
    }
}

if (!function_exists('format_date')) {
    function format_date(?string $dateStr, bool $withTime = false, ?string $locale = null): string
    {
        if (empty($dateStr)) {
            return '';
        }
        $ts = strtotime($dateStr);
        if (!$ts) {
            return $dateStr;
        }

        $loc = $locale ?: ($GLOBALS['appConfig']['locale'] ?? 'tr');

        $monthsEn = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];

        $monthsTr = [
            1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
            5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
            9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık',
        ];

        $months = ($loc === 'en') ? $monthsEn : $monthsTr;
        $day = date('j', $ts);
        $month = $months[(int) date('n', $ts)] ?? '';
        $year = date('Y', $ts);

        $formatted = ($loc === 'en') ? "{$month} {$day}, {$year}" : "{$day} {$month} {$year}";
        if ($withTime) {
            $formatted .= ' ' . date('H:i', $ts);
        }
        return $formatted;
    }
}

if (!function_exists('format_bytes')) {
    function format_bytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }
}

if (!function_exists('captcha_verify')) {
    function captcha_verify(string $form): bool
    {
        return (bool) \Core\Hook::applyFilters('verify_captcha', true, $form);
    }
}
