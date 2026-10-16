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
        // Örnek: İleride 1.1.0 sürümünde mevcut bir tabloya yeni sütun ekleneceği zaman:
        // if (version_compare($fromVersion, '1.1.0', '<')) {
        //     self::addColumnIfNotExists($pdo, 'posts', 'reading_minutes', 'INT UNSIGNED DEFAULT NULL');
        // }
    }

    public static function addColumnIfNotExists(\PDO $pdo, string $table, string $column, string $definition): void
    {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE :col");
        $stmt->execute(['col' => $column]);
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
        }
    }
}
