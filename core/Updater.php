<?php

namespace Core;

class Updater
{
    private const REPO = 'fatihsinankara/bvlog';
    private const API_URL = 'https://api.github.com/repos/fatihsinankara/bvlog/releases/latest';
    private const CACHE_FILE = BVLOG_ROOT . '/storage/cache/update_check.json';
    private const CACHE_TTL = 43200; // 12 saat

    public static function check(bool $force = false): array
    {
        $appConfig = require BVLOG_ROOT . '/config/app.php';
        $currentVersion = ltrim((string) ($appConfig['version'] ?? '1.0.0'), 'vV');

        if (!$force && is_file(self::CACHE_FILE)) {
            $raw = @file_get_contents(self::CACHE_FILE);
            if ($raw) {
                $cached = json_decode($raw, true);
                if (is_array($cached) && !empty($cached['checked_at'])) {
                    $age = time() - strtotime($cached['checked_at']);
                    if ($age < self::CACHE_TTL) {
                        $cached['current_version'] = $currentVersion;
                        $cached['has_update'] = version_compare(ltrim($cached['latest_version'] ?? '0.0.0', 'vV'), $currentVersion, '>');
                        return $cached;
                    }
                }
            }
        }

        $remoteData = self::fetchFromGitHub();
        if ($remoteData === null || !isset($remoteData['tag_name'])) {
            // Hata durumunda varsa eski önbelleği döndür
            if (is_file(self::CACHE_FILE)) {
                $raw = @file_get_contents(self::CACHE_FILE);
                $cached = $raw ? json_decode($raw, true) : null;
                if (is_array($cached)) {
                    $cached['current_version'] = $currentVersion;
                    $cached['has_update'] = version_compare(ltrim($cached['latest_version'] ?? '0.0.0', 'vV'), $currentVersion, '>');
                    return $cached;
                }
            }

            return [
                'checked'         => false,
                'has_update'      => false,
                'current_version' => $currentVersion,
                'latest_version'  => $currentVersion,
                'tag_name'        => 'v' . $currentVersion,
                'release_name'    => 'Bilinmiyor',
                'release_notes'   => '',
                'html_url'        => 'https://github.com/' . self::REPO . '/releases',
                'download_url'    => '',
                'checked_at'      => date('Y-m-d H:i:s'),
                'error'           => 'GitHub sunucularına bağlanılamadı veya yayın bulunamadı.',
            ];
        }

        $latestTag = (string) $remoteData['tag_name'];
        $cleanLatest = ltrim($latestTag, 'vV');
        $hasUpdate = version_compare($cleanLatest, $currentVersion, '>');

        $downloadUrl = '';
        if (!empty($remoteData['assets']) && is_array($remoteData['assets'])) {
            foreach ($remoteData['assets'] as $asset) {
                if (!empty($asset['browser_download_url']) && str_ends_with($asset['name'] ?? '', '.zip')) {
                    $downloadUrl = $asset['browser_download_url'];
                    break;
                }
            }
        }
        if ($downloadUrl === '' && !empty($remoteData['zipball_url'])) {
            $downloadUrl = $remoteData['zipball_url'];
        }

        $result = [
            'checked'         => true,
            'has_update'      => $hasUpdate,
            'current_version' => $currentVersion,
            'latest_version'  => $cleanLatest,
            'tag_name'        => $latestTag,
            'release_name'    => $remoteData['name'] ?: $latestTag,
            'release_notes'   => $remoteData['body'] ?? '',
            'html_url'        => $remoteData['html_url'] ?? ('https://github.com/' . self::REPO . '/releases'),
            'download_url'    => $downloadUrl,
            'published_at'    => $remoteData['published_at'] ?? '',
            'checked_at'      => date('Y-m-d H:i:s'),
            'error'           => null,
        ];

        $cacheDir = dirname(self::CACHE_FILE);
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
        @file_put_contents(self::CACHE_FILE, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return $result;
    }

    private static function fetchFromGitHub(): ?array
    {
        $userAgent = 'Bvlog-CMS/' . (setting('db_version', '1.0.0')) . ' (PHP ' . PHP_VERSION . ')';

        // 1. Önce cURL dene (Varsayılan olarak tam SSL doğrulamasıyla)
        if (function_exists('curl_init')) {
            $ch = curl_init(self::API_URL);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_USERAGENT      => $userAgent,
                CURLOPT_TIMEOUT        => 8,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_HTTPHEADER     => [
                    'Accept: application/vnd.github.v3+json',
                ],
            ]);
            $body = curl_exec($ch);
            $errno = curl_errno($ch);
            // Sunucuda SSL CA sertifika paketi eksikse (CURLE_SSL_CACERT 60 / CURLE_SSL_CACERT_BADFILE 77) geri çekilme
            if ($errno === 60 || $errno === 77) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                $body = curl_exec($ch);
            }
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && is_string($body)) {
                $data = json_decode($body, true);
                if (is_array($data)) {
                    return $data;
                }
            }
        }

        // 2. cURL yoksa stream_context dene
        $ctx = stream_context_create([
            'http' => [
                'header'  => "User-Agent: {$userAgent}\r\nAccept: application/vnd.github.v3+json\r\n",
                'timeout' => 8,
            ],
            'ssl' => [
                'verify_peer'      => true,
                'verify_peer_name' => true,
            ],
        ]);

        $body = @file_get_contents(self::API_URL, false, $ctx);
        if (is_string($body) && $body !== '') {
            $data = json_decode($body, true);
            if (is_array($data)) {
                return $data;
            }
        }

        return null;
    }

    public static function hasUpdate(): bool
    {
        $info = self::check(false);
        return !empty($info['has_update']);
    }
}
