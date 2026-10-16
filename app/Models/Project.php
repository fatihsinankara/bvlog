<?php

namespace App\Models;

use Core\Database;
use Core\Model;

class Project extends Model
{
    protected static string $table = 'projects';

    public static function publishedList(?int $limit = null): array
    {
        $sql = 'SELECT * FROM projects WHERE status = "published" ORDER BY display_order ASC, id DESC';
        if ($limit !== null) {
            $sql .= " LIMIT {$limit}";
        }
        return Database::fetchAll($sql);
    }

    public static function featuredList(int $limit = 4): array
    {
        return Database::fetchAll(
            "SELECT * FROM projects WHERE status = 'published' ORDER BY is_featured DESC, display_order ASC, id DESC LIMIT {$limit}"
        );
    }

    public static function ensureUniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $base = $slug;
        $counter = 1;
        while (true) {
            $sql = 'SELECT id FROM projects WHERE slug = :slug';
            $params = ['slug' => $slug];
            if ($ignoreId !== null) {
                $sql .= ' AND id != :id';
                $params['id'] = $ignoreId;
            }
            $exists = Database::fetchOne($sql, $params);
            if (!$exists) {
                return $slug;
            }
            $counter++;
            $slug = "{$base}-{$counter}";
        }
    }
}
