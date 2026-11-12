<?php

namespace Core;

use App\Controllers\SeoController;
use App\Models\Category;
use App\Models\CvEducation;
use App\Models\CvExperience;
use App\Models\CvSkill;
use App\Models\Post;
use App\Models\Project;
use App\Models\Setting;
use App\Models\User;
use Throwable;

class Cache
{
    private const FILE_PREFIX = 'bvcache_';
    private const FILE_EXT = '.cache';

    /**
     * Aynı HTTP isteği içinde tekrar eden dosya okumalarını önleyen bellek içi (L1) önbellek.
     *
     * @var array<string, array{hit: bool, value: mixed}>
     */
    private static array $memory = [];

    /**
     * Önbellek grupları ve yönetim paneli meta bilgileri.
     */
    public const GROUPS = [
        'settings' => [
            'title'       => 'Sistem & Yazar Ayarları',
            'icon'        => 'fa-solid fa-sliders',
            'description' => 'Site genel yapılandırması, modül durumları ve yazar profil bilgileri.',
        ],
        'home' => [
            'title'       => 'Anasayfa Verileri',
            'icon'        => 'fa-solid fa-house',
            'description' => 'Anasayfa vitrin blokları, öne çıkan yazılar ve özet içerik listeleri.',
        ],
        'blog' => [
            'title'       => 'Blog, Kategoriler & Yazılar',
            'icon'        => 'fa-solid fa-pen-nib',
            'description' => 'Blog yazı listeleri, kategori arşivleri, tekil yazı detayları, etiketler ve yorumlar.',
        ],
        'portfolio' => [
            'title'       => 'Portfolyo & Projeler',
            'icon'        => 'fa-solid fa-briefcase',
            'description' => 'Portfolyo vitrini ve proje detay sayfaları.',
        ],
        'cv' => [
            'title'       => 'CV & Özgeçmiş',
            'icon'        => 'fa-solid fa-id-card',
            'description' => 'İş deneyimleri, eğitim geçmişi, teknik yetenek grupları ve yapısal CV şeması.',
        ],
        'feeds' => [
            'title'       => 'SEO Sitemap & RSS Beslemeleri',
            'icon'        => 'fa-solid fa-rss',
            'description' => 'Arama motorları için üretilen sitemap.xml ve okuyucular için rss.xml çıktıları.',
        ],
    ];

    public static function getCacheDir(): string
    {
        $dir = BVLOG_ROOT . '/storage/cache';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir;
    }

    public static function getGroups(): array
    {
        return self::GROUPS;
    }

    public static function isEnabled(): bool
    {
        if (!function_exists('is_installed') || !is_installed()) {
            return false;
        }
        return setting('cache_enabled', '1') === '1';
    }

    public static function defaultTtl(): int
    {
        $ttl = (int) setting('cache_ttl', '3600');
        return max(60, min(604800, $ttl > 0 ? $ttl : 3600));
    }

    public static function isFeedsEnabled(): bool
    {
        return self::isEnabled() && setting('cache_feeds', '1') === '1';
    }

    public static function isAutoFlushEnabled(): bool
    {
        return setting('cache_auto_flush', '1') === '1';
    }

    /**
     * Düşük seviyeli (Setting sınıfından bağımsız, özyineleme yapmayan) önbellek okuma.
     */
    public static function getRaw(string $group, string $key): mixed
    {
        $group = self::sanitizeGroup($group);
        $memKey = $group . ':' . $key;

        if (isset(self::$memory[$memKey]) && self::$memory[$memKey]['hit']) {
            return self::$memory[$memKey]['value'];
        }

        $file = self::resolveFilePath($group, $key);
        if (!is_file($file)) {
            return null;
        }

        $raw = @file_get_contents($file);
        if (!is_string($raw) || $raw === '') {
            return null;
        }

        $envelope = json_decode($raw, true);
        if (!is_array($envelope) || !array_key_exists('payload', $envelope)) {
            return null;
        }

        $expiresAt = (int) ($envelope['expires_at'] ?? 0);
        if ($expiresAt > 0 && $expiresAt <= time()) {
            @unlink($file);
            unset(self::$memory[$memKey]);
            return null;
        }

        self::$memory[$memKey] = [
            'hit'   => true,
            'value' => $envelope['payload'],
        ];

        return $envelope['payload'];
    }

    /**
     * Düşük seviyeli (Setting sınıfından bağımsız) atomik önbellek yazma.
     */
    public static function putRaw(string $group, string $key, mixed $value, int $ttl = 3600, string $label = ''): bool
    {
        $group = self::sanitizeGroup($group);
        $memKey = $group . ':' . $key;
        $now = time();
        $ttl = max(60, $ttl);

        $envelope = [
            'key'        => $key,
            'group'      => $group,
            'label'      => $label !== '' ? $label : $key,
            'created_at' => $now,
            'expires_at' => $now + $ttl,
            'ttl'        => $ttl,
            'payload'    => $value,
        ];

        $json = json_encode($envelope, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return false;
        }

        $file = self::resolveFilePath($group, $key);
        $written = @file_put_contents($file, $json, LOCK_EX) !== false;

        if ($written) {
            self::$memory[$memKey] = [
                'hit'   => true,
                'value' => $value,
            ];
        }

        return $written;
    }

    /**
     * Düşük seviyeli önbellek anahtarı silme.
     */
    public static function forgetRaw(string $group, string $key): bool
    {
        $group = self::sanitizeGroup($group);
        $memKey = $group . ':' . $key;
        unset(self::$memory[$memKey]);

        $file = self::resolveFilePath($group, $key);
        if (is_file($file)) {
            return @unlink($file);
        }
        return false;
    }

    /**
     * Önbellekten veri okur; yoksa veya süresi dolmuşsa $default döndürür.
     */
    public static function get(string $key, mixed $default = null, string $group = 'blog'): mixed
    {
        if (!self::isEnabled()) {
            return $default;
        }

        $val = self::getRaw($group, $key);
        return $val !== null ? $val : $default;
    }

    /**
     * Önbelleğe veri yazar.
     */
    public static function put(string $key, mixed $value, ?int $ttl = null, string $group = 'blog', string $label = ''): bool
    {
        if (!self::isEnabled()) {
            return false;
        }

        $effectiveTtl = $ttl !== null && $ttl > 0 ? $ttl : self::defaultTtl();
        return self::putRaw($group, $key, $value, $effectiveTtl, $label);
    }

    /**
     * Önbellekte varsa döndürür, yoksa $callback çalıştırıp sonucu önbelleğe kaydederek döndürür.
     */
    public static function remember(string $key, callable $callback, ?int $ttl = null, string $group = 'blog', string $label = ''): mixed
    {
        if (!self::isEnabled()) {
            return $callback();
        }

        $group = self::sanitizeGroup($group);
        $memKey = $group . ':' . $key;

        if (isset(self::$memory[$memKey]) && self::$memory[$memKey]['hit']) {
            return self::$memory[$memKey]['value'];
        }

        $cached = self::getRaw($group, $key);
        if ($cached !== null) {
            return $cached;
        }

        $value = $callback();
        if ($value !== null) {
            $effectiveTtl = $ttl !== null && $ttl > 0 ? $ttl : self::defaultTtl();
            self::putRaw($group, $key, $value, $effectiveTtl, $label);
        }

        return $value;
    }

    /**
     * Belirtilen önbellek anahtarını siler.
     */
    public static function forget(string $key, string $group = 'blog'): bool
    {
        return self::forgetRaw($group, $key);
    }

    /**
     * Yönetim panelinden dosya adına göre tekil önbellek kaydını güvenle siler.
     */
    public static function forgetByFilename(string $filename): bool
    {
        $clean = basename(trim($filename));
        if (!str_starts_with($clean, self::FILE_PREFIX) || !str_ends_with($clean, self::FILE_EXT)) {
            return false;
        }

        $path = self::getCacheDir() . '/' . $clean;
        if (!is_file($path)) {
            return false;
        }

        self::$memory = [];
        return @unlink($path);
    }

    /**
     * Belirli bir gruba ait tüm önbellek dosyalarını temizler.
     */
    public static function flushGroup(string $group): int
    {
        $group = self::sanitizeGroup($group);
        $prefix = $group . ':';
        foreach (array_keys(self::$memory) as $mk) {
            if (str_starts_with($mk, $prefix)) {
                unset(self::$memory[$mk]);
            }
        }

        $pattern = self::getCacheDir() . '/' . self::FILE_PREFIX . $group . '_*' . self::FILE_EXT;
        $files = glob($pattern);
        $deleted = 0;

        if (is_array($files)) {
            foreach ($files as $file) {
                if (is_file($file) && @unlink($file)) {
                    $deleted++;
                }
            }
        }

        if ($group === 'settings') {
            Setting::clearCache();
            User::clearAuthorCache();
        }

        return $deleted;
    }

    /**
     * Tüm uygulama önbelleğini (ve opsiyonel olarak güncelleme önbelleğini ve OPcache'i) temizler.
     */
    public static function flushAll(bool $includeSystem = false): int
    {
        self::$memory = [];

        $pattern = self::getCacheDir() . '/' . self::FILE_PREFIX . '*' . self::FILE_EXT;
        $files = glob($pattern);
        $deleted = 0;

        if (is_array($files)) {
            foreach ($files as $file) {
                if (is_file($file) && @unlink($file)) {
                    $deleted++;
                }
            }
        }

        if ($includeSystem) {
            $updateFile = self::getCacheDir() . '/update_check.json';
            if (is_file($updateFile) && @unlink($updateFile)) {
                $deleted++;
            }
        }

        Setting::clearCache();
        User::clearAuthorCache();

        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }

        try {
            if (function_exists('is_installed') && is_installed()) {
                Setting::set('cache_last_cleared_at', date('Y-m-d H:i:s'));
            }
        } catch (Throwable $e) {
            // Sessizce geç
        }

        return $deleted;
    }

    /**
     * Yalnızca süresi dolmuş (expired) önbellek dosyalarını temizler.
     */
    public static function purgeExpired(): int
    {
        $pattern = self::getCacheDir() . '/' . self::FILE_PREFIX . '*' . self::FILE_EXT;
        $files = glob($pattern);
        if (!is_array($files)) {
            return 0;
        }

        $now = time();
        $purged = 0;

        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }
            $raw = @file_get_contents($file);
            $data = $raw ? json_decode($raw, true) : null;
            if (!is_array($data)) {
                if (@unlink($file)) {
                    $purged++;
                }
                continue;
            }

            $expiresAt = (int) ($data['expires_at'] ?? 0);
            if ($expiresAt > 0 && $expiresAt <= $now) {
                if (@unlink($file)) {
                    $purged++;
                }
            }
        }

        return $purged;
    }

    /**
     * Veritabanı tablosunda değişiklik (INSERT / UPDATE / DELETE) olduğunda
     * ilgili önbellek gruplarını otomatik olarak temizler.
     */
    public static function invalidateByTable(string $table): void
    {
        if (!self::isAutoFlushEnabled()) {
            return;
        }

        $groupsToFlush = match ($table) {
            'posts', 'post_categories', 'post_tags', 'tags', 'categories', 'collections', 'collection_posts' => ['blog', 'home', 'feeds'],
            'comments' => ['blog'],
            'projects' => ['portfolio', 'home', 'feeds'],
            'cv_experiences', 'cv_educations', 'cv_skills', 'cv_skill_categories' => ['cv', 'home'],
            'users' => ['settings', 'home', 'blog', 'cv'],
            default => [],
        };

        foreach ($groupsToFlush as $grp) {
            self::flushGroup($grp);
        }
    }

    /**
     * Önbelleği önceden ısıtır (Preload / Warmup):
     * Ziyaretçilerin en sık eriştiği sayfaları ve beslemeleri arka planda önbelleğe alır.
     */
    public static function warmup(): array
    {
        $t0 = microtime(true);
        $warmed = 0;

        // 1. Ayarlar ve Yazar Profili
        Setting::clearCache();
        User::clearAuthorCache();
        Setting::loadAll();
        $author = User::firstAuthor() ?? [];
        $warmed += 2;

        // 2. Kategoriler ve Yazı Sayaçları
        $categories = Category::allWithCounts();
        $warmed++;

        // 3. Anasayfa Verileri
        $limit = max(1, (int) Setting::get('posts_per_page', 10));
        $latestPosts = Post::publishedList($limit, 0);
        $featuredPosts = Post::featuredPosts(3);
        $featuredProjects = is_module_active('portfolio') ? Project::featuredList(4) : [];
        $skillsGrouped = is_module_active('cv') ? CvSkill::groupedByCategory() : [];
        $experiences = is_module_active('cv') ? array_slice(CvExperience::ordered(), 0, 3) : [];

        $homeData = [
            'author'           => $author,
            'heroStyle'        => Setting::get('homepage_hero_style', 'minimal_bio'),
            'postStyle'        => Setting::get('homepage_post_style', 'list'),
            'sections'         => Setting::getJson('homepage_sections', [
                ['id' => 'hero', 'title' => 'Yazar Tanıtım Kartı (Hero)', 'enabled' => true],
                ['id' => 'featured_posts', 'title' => 'Öne Çıkan Yazılar', 'enabled' => true],
                ['id' => 'latest_posts', 'title' => 'Son Blog Yazıları', 'enabled' => true],
                ['id' => 'featured_projects', 'title' => 'Seçkin Portfolyo Projeleri', 'enabled' => true],
                ['id' => 'cv_skills_summary', 'title' => 'Yetenek & Deneyim Özeti', 'enabled' => true],
                ['id' => 'contact_cta', 'title' => 'İletişim Çağrı Kutusu', 'enabled' => true],
            ]),
            'latestPosts'      => $latestPosts,
            'featuredPosts'    => $featuredPosts,
            'categories'       => $categories,
            'featuredProjects' => $featuredProjects,
            'skillsGrouped'    => $skillsGrouped,
            'experiences'      => $experiences,
            'meta'             => [
                'title'       => '',
                'description' => Setting::get('site_description', ''),
            ],
        ];
        self::putRaw('home', 'home:page_data', $homeData, self::defaultTtl(), 'Anasayfa Blok ve İçerik Verileri');
        $warmed++;

        // 4. Blog İlk Sayfa Listesi
        $totalPublished = Post::countPublished(null, '');
        self::putRaw('blog', 'blog:list:total', $totalPublished, self::defaultTtl(), 'Blog Yazı Sayısı');
        self::putRaw('blog', "blog:list:posts:page_1_per_{$limit}", $latestPosts, self::defaultTtl(), 'Blog Yazı Listesi (Sayfa 1)');
        $warmed++;

        // 5. Portfolyo Vitrini
        if (is_module_active('portfolio')) {
            $projects = Project::publishedList();
            self::putRaw('portfolio', 'portfolio:list', $projects, self::defaultTtl(), 'Portfolyo Proje Listesi');
            $warmed++;
        }

        // 6. CV & Özgeçmiş Sayfası
        if (is_module_active('cv')) {
            $allExperiences = CvExperience::ordered();
            $educations = CvEducation::ordered();
            $skillsRaw = CvSkill::ordered();
            $schema = Seo::buildPersonSchema($author, $skillsRaw);

            self::putRaw('cv', 'cv:page_data', [
                'author'        => $author,
                'experiences'   => $allExperiences,
                'educations'    => $educations,
                'skillsGrouped' => $skillsGrouped,
                'schema'        => $schema,
            ], self::defaultTtl(), 'CV & Özgeçmiş Sayfa Verileri');
            $warmed++;
        }

        // 7. Sitemap.xml ve RSS.xml Beslemeleri
        if (setting('cache_feeds', '1') === '1') {
            $sitemapXml = SeoController::generateSitemapXml();
            self::putRaw('feeds', 'feeds:sitemap_xml', $sitemapXml, self::defaultTtl(), 'XML Site Haritası (/sitemap.xml)');

            $rssXml = SeoController::generateRssXml();
            self::putRaw('feeds', 'feeds:rss_xml', $rssXml, self::defaultTtl(), 'RSS İçerik Beslemesi (/rss.xml)');
            $warmed += 2;
        }

        $durationMs = (int) round((microtime(true) - $t0) * 1000);

        return [
            'warmed_count' => $warmed,
            'duration_ms'  => $durationMs,
        ];
    }

    /**
     * Yönetim panelindeki Önbellek Yönetimi sayfası için tüm detaylı istatistikleri,
     * grup özetlerini, kayıt listesini ve OPcache durumunu döndürür.
     */
    public static function getDiagnostics(): array
    {
        $cacheDir = self::getCacheDir();
        $now = time();

        $groups = [];
        foreach (self::GROUPS as $gKey => $gMeta) {
            $groups[$gKey] = array_merge($gMeta, [
                'id'            => $gKey,
                'count'         => 0,
                'active_count'  => 0,
                'expired_count' => 0,
                'bytes'         => 0,
            ]);
        }

        $pattern = $cacheDir . '/' . self::FILE_PREFIX . '*' . self::FILE_EXT;
        $files = glob($pattern) ?: [];

        $items = [];
        $totalBytes = 0;
        $activeFiles = 0;
        $expiredFiles = 0;

        foreach ($files as $filePath) {
            if (!is_file($filePath)) {
                continue;
            }

            $size = (int) @filesize($filePath);
            $mtime = (int) @filemtime($filePath);
            $filename = basename($filePath);

            $raw = @file_get_contents($filePath);
            $data = $raw ? json_decode($raw, true) : null;

            $group = is_array($data) && !empty($data['group']) ? (string) $data['group'] : 'blog';
            if (!isset($groups[$group])) {
                $group = 'blog';
            }

            $key = is_array($data) && !empty($data['key']) ? (string) $data['key'] : $filename;
            $label = is_array($data) && !empty($data['label']) ? (string) $data['label'] : $key;
            $createdAt = is_array($data) && !empty($data['created_at']) ? (int) $data['created_at'] : $mtime;
            $expiresAt = is_array($data) && !empty($data['expires_at']) ? (int) $data['expires_at'] : 0;
            $ttl = is_array($data) && !empty($data['ttl']) ? (int) $data['ttl'] : 0;

            $isExpired = ($expiresAt > 0 && $expiresAt <= $now);
            $remainingSeconds = $expiresAt > 0 ? max(0, $expiresAt - $now) : 0;

            $totalBytes += $size;
            $groups[$group]['count']++;
            $groups[$group]['bytes'] += $size;

            if ($isExpired) {
                $expiredFiles++;
                $groups[$group]['expired_count']++;
            } else {
                $activeFiles++;
                $groups[$group]['active_count']++;
            }

            $items[] = [
                'filename'          => $filename,
                'key'               => $key,
                'group'             => $group,
                'group_title'       => $groups[$group]['title'],
                'group_icon'        => $groups[$group]['icon'],
                'label'             => $label,
                'bytes'             => $size,
                'created_at_ts'     => $createdAt,
                'created_at'        => date('Y-m-d H:i:s', $createdAt),
                'expires_at'        => $expiresAt > 0 ? date('Y-m-d H:i:s', $expiresAt) : 'Süresiz',
                'ttl'               => $ttl,
                'remaining_seconds' => $remainingSeconds,
                'remaining_label'   => $isExpired ? 'Süresi Doldu' : self::formatRemainingTime($remainingSeconds),
                'is_expired'        => $isExpired,
            ];
        }

        // En yeni oluşturulandan eskiye doğru sırala
        usort($items, fn(array $a, array $b) => $b['created_at_ts'] <=> $a['created_at_ts']);

        // Sistem yardımcı önbellek dosyaları (update_check.json ve cron_last_tick.txt)
        $systemFiles = [];
        $updateFile = $cacheDir . '/update_check.json';
        if (is_file($updateFile)) {
            $systemFiles[] = [
                'name'       => 'update_check.json',
                'label'      => 'GitHub Sürüm & Güncelleme Denetimi Önbelleği',
                'bytes'      => (int) @filesize($updateFile),
                'updated_at' => date('Y-m-d H:i:s', (int) @filemtime($updateFile)),
            ];
        }
        $cronTickFile = $cacheDir . '/cron_last_tick.txt';
        if (is_file($cronTickFile)) {
            $systemFiles[] = [
                'name'       => 'cron_last_tick.txt',
                'label'      => 'Web-Cron Son Tetiklenme Zaman Damgası',
                'bytes'      => (int) @filesize($cronTickFile),
                'updated_at' => date('Y-m-d H:i:s', (int) @filemtime($cronTickFile)),
            ];
        }

        $ttl = self::defaultTtl();

        return [
            'enabled'         => self::isEnabled(),
            'ttl'             => $ttl,
            'ttl_label'       => self::formatTtlLabel($ttl),
            'feeds_enabled'   => setting('cache_feeds', '1') === '1',
            'auto_flush'      => self::isAutoFlushEnabled(),
            'last_cleared_at' => setting('cache_last_cleared_at', null),
            'writable'        => is_writable($cacheDir),
            'cache_dir'       => str_replace('\\', '/', $cacheDir),
            'total_files'     => count($items),
            'active_files'    => $activeFiles,
            'expired_files'   => $expiredFiles,
            'total_bytes'     => $totalBytes,
            'groups'          => $groups,
            'items'           => $items,
            'system_files'    => $systemFiles,
            'opcache'         => self::getOpcacheInfo(),
        ];
    }

    public static function formatTtlLabel(int $seconds): string
    {
        return match ($seconds) {
            300    => '5 Dakika',
            900    => '15 Dakika',
            1800   => '30 Dakika',
            3600   => '1 Saat',
            10800  => '3 Saat',
            21600  => '6 Saat',
            43200  => '12 Saat',
            86400  => '24 Saat (1 Gün)',
            604800 => '7 Gün (1 Hafta)',
            default => ($seconds >= 3600 ? round($seconds / 3600, 1) . ' Saat' : round($seconds / 60) . ' Dakika'),
        };
    }

    private static function formatRemainingTime(int $seconds): string
    {
        if ($seconds <= 0) {
            return 'Süresi Doldu';
        }
        if ($seconds < 60) {
            return $seconds . ' sn kaldı';
        }
        if ($seconds < 3600) {
            return ceil($seconds / 60) . ' dk kaldı';
        }
        $hours = floor($seconds / 3600);
        $mins = floor(($seconds % 3600) / 60);
        return $mins > 0 ? "{$hours} sa {$mins} dk kaldı" : "{$hours} saat kaldı";
    }

    private static function getOpcacheInfo(): array
    {
        if (!function_exists('opcache_get_status')) {
            return [
                'available'      => false,
                'enabled'        => false,
                'cached_scripts' => 0,
                'memory_used'    => 0,
                'memory_free'    => 0,
                'hit_rate'       => 0.0,
            ];
        }

        $status = @opcache_get_status(false);
        if (!is_array($status) || empty($status['opcache_enabled'])) {
            return [
                'available'      => true,
                'enabled'        => false,
                'cached_scripts' => 0,
                'memory_used'    => 0,
                'memory_free'    => 0,
                'hit_rate'       => 0.0,
            ];
        }

        return [
            'available'      => true,
            'enabled'        => true,
            'cached_scripts' => (int) ($status['opcache_statistics']['num_cached_scripts'] ?? 0),
            'memory_used'    => (int) ($status['memory_usage']['used_memory'] ?? 0),
            'memory_free'    => (int) ($status['memory_usage']['free_memory'] ?? 0),
            'hit_rate'       => round((float) ($status['opcache_statistics']['opcache_hit_rate'] ?? 0.0), 1),
        ];
    }

    private static function sanitizeGroup(string $group): string
    {
        $clean = preg_replace('/[^a-z0-9_-]/', '', strtolower($group)) ?: 'blog';
        return isset(self::GROUPS[$clean]) ? $clean : 'blog';
    }

    private static function resolveFilePath(string $group, string $key): string
    {
        $slugPart = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key) ?: 'item';
        $slugPart = substr($slugPart, 0, 48);
        $hash = substr(sha1($group . '|' . $key), 0, 12);

        return self::getCacheDir() . '/' . self::FILE_PREFIX . $group . '_' . $slugPart . '_' . $hash . self::FILE_EXT;
    }
}
