<?php

namespace Core;

use App\Models\Setting;

class Migrator
{
    /**
     * FTP / ZIP ile yeni sürüm dosyaları üzerine yazıldığında
     * veritabanı şemasını ve yeni ayar anahtarlarını veri kaybı olmadan
     * otomatik olarak yükseltir.
     */
    public static function checkAndUpgrade(string $targetVersion, bool $force = false): void
    {
        $installedVersion = setting('db_version', '0.0.0');
        if (!$force && version_compare($installedVersion, $targetVersion, '>=')) {
            return;
        }

        try {
            $pdo = Database::getInstance();

            // 1. Yeni eklenen tablolar varsa idempotent olarak (CREATE TABLE IF NOT EXISTS) oluştur
            $schemaFile = BVLOG_ROOT . '/schema.sql';
            if (is_file($schemaFile)) {
                $sql = (string) file_get_contents($schemaFile);
                $pdo->exec($sql);
            }

            // 2. Gelecek sürümler için kademeli şema güncellemeleri (ALTER TABLE vb.)
            self::runVersionedMigrations($pdo, $installedVersion);

            // 3. Veritabanı sürüm bilgisini güncelle ve önbelleği tazele
            Setting::set('db_version', $targetVersion);
            Setting::clearCache();
        } catch (\Throwable $e) {
            // Güncelleme sırasında geçici bir hata oluşursa logla, ziyaretçi ekranını çökertme
            $logDir = BVLOG_ROOT . '/storage/logs';
            if (is_dir($logDir)) {
                @file_put_contents(
                    $logDir . '/upgrade.log',
                    '[' . date('Y-m-d H:i:s') . '] Upgrade Error: ' . $e->getMessage() . PHP_EOL,
                    FILE_APPEND
                );
            }
        }
    }

    private static function runVersionedMigrations(\PDO $pdo, string $fromVersion): void
    {
        if (version_compare($fromVersion, '1.2.0', '<')) {
            $pdo->exec("CREATE TABLE IF NOT EXISTS `cv_skill_categories` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(100) NOT NULL,
                `display_order` INT DEFAULT 0,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            self::addColumnIfNotExists($pdo, 'cv_skills', 'category_id', 'INT NULL AFTER id');

            try {
                $stmt = $pdo->query("SELECT DISTINCT category FROM cv_skills WHERE category IS NOT NULL AND category != ''");
                $distinct = $stmt ? $stmt->fetchAll(\PDO::FETCH_COLUMN) : [];
                $order = 1;
                foreach ($distinct as $catName) {
                    $catName = trim((string) $catName);
                    if ($catName === '') {
                        continue;
                    }
                    $find = $pdo->prepare("SELECT id FROM cv_skill_categories WHERE name = :name LIMIT 1");
                    $find->execute(['name' => $catName]);
                    $catId = $find->fetchColumn();
                    if (!$catId) {
                        $ins = $pdo->prepare("INSERT INTO cv_skill_categories (name, display_order) VALUES (:name, :ord)");
                        $ins->execute(['name' => $catName, 'ord' => $order++]);
                        $catId = $pdo->lastInsertId();
                    }
                    $upd = $pdo->prepare("UPDATE cv_skills SET category_id = :cid WHERE category = :cname AND (category_id IS NULL OR category_id = 0)");
                    $upd->execute(['cid' => $catId, 'cname' => $catName]);
                }
            } catch (\Throwable $e) {
                // Sessizce geç veya logla
            }
        }

        if (version_compare($fromVersion, '1.3.0', '<')) {
            try {
                $pdo->exec("ALTER TABLE `posts` MODIFY COLUMN `status` ENUM('published', 'draft', 'scheduled') DEFAULT 'published'");
            } catch (\Throwable $e) {
                // Sessizce geç
            }

            try {
                $stmtToken = $pdo->prepare("SELECT value FROM settings WHERE key_name = 'cron_token' LIMIT 1");
                $stmtToken->execute();
                $existingToken = $stmtToken->fetchColumn();
                if (empty($existingToken)) {
                    $newToken = bin2hex(random_bytes(24));
                    $insToken = $pdo->prepare("INSERT INTO settings (key_name, value) VALUES ('cron_token', :val) ON DUPLICATE KEY UPDATE value = :val2");
                    $insToken->execute(['val' => $newToken, 'val2' => $newToken]);
                }
            } catch (\Throwable $e) {
                // Sessizce geç
            }

            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS `post_categories` (
                    `post_id` INT NOT NULL,
                    `category_id` INT NOT NULL,
                    `sort_order` INT DEFAULT 1,
                    PRIMARY KEY (`post_id`, `category_id`),
                    FOREIGN KEY (`post_id`) REFERENCES `posts`(`id`) ON DELETE CASCADE,
                    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE,
                    INDEX `idx_post_categories_cat` (`category_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

                $pdo->exec("INSERT IGNORE INTO `post_categories` (`post_id`, `category_id`, `sort_order`)
                            SELECT `id`, `category_id`, 1 FROM `posts` WHERE `category_id` IS NOT NULL AND `category_id` > 0");
            } catch (\Throwable $e) {
                // Sessizce geç
            }
        }

        if (version_compare($fromVersion, '1.3.1', '<')) {
            try {
                $pdo->exec("INSERT IGNORE INTO `settings` (`key_name`, `value`) VALUES
                    ('cache_enabled', '1'),
                    ('cache_default_ttl', '3600')");
            } catch (\Throwable $e) {
                // Sessizce geç
            }
        }

        if (version_compare($fromVersion, '1.3.2', '<')) {
            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS `password_resets` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `user_id` INT NOT NULL,
                    `token_hash` CHAR(64) NOT NULL,
                    `expires_at` DATETIME NOT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY `uq_password_resets_hash` (`token_hash`),
                    KEY `idx_password_resets_user` (`user_id`),
                    CONSTRAINT `fk_password_resets_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            } catch (\Throwable $e) {
                // Sessizce geç
            }

            self::addColumnIfNotExists($pdo, 'posts', 'reading_minutes', 'SMALLINT UNSIGNED NOT NULL DEFAULT 1');
            try {
                $pdo->exec('UPDATE posts SET reading_minutes = GREATEST(1, CEILING(CHAR_LENGTH(`content`) / 1200)), updated_at = updated_at');
            } catch (\Throwable $e) {
                // Sessizce geç
            }

            try {
                $idx = $pdo->query("SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts' AND INDEX_NAME = 'ft_posts_search' LIMIT 1");
                if ($idx && !$idx->fetch()) {
                    $pdo->exec('ALTER TABLE posts ADD FULLTEXT INDEX ft_posts_search (title, summary, content)');
                }
            } catch (\Throwable $e) {
                // FULLTEXT desteklenmiyorsa arama LIKE yedeğine düşer
            }
        }
    }

    public static function addColumnIfNotExists(\PDO $pdo, string $table, string $column, string $definition): void
    {
        $stmt = $pdo->prepare("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tbl AND COLUMN_NAME = :col LIMIT 1");
        $stmt->execute(['tbl' => $table, 'col' => $column]);
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
        }
    }
}
