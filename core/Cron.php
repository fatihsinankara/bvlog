<?php

namespace Core;

use App\Models\Post;
use App\Models\Setting;
use Throwable;

class Cron
{
    private static array $tasks = [];
    private static bool $booted = false;

    /**
     * Yeni bir zamanlanmış görev kaydeder.
     * Eklentiler `cron_register` kancası (Hook) içinden bu metodu çağırabilir.
     *
     * @param string $id Görev benzersiz kimliği (ör. 'publish_scheduled_posts')
     * @param array{
     *   title: string,
     *   description?: string,
     *   interval?: int,
     *   interval_label?: string,
     *   callback: callable,
     *   source?: string
     * } $config
     */
    public static function register(string $id, array $config): void
    {
        if (empty($id) || empty($config['callback']) || !is_callable($config['callback'])) {
            return;
        }

        $interval = max(60, (int) ($config['interval'] ?? 3600));

        self::$tasks[$id] = [
            'id'             => $id,
            'title'          => (string) ($config['title'] ?? $id),
            'description'    => (string) ($config['description'] ?? ''),
            'interval'       => $interval,
            'interval_label' => (string) ($config['interval_label'] ?? self::formatIntervalLabel($interval)),
            'callback'       => $config['callback'],
            'source'         => (string) ($config['source'] ?? 'Çekirdek'),
        ];
    }

    /**
     * Çekirdek görevleri ve eklenti görevlerini yükler.
     */
    public static function bootTasks(): void
    {
        if (self::$booted) {
            return;
        }
        self::$booted = true;

        // 1. Zamanlanmış Blog Yazılarını Yayınlama Görevi (Her Dakika)
        self::register('publish_scheduled_posts', [
            'title'          => 'Zamanlanmış Yazıları Yayınla',
            'description'    => 'Yayın tarihi ve saati gelen zamanlanmış (scheduled) blog yazılarını otomatik olarak yayına alır.',
            'interval'       => 60,
            'interval_label' => 'Her Dakika',
            'source'         => 'Çekirdek',
            'callback'       => function (): array {
                $published = Post::publishDueScheduledPosts();
                $count = count($published);
                if ($count > 0) {
                    $titles = array_map(fn($p) => '“' . $p['title'] . '”', array_slice($published, 0, 3));
                    $suffix = $count > 3 ? ' ve ' . ($count - 3) . ' yazı daha' : '';
                    return [
                        'affected' => $count,
                        'message'  => "{$count} zamanlanmış yazı yayına alındı: " . implode(', ', $titles) . $suffix,
                    ];
                }
                return [
                    'affected' => 0,
                    'message'  => 'Yayın zamanı gelmiş bekleyen yazı bulunmuyor.',
                ];
            },
        ]);

        // 2. Arka Plan Sürüm ve Güncelleme Denetimi (12 Saatte Bir)
        self::register('check_github_updates', [
            'title'          => 'Sistem Güncelleme Denetimi',
            'description'    => 'GitHub Releases üzerinden yeni bir Bvlog sürümü olup olmadığını arka planda denetler.',
            'interval'       => 43200,
            'interval_label' => '12 Saatte Bir',
            'source'         => 'Çekirdek',
            'callback'       => function (): array {
                $info = Updater::check(true);
                if (!empty($info['error'])) {
                    return [
                        'affected' => 0,
                        'message'  => 'Güncelleme sunucusuna ulaşılamadı: ' . $info['error'],
                    ];
                }
                if (!empty($info['has_update'])) {
                    return [
                        'affected' => 1,
                        'message'  => 'Yeni Bvlog sürümü mevcut: v' . ($info['latest_version'] ?? ''),
                    ];
                }
                return [
                    'affected' => 0,
                    'message'  => 'Sistem güncel (v' . ($info['current_version'] ?? '1.3.2') . ').',
                ];
            },
        ]);

        // 3. Geçici Önbellek ve Hız Sınırı (RateLimiter) Temizliği (Günlük)
        self::register('cleanup_system_cache', [
            'title'          => 'Sistem & Önbellek Bakımı',
            'description'    => 'Süresi dolmuş IP hız sınırı (RateLimiter) kayıtlarını ve eski geçici güncelleme dosyalarını temizler.',
            'interval'       => 86400,
            'interval_label' => 'Günlük',
            'source'         => 'Çekirdek',
            'callback'       => function (): array {
                $cleaned = self::performSystemCleanup();
                return [
                    'affected' => $cleaned,
                    'message'  => $cleaned > 0
                        ? "Sistem bakımı tamamlandı ({$cleaned} eski kayıt/geçici dosya temizlendi)."
                        : 'Sistem bakımı tamamlandı, temizlenecek eski dosya bulunmadı.',
                ];
            },
        ]);

        // Eklentilerin kendi cron görevlerini kaydetmesi için kanca tetikle
        Hook::doAction('cron_register');
    }

    /**
     * Zamanı gelen (veya zorlanan) görevleri çalıştırır.
     *
     * @param bool        $forceAll   True ise çalışma aralığı beklenmeden tüm görevler çalıştırılır
     * @param string|null $onlyTaskId Belirtilirse yalnızca ilgili görev çalıştırılır
     * @param string      $trigger    Tetikleyici kaynağı ('cli', 'http', 'manual', 'web')
     */
    public static function run(bool $forceAll = false, ?string $onlyTaskId = null, string $trigger = 'cli'): array
    {
        $cacheDir = BVLOG_ROOT . '/storage/cache';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }

        $lockFile = $cacheDir . '/cron.lock';
        $lockFp = @fopen($lockFile, 'c+');
        if ($lockFp && !@flock($lockFp, LOCK_EX | LOCK_NB)) {
            fclose($lockFp);
            return [
                'success'  => false,
                'locked'   => true,
                'ran_at'   => date('Y-m-d H:i:s'),
                'trigger'  => $trigger,
                'executed' => [],
                'message'  => 'Başka bir Cron işlemi şu anda çalışıyor.',
            ];
        }

        $startTotal = microtime(true);
        self::bootTasks();

        $states = Setting::getJson('cron_task_states', []);
        $now = time();
        $nowStr = date('Y-m-d H:i:s', $now);

        $executed = [];
        $skipped = [];
        $errorsCount = 0;

        foreach (self::$tasks as $taskId => $task) {
            if ($onlyTaskId !== null && $onlyTaskId !== '' && $taskId !== $onlyTaskId) {
                continue;
            }

            if ($trigger === 'web' && $taskId === 'check_github_updates') {
                $skipped[] = $taskId;
                continue;
            }

            $state = $states[$taskId] ?? [];
            $lastRunTs = !empty($state['last_run_at']) ? (int) strtotime((string) $state['last_run_at']) : 0;
            $isDue = $forceAll || ($onlyTaskId !== null && $onlyTaskId !== '') || (($now - $lastRunTs) >= $task['interval']);

            if (!$isDue) {
                $skipped[] = $taskId;
                continue;
            }

            $t0 = microtime(true);
            $status = 'success';
            $message = 'Görev başarıyla tamamlandı.';
            $affected = 0;

            try {
                $res = call_user_func($task['callback']);
                if (is_array($res)) {
                    $message = (string) ($res['message'] ?? $message);
                    $affected = (int) ($res['affected'] ?? 0);
                } elseif (is_string($res) && $res !== '') {
                    $message = $res;
                }
            } catch (Throwable $e) {
                $status = 'error';
                $errorsCount++;
                $message = 'Hata: ' . $e->getMessage();
                self::logToFile("ERROR [{$taskId}]: " . $e->getMessage());
            }

            $durationMs = (int) round((microtime(true) - $t0) * 1000);

            $states[$taskId] = [
                'last_run_at'      => date('Y-m-d H:i:s'),
                'last_status'      => $status,
                'last_duration_ms' => $durationMs,
                'last_message'     => $message,
                'last_trigger'     => $trigger,
                'run_count'        => ((int) ($state['run_count'] ?? 0)) + 1,
            ];

            $executed[$taskId] = [
                'id'          => $taskId,
                'title'       => $task['title'],
                'status'      => $status,
                'duration_ms' => $durationMs,
                'affected'    => $affected,
                'message'     => $message,
            ];
        }

        $totalDurationMs = (int) round((microtime(true) - $startTotal) * 1000);

        // Son çalışma bilgilerini kaydet
        Setting::setJson('cron_task_states', $states);

        $summary = [
            'ran_at'         => $nowStr,
            'trigger'        => $trigger,
            'executed_count' => count($executed),
            'skipped_count'  => count($skipped),
            'errors_count'   => $errorsCount,
            'duration_ms'    => $totalDurationMs,
        ];
        Setting::setJson('cron_last_summary', $summary);

        // Son çalışma zaman damgasını hafif önbellek dosyasına da yaz
        @file_put_contents($cacheDir . '/cron_last_tick.txt', (string) $now);

        // Çalışma geçmişine kaydet (en az 1 görev çalıştıysa veya manuel/CLI/HTTP çağrısıysa)
        if (!empty($executed) || $trigger !== 'web') {
            $history = Setting::getJson('cron_history', []);
            $messages = [];
            foreach ($executed as $ex) {
                $messages[] = $ex['title'] . ': ' . $ex['message'];
            }
            if (empty($messages)) {
                $messages[] = 'Zamanı gelen görev yok (tüm görevler güncel).';
            }

            array_unshift($history, [
                'ran_at'         => $nowStr,
                'trigger'        => $trigger,
                'executed_count' => count($executed),
                'errors_count'   => $errorsCount,
                'status'         => $errorsCount > 0 ? 'error' : 'success',
                'duration_ms'    => $totalDurationMs,
                'summary'        => implode(' | ', $messages),
            ]);

            $history = array_slice($history, 0, 25);
            Setting::setJson('cron_history', $history);
        }

        if ($lockFp) {
            @flock($lockFp, LOCK_UN);
            fclose($lockFp);
        }

        ViewCounter::flush();

        return [
            'success'     => $errorsCount === 0,
            'ran_at'      => $nowStr,
            'trigger'     => $trigger,
            'duration_ms' => $totalDurationMs,
            'executed'    => array_values($executed),
            'skipped'     => $skipped,
        ];
    }

    /**
     * Ziyaretçi tetiklemeli yedek mod (Web-Cron) aktifse dakikada en fazla 1 kez çalıştırır.
     */
    public static function runWebFallbackIfNeeded(): void
    {
        if (setting('cron_web_fallback', '0') !== '1') {
            return;
        }

        $tickFile = BVLOG_ROOT . '/storage/cache/cron_last_tick.txt';
        if (is_file($tickFile)) {
            $lastTick = (int) @file_get_contents($tickFile);
            if ((time() - $lastTick) < 60) {
                return;
            }
        }

        // Gorevler yanit gonderildikten sonra calisir; ziyaretci beklemez.
        register_shutdown_function(static function (): void {
            self::finishResponse();
            try {
                self::run(false, null, 'web');
            } catch (Throwable $e) {
                // Ziyaretçi isteğini kesintiye uğratma
            }
        });
    }

    /**
     * Yaniti istemciye gonderip baglantiyi kapatir (PHP-FPM, LiteSpeed).
     * Oturum kilidi de birakilir; aksi halde ayni kullanicinin sonraki istegi
     * gorevler bitene kadar bekler. Desteklenmeyen SAPI'lerde gorevler eskisi
     * gibi istegin sonunda calisir.
     */
    private static function finishResponse(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        ignore_user_abort(true);

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } elseif (function_exists('litespeed_finish_request')) {
            litespeed_finish_request();
        }
    }

    /**
     * Gizli Cron güvenlik anahtarını (token) döndürür; yoksa oluşturur.
     */
    public static function getToken(): string
    {
        $token = (string) setting('cron_token', '');
        if (strlen($token) < 16) {
            $token = bin2hex(random_bytes(24));
            Setting::set('cron_token', $token);
        }
        return $token;
    }

    /**
     * Gizli Cron güvenlik anahtarını yeniler.
     */
    public static function regenerateToken(): string
    {
        $token = bin2hex(random_bytes(24));
        Setting::set('cron_token', $token);
        return $token;
    }

    /**
     * Yönetim paneli için görev listesini ve mevcut durumlarını birleştirerek döndürür.
     */
    public static function getTasksWithState(): array
    {
        self::bootTasks();
        $states = Setting::getJson('cron_task_states', []);
        $now = time();
        $result = [];

        foreach (self::$tasks as $id => $task) {
            $st = $states[$id] ?? [];
            $lastRunAt = $st['last_run_at'] ?? null;
            $lastRunTs = $lastRunAt ? (int) strtotime((string) $lastRunAt) : 0;
            $nextRunTs = $lastRunTs > 0 ? ($lastRunTs + $task['interval']) : $now;

            $result[] = [
                'id'               => $id,
                'title'            => $task['title'],
                'description'      => $task['description'],
                'interval'         => $task['interval'],
                'interval_label'   => $task['interval_label'],
                'source'           => $task['source'],
                'last_run_at'      => $lastRunAt,
                'next_run_at'      => date('Y-m-d H:i:s', $nextRunTs),
                'is_due'           => ($now >= $nextRunTs),
                'last_status'      => $st['last_status'] ?? null,
                'last_duration_ms' => $st['last_duration_ms'] ?? null,
                'last_message'     => $st['last_message'] ?? 'Henüz çalıştırılmadı.',
                'last_trigger'     => $st['last_trigger'] ?? null,
                'run_count'        => (int) ($st['run_count'] ?? 0),
            ];
        }

        return $result;
    }

    public static function getStatusSummary(): array
    {
        $summary = Setting::getJson('cron_last_summary', []);
        return [
            'ran_at'         => $summary['ran_at'] ?? null,
            'trigger'        => $summary['trigger'] ?? null,
            'trigger_label'  => self::formatTriggerLabel($summary['trigger'] ?? null),
            'executed_count' => (int) ($summary['executed_count'] ?? 0),
            'errors_count'   => (int) ($summary['errors_count'] ?? 0),
            'duration_ms'    => (int) ($summary['duration_ms'] ?? 0),
        ];
    }

    public static function getHistory(): array
    {
        $history = Setting::getJson('cron_history', []);
        foreach ($history as &$item) {
            $item['trigger_label'] = self::formatTriggerLabel($item['trigger'] ?? null);
        }
        return $history;
    }

    /**
     * Sunucunun işletim sistemi, PHP ikili dosyası (binary) ve kök dizin yoluna göre
     * cPanel / Plesk / SSH uyumlu hazır Cron komutlarını üretir.
     */
    public static function getServerCommands(): array
    {
        $token = self::getToken();
        $cronUrl = url('/cron');
        $cronFile = str_replace('\\', '/', BVLOG_ROOT) . '/cron.php';

        $phpCli = self::detectPhpCliBinary();
        $curl = 'curl -fsS -H ' . escapeshellarg('X-Cron-Token: ' . $token) . ' ' . escapeshellarg($cronUrl) . ' >/dev/null 2>&1';
        $wget = 'wget -q -O - --header=' . escapeshellarg('X-Cron-Token: ' . $token) . ' ' . escapeshellarg($cronUrl) . ' >/dev/null 2>&1';

        return [
            'php_binary'   => $phpCli,
            'cron_file'    => $cronFile,
            'cron_url'     => $cronUrl . '?token=' . urlencode($token),
            'token'        => $token,
            'os_family'    => PHP_OS_FAMILY,
            'cpanel_cli'   => "{$phpCli} -q {$cronFile} >/dev/null 2>&1",
            'crontab_cli'  => "* * * * * {$phpCli} -q {$cronFile} >/dev/null 2>&1",
            'cpanel_curl'  => $curl,
            'crontab_curl' => '* * * * * ' . $curl,
            'cpanel_wget'  => $wget,
        ];
    }

    /**
     * Sunucudaki gerçek PHP CLI yolunu tespit eder (FPM/CGI çalışıyorsa CLI karşılığını bulur).
     */
    private static function detectPhpCliBinary(): string
    {
        $binary = defined('PHP_BINARY') && PHP_BINARY !== '' ? str_replace('\\', '/', PHP_BINARY) : '';

        // Eğer PHP-FPM veya PHP-CGI üzerinden çalışıyorsa aynı dizindeki 'php' CLI ikilisini kontrol et
        if ($binary !== '' && (str_contains($binary, 'php-fpm') || str_contains($binary, 'php-cgi') || str_contains($binary, 'lsphp'))) {
            if (defined('PHP_BINDIR') && PHP_BINDIR !== '') {
                $candidate = str_replace('\\', '/', PHP_BINDIR) . '/php';
                if (@is_executable($candidate) || @is_file($candidate)) {
                    return $candidate;
                }
            }
            // cPanel yaygın PHP CLI yolu
            if (@is_file('/usr/local/bin/php')) {
                return '/usr/local/bin/php';
            }
            if (@is_file('/usr/bin/php')) {
                return '/usr/bin/php';
            }
            return '/usr/local/bin/php';
        }

        if ($binary !== '') {
            return $binary;
        }

        return PHP_OS_FAMILY === 'Windows' ? 'php' : '/usr/local/bin/php';
    }

    public static function formatTriggerLabel(?string $trigger): string
    {
        return match ($trigger) {
            'cli'    => 'Sunucu CLI (cron.php)',
            'http'   => 'HTTP Cron URL',
            'manual' => 'Yönetim Paneli (Manuel)',
            'web'    => 'Ziyaretçi Trafiği (Web-Cron)',
            default  => 'Henüz Çalışmadı',
        };
    }

    private static function formatIntervalLabel(int $seconds): string
    {
        if ($seconds < 120) {
            return 'Her Dakika';
        }
        if ($seconds < 3600) {
            return 'Her ' . round($seconds / 60) . ' Dakikada Bir';
        }
        if ($seconds === 3600) {
            return 'Saatlik';
        }
        if ($seconds < 86400) {
            return round($seconds / 3600) . ' Saatte Bir';
        }
        return 'Günlük';
    }

    private static function performSystemCleanup(): int
    {
        $cleaned = 0;
        $now = time();

        // 1. 24 saatten eski RateLimiter kayıtlarını temizle
        $rateFile = BVLOG_ROOT . '/storage/logs/ratelimit.json';
        if (is_file($rateFile)) {
            $raw = @file_get_contents($rateFile);
            $data = $raw ? json_decode($raw, true) : null;
            if (is_array($data)) {
                $before = count($data);
                foreach ($data as $k => $item) {
                    if (!isset($item['first_attempt']) || ($now - (int) $item['first_attempt']) > 86400) {
                        unset($data[$k]);
                        $cleaned++;
                    }
                }
                if (count($data) !== $before) {
                    @file_put_contents($rateFile, json_encode($data, JSON_UNESCAPED_SLASHES));
                }
            }
        }

        // 2. Süresi dolmuş uygulama önbellek (bvcache_*.cache) dosyalarını temizle
        if (class_exists('\\Core\\Cache')) {
            $cleaned += Cache::purgeExpired();
        }

        // 3. 1 saatten eski kalıntı güncelleme ZIP dosyalarını temizle
        $tempZips = glob(BVLOG_ROOT . '/storage/cache/update_*.zip');
        if (is_array($tempZips)) {
            foreach ($tempZips as $zf) {
                if (is_file($zf) && ($now - (int) @filemtime($zf)) > 3600) {
                    if (@unlink($zf)) {
                        $cleaned++;
                    }
                }
            }
        }

        // 4. Süresi dolmuş parola sıfırlama bağlantıları
        try {
            $cleaned += Database::delete('password_resets', 'expires_at < :now', ['now' => date('Y-m-d H:i:s', $now)]);
        } catch (Throwable $e) {
            // Tablo henüz yoksa geç
        }

        return $cleaned;
    }

    private static function logToFile(string $line): void
    {
        $logDir = BVLOG_ROOT . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        @file_put_contents(
            $logDir . '/cron.log',
            '[' . date('Y-m-d H:i:s') . '] ' . $line . PHP_EOL,
            FILE_APPEND
        );
    }
}
