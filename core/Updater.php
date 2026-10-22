<?php

namespace Core;

use App\Models\Setting;
use Throwable;
use ZipArchive;

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

    /**
     * Sunucu ortamının (ZipArchive, cURL, dosya yazma izinleri)
     * otomatik güncellemeye uygun olup olmadığını denetler.
     */
    public static function checkEnvironment(): array
    {
        $hasZip = class_exists('ZipArchive');
        $hasDownload = function_exists('curl_init') || (bool) ini_get('allow_url_fopen');

        $rootWritable = is_writable(BVLOG_ROOT);
        $appWritable = is_writable(BVLOG_ROOT . '/app');
        $coreWritable = is_writable(BVLOG_ROOT . '/core');
        $storageWritable = is_writable(BVLOG_ROOT . '/storage');

        $allWritable = $rootWritable && $appWritable && $coreWritable && $storageWritable;

        $errors = [];
        if (!$hasZip) {
            $errors[] = 'PHP ZipArchive eklentisi aktif değil.';
        }
        if (!$hasDownload) {
            $errors[] = 'PHP cURL veya allow_url_fopen desteği bulunamadı.';
        }
        if (!$allWritable) {
            $errors[] = 'Kök dizin veya sistem klasörleri (app, core, storage) yazılabilir değil. Dosya izinlerini (chmod) kontrol edin.';
        }

        return [
            'ready'            => empty($errors),
            'zip_archive'      => $hasZip,
            'download_support' => $hasDownload,
            'writable_root'    => $rootWritable,
            'writable_all'     => $allWritable,
            'error'            => !empty($errors) ? implode(' ', $errors) : null,
        ];
    }

    /**
     * GitHub Releases üzerindeki en son ZIP paketini indirip sisteme uygular.
     *
     * @param bool $force Sürüm zaten güncel olsa bile yeniden kurulumu zorla
     * @return array [success => bool, message => string, error => string|null, ...]
     */
    public static function applyUpdate(bool $force = false): array
    {
        // 1. Ortam ve yetki kontrolleri
        $env = self::checkEnvironment();
        if (!$env['ready']) {
            return [
                'success' => false,
                'error'   => $env['error'] ?? 'Sunucu ortamı otomatik güncelleme için hazır değil.',
            ];
        }

        // Zaman aşımı ve bellek sınırlarını genişlet
        @set_time_limit(300);
        @ini_set('memory_limit', '256M');

        // 2. Güncelleme bilgilerini al
        $info = self::check(true);
        if (!empty($info['error'])) {
            return [
                'success' => false,
                'error'   => $info['error'],
            ];
        }

        if (!$force && empty($info['has_update'])) {
            return [
                'success' => false,
                'error'   => 'Sisteminiz zaten en güncel sürümde (v' . ($info['current_version'] ?? '') . ').',
            ];
        }

        $downloadUrl = (string) ($info['download_url'] ?? '');
        if ($downloadUrl === '') {
            return [
                'success' => false,
                'error'   => 'GitHub sürümüne ait indirilebilir ZIP paketi bulunamadı.',
            ];
        }

        // 3. İndirme hedefi
        $targetVersion = $info['latest_version'];
        $cacheDir = BVLOG_ROOT . '/storage/cache';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }

        $tempZip = $cacheDir . '/update_v' . preg_replace('/[^a-zA-Z0-9._-]/', '', $targetVersion) . '.zip';
        if (is_file($tempZip)) {
            @unlink($tempZip);
        }

        $downloaded = self::downloadPackage($downloadUrl, $tempZip);
        if (!$downloaded || !is_file($tempZip) || filesize($tempZip) < 10000) {
            if (is_file($tempZip)) {
                @unlink($tempZip);
            }
            return [
                'success' => false,
                'error'   => 'Güncelleme paketi GitHub üzerinden tam olarak indirilemedi. Lütfen internet bağlantınızı kontrol edip tekrar deneyin.',
            ];
        }

        // 4. ZIP arşivi bütünlük ve güvenlik denetimi
        $zip = new ZipArchive();
        $res = $zip->open($tempZip);
        if ($res !== true) {
            @unlink($tempZip);
            return [
                'success' => false,
                'error'   => 'İndirilen paket geçerli bir ZIP arşivi değil veya dosya bozulmuş.',
            ];
        }

        $hasFrameworkMarker = false;
        $numFiles = $zip->numFiles;

        for ($i = 0; $i < $numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name === false) {
                continue;
            }
            $cleanName = str_replace('\\', '/', $name);

            // Zip Slip (dizin atlama) denetimi
            if (str_contains($cleanName, '../') || str_starts_with($cleanName, '/')) {
                $zip->close();
                @unlink($tempZip);
                return [
                    'success' => false,
                    'error'   => 'Güvenlik İhlali: ZIP paketi geçersiz dosya yolu içeriyor.',
                ];
            }

            if ($cleanName === 'index.php' || $cleanName === 'core/Autoloader.php') {
                $hasFrameworkMarker = true;
            }
        }

        if (!$hasFrameworkMarker) {
            $zip->close();
            @unlink($tempZip);
            return [
                'success' => false,
                'error'   => 'Geçersiz güncelleme paketi: Temel sistem dosyaları tespit edilemedi.',
            ];
        }

        // 5. Dosyaları güvenle çıkart ve üzerine yaz
        // Korumalı yollar: Kullanıcının özel ayar, yükleme ve kilit dosyaları ASLA ezilmez
        $protectedPaths = [
            'config/database.php',
            'storage/installed.lock',
            'storage/quarantine.json',
            '.env',
        ];

        $updatedFilesCount = 0;

        try {
            for ($i = 0; $i < $numFiles; $i++) {
                $entryName = $zip->getNameIndex($i);
                if ($entryName === false) {
                    continue;
                }

                $normalized = ltrim(str_replace('\\', '/', $entryName), '/');
                if ($normalized === '' || str_contains($normalized, '../')) {
                    continue;
                }

                // Korumalı dosyaları ve yüklemeleri atla
                if (in_array($normalized, $protectedPaths, true) || str_starts_with($normalized, 'storage/uploads/')) {
                    continue;
                }

                $destPath = BVLOG_ROOT . '/' . $normalized;

                if (str_ends_with($normalized, '/')) {
                    if (!is_dir($destPath)) {
                        @mkdir($destPath, 0755, true);
                    }
                } else {
                    $parentDir = dirname($destPath);
                    if (!is_dir($parentDir)) {
                        @mkdir($parentDir, 0755, true);
                    }

                    $stream = $zip->getStream($entryName);
                    if ($stream) {
                        $outFp = @fopen($destPath, 'wb');
                        if ($outFp) {
                            stream_copy_to_stream($stream, $outFp);
                            fclose($outFp);
                            $updatedFilesCount++;
                        }
                        fclose($stream);
                    }
                }
            }
            $zip->close();
        } catch (Throwable $e) {
            $zip->close();
            @unlink($tempZip);
            return [
                'success' => false,
                'error'   => 'Dosyalar açılırken bir hata oluştu: ' . $e->getMessage(),
            ];
        }

        // Geçici ZIP dosyasını temizle
        @unlink($tempZip);

        // 6. Veritabanını ve şemayı yeni sürüme yükselt
        try {
            Migrator::checkAndUpgrade($targetVersion, $force);
        } catch (Throwable $e) {
            // Migrator logu zaten Core\Migrator içinde tutuluyor
        }

        // 7. Önbellekleri ve OPcache'i temizle
        @unlink(self::CACHE_FILE);

        $cacheFiles = glob(BVLOG_ROOT . '/storage/cache/*');
        if (is_array($cacheFiles)) {
            foreach ($cacheFiles as $cf) {
                if (is_file($cf) && !str_ends_with($cf, '.gitkeep')) {
                    @unlink($cf);
                }
            }
        }

        if (class_exists('\\App\\Models\\Setting')) {
            Setting::clearCache();
        }

        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }

        // 8. İşlemi logla
        $logDir = BVLOG_ROOT . '/storage/logs';
        if (is_dir($logDir)) {
            $logLine = sprintf(
                "[%s] AUTO-UPDATE: %s -> %s | Güncellenen Dosya: %d\n",
                date('Y-m-d H:i:s'),
                $info['current_version'],
                $targetVersion,
                $updatedFilesCount
            );
            @file_put_contents($logDir . '/update.log', $logLine, FILE_APPEND);
        }

        return [
            'success'         => true,
            'from_version'    => $info['current_version'],
            'to_version'      => $targetVersion,
            'updated_files'   => $updatedFilesCount,
            'message'         => 'Bvlog başarıyla v' . $targetVersion . ' sürümüne güncellendi!',
        ];
    }

    private static function downloadPackage(string $url, string $destPath): bool
    {
        $userAgent = 'Bvlog-CMS/' . (setting('db_version', '1.0.0')) . ' (PHP ' . PHP_VERSION . ')';

        // 1. Önce cURL ile akış (streaming) indirme dene
        if (function_exists('curl_init')) {
            $fp = @fopen($destPath, 'w+b');
            if ($fp) {
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_FILE           => $fp,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_MAXREDIRS      => 5,
                    CURLOPT_TIMEOUT        => 180,
                    CURLOPT_USERAGENT      => $userAgent,
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_SSL_VERIFYHOST => 2,
                ]);

                $success = curl_exec($ch);
                $errno = curl_errno($ch);

                // SSL sertifika paketinde sorun varsa güvenli geri çekilme
                if (($errno === 60 || $errno === 77) && !$success) {
                    rewind($fp);
                    ftruncate($fp, 0);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                    $success = curl_exec($ch);
                }

                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                fclose($fp);

                if ($success && $httpCode === 200 && is_file($destPath) && filesize($destPath) > 10000) {
                    return true;
                }
            }
        }

        // 2. cURL başarısız olursa stream context ile dene
        $ctx = stream_context_create([
            'http' => [
                'header'          => "User-Agent: {$userAgent}\r\n",
                'timeout'         => 180,
                'follow_location' => 1,
            ],
            'ssl' => [
                'verify_peer'      => true,
                'verify_peer_name' => true,
            ],
        ]);

        $in = @fopen($url, 'rb', false, $ctx);
        if ($in) {
            $out = @fopen($destPath, 'wb');
            if ($out) {
                stream_copy_to_stream($in, $out);
                fclose($out);
            }
            fclose($in);

            if (is_file($destPath) && filesize($destPath) > 10000) {
                return true;
            }
        }

        return false;
    }
}
