<?php

namespace Core;

abstract class Model
{
    protected static string $table = '';

    public static function all(string $orderBy = 'id DESC'): array
    {
        $table = static::$table;
        return Database::fetchAll("SELECT * FROM `{$table}` ORDER BY {$orderBy}");
    }

    public static function find(int $id): ?array
    {
        $table = static::$table;
        return Database::fetchOne("SELECT * FROM `{$table}` WHERE id = :id LIMIT 1", ['id' => $id]);
    }

    public static function findBySlug(string $slug): ?array
    {
        $table = static::$table;
        return Database::fetchOne("SELECT * FROM `{$table}` WHERE slug = :slug LIMIT 1", ['slug' => $slug]);
    }

    public static function create(array $data): int
    {
        return Database::insert(static::$table, $data);
    }

    public static function updateById(int $id, array $data): int
    {
        return Database::update(static::$table, $data, 'id = :where_id', ['where_id' => $id]);
    }

    public static function deleteById(int $id): int
    {
        return Database::delete(static::$table, 'id = :id', ['id' => $id]);
    }

    public static function count(string $where = '1=1', array $params = []): int
    {
        $table = static::$table;
        return (int) Database::fetchColumn("SELECT COUNT(*) FROM `{$table}` WHERE {$where}", $params);
    }
}
