<?php

namespace Core;

use PDO;
use PDOException;
use PDOStatement;

class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(?array $customConfig = null): PDO
    {
        if ($customConfig !== null) {
            return self::createConnection($customConfig);
        }

        if (self::$instance === null) {
            $configFile = BVLOG_ROOT . '/config/database.php';
            if (!file_exists($configFile)) {
                throw new PDOException('Veritabanı yapılandırma dosyası bulunamadı.');
            }
            $config = require $configFile;
            self::$instance = self::createConnection($config);
        }

        return self::$instance;
    }

    public static function createConnection(array $config): PDO
    {
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? '3306';
        $db   = $config['database'] ?? '';
        $user = $config['username'] ?? 'root';
        $pass = $config['password'] ?? '';
        $charset = $config['charset'] ?? 'utf8mb4';

        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        return new PDO($dsn, $user, $pass, $options);
    }

    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $result = self::query($sql, $params)->fetch();
        return $result ?: null;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function fetchColumn(string $sql, array $params = [], int $column = 0): mixed
    {
        return self::query($sql, $params)->fetchColumn($column);
    }

    public static function insert(string $table, array $data): int
    {
        self::assertIdentifier($table);
        $columns = array_keys($data);
        foreach ($columns as $column) {
            self::assertIdentifier((string) $column);
        }
        $placeholders = array_map(fn($col) => ":{$col}", $columns);

        $sql = sprintf(
            'INSERT INTO `%s` (`%s`) VALUES (%s)',
            $table,
            implode('`, `', $columns),
            implode(', ', $placeholders)
        );

        self::query($sql, $data);
        $lastId = (int) self::getInstance()->lastInsertId();
        Cache::invalidateByTable($table);
        return $lastId;
    }

    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        self::assertIdentifier($table);
        $sets = [];
        $params = [];
        foreach ($data as $col => $val) {
            self::assertIdentifier((string) $col);
            $sets[] = "`{$col}` = :set_{$col}";
            $params["set_{$col}"] = $val;
        }

        $sql = sprintf(
            'UPDATE `%s` SET %s WHERE %s',
            $table,
            implode(', ', $sets),
            $where
        );

        $stmt = self::query($sql, array_merge($params, $whereParams));
        $affected = $stmt->rowCount();
        Cache::invalidateByTable($table);
        return $affected;
    }

    public static function delete(string $table, string $where, array $params = []): int
    {
        self::assertIdentifier($table);
        $sql = sprintf('DELETE FROM `%s` WHERE %s', $table, $where);
        $affected = self::query($sql, $params)->rowCount();
        Cache::invalidateByTable($table);
        return $affected;
    }

    private static function assertIdentifier(string $name): void
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
            throw new \InvalidArgumentException('Geçersiz SQL tanımlayıcısı.');
        }
    }
}
