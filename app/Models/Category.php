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

    /**
     * Site haritasi icin kategoriler ve her birindeki en son yazi degisikligi.
     * Yazisi olmayan kategoride `last_post_at` NULL doner.
     */
    public static function sitemapEntries(): array
    {
        try {
            return Database::fetchAll(
                "SELECT c.slug, c.created_at, MAX(p.updated_at) AS last_post_at
                 FROM categories c
                 LEFT JOIN posts p ON p.status = 'published'
                     AND (p.category_id = c.id OR EXISTS (
                         SELECT 1 FROM post_categories pc WHERE pc.post_id = p.id AND pc.category_id = c.id
                     ))
                 GROUP BY c.id, c.slug, c.created_at
                 ORDER BY c.id ASC"
            );
        } catch (\Throwable $e) {
            return Database::fetchAll(
                "SELECT c.slug, c.created_at, MAX(p.updated_at) AS last_post_at
                 FROM categories c
                 LEFT JOIN posts p ON p.status = 'published' AND p.category_id = c.id
                 GROUP BY c.id, c.slug, c.created_at
                 ORDER BY c.id ASC"
            );
        }
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
