<?php

namespace App\Models;

use Core\Cache;
use Core\Database;
use Core\Model;

class Category extends Model
{
    protected static string $table = 'categories';

    public static function allWithCounts(): array
    {
        return Cache::remember('blog:categories_with_counts', function (): array {
            try {
                return Database::fetchAll(
                    'SELECT c.*, COUNT(DISTINCT p.id) AS post_count
                     FROM categories c
                     LEFT JOIN post_categories pc ON pc.category_id = c.id
                     LEFT JOIN posts p ON (p.id = pc.post_id OR p.category_id = c.id) AND p.status = "published"
                     GROUP BY c.id
                     ORDER BY c.name ASC'
                );
            } catch (\Throwable $e) {
                return Database::fetchAll(
                    'SELECT c.*, COUNT(p.id) AS post_count
                     FROM categories c
                     LEFT JOIN posts p ON p.category_id = c.id AND p.status = "published"
                     GROUP BY c.id
                     ORDER BY c.name ASC'
                );
            }
        }, null, 'blog', 'Kategori Listesi ve Yazı Sayaçları');
    }

    public static function ensureUniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $base = $slug;
        $counter = 1;
        while (true) {
            $sql = 'SELECT id FROM categories WHERE slug = :slug';
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
