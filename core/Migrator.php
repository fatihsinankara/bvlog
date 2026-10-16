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
    public static function checkAndUpgrade(string $targetVersion): void
    {
        $installedVersion = setting('db_version', '0.0.0');
        if (version_compare($installedVersion, $targetVersion, '>=')) {
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
