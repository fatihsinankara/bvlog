<?php

namespace Plugins\Collections\Models;

use Core\Database;

class Collection
{
    protected static string $table = 'collections';

    public static function all(string $orderBy = 'id DESC'): array
    {
        return Database::fetchAll("SELECT * FROM `" . static::$table . "` ORDER BY {$orderBy}");
    }

    public static function allWithCount(): array
    {
        $sql = "SELECT c.*, 
                       COUNT(cp.id) AS total_posts,
                       COUNT(CASE WHEN p.status = 'published' THEN 1 END) AS published_posts
                FROM collections c
                LEFT JOIN collection_posts cp ON cp.collection_id = c.id
                LEFT JOIN posts p ON p.id = cp.post_id
                GROUP BY c.id
                ORDER BY c.id DESC";

        return Database::fetchAll($sql);
    }

    /**
     * Site haritasi icin yayindaki yazisi olan koleksiyonlar. `last_modified`,
     * koleksiyonun ve icindeki yazilarin en son degisiklik zamanidir.
     */
    public static function sitemapEntries(): array
    {
        return Database::fetchAll(
            "SELECT c.slug, GREATEST(c.updated_at, MAX(p.updated_at)) AS last_modified
             FROM collections c
             INNER JOIN collection_posts cp ON cp.collection_id = c.id
             INNER JOIN posts p ON p.id = cp.post_id AND p.status = 'published'
             WHERE c.status = 'published'
             GROUP BY c.id, c.slug, c.updated_at
             ORDER BY c.id DESC"
        );
    }

    public static function allPublishedWithCount(): array
    {
        $sql = "SELECT c.*, 
                       COUNT(cp.id) AS total_posts,
                       COUNT(CASE WHEN p.status = 'published' THEN 1 END) AS published_posts
                FROM collections c
                LEFT JOIN collection_posts cp ON cp.collection_id = c.id
                LEFT JOIN posts p ON p.id = cp.post_id
                WHERE c.status = 'published'
                GROUP BY c.id
                HAVING published_posts > 0
                ORDER BY c.id DESC";

        return Database::fetchAll($sql);
    }

    public static function find(int $id): ?array
    {
        return Database::fetchOne("SELECT * FROM `" . static::$table . "` WHERE id = :id LIMIT 1", ['id' => $id]);
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::fetchOne("SELECT * FROM `" . static::$table . "` WHERE slug = :slug LIMIT 1", ['slug' => $slug]);
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
        // Önce pivot ilişkilerini temizle
        Database::query("DELETE FROM collection_posts WHERE collection_id = :cid", ['cid' => $id]);
        return Database::delete(static::$table, 'id = :id', ['id' => $id]);
    }

    public static function ensureUniqueSlug(string $slug, int $ignoreId = 0): string
    {
        $base = $slug !== '' ? $slug : 'koleksiyon';
        $unique = $base;
        $counter = 1;

        while (true) {
            $existing = Database::fetchOne(
                "SELECT id FROM `" . static::$table . "` WHERE slug = :slug AND id != :ignore_id LIMIT 1",
                ['slug' => $unique, 'ignore_id' => $ignoreId]
            );

            if (!$existing) {
                return $unique;
            }

            $counter++;
            $unique = "{$base}-{$counter}";
        }
    }

    /**
     * Koleksiyona ait yazıları bölüm sırasına göre getirir.
     */
    public static function getPosts(int $collectionId, bool $onlyPublished = true): array
    {
        $where = "cp.collection_id = :cid";
        if ($onlyPublished) {
            $where .= " AND p.status = 'published'";
        }

        $sql = "SELECT p.id, p.title, p.slug, p.summary, p.status, p.published_at, p.created_at, p.reading_minutes, p.category_id,
                       cp.sort_order AS part_order, cat.name AS category_name, cat.slug AS category_slug
                FROM collection_posts cp
                INNER JOIN posts p ON p.id = cp.post_id
                LEFT JOIN categories cat ON cat.id = p.category_id
                WHERE {$where}
                ORDER BY cp.sort_order ASC, p.id ASC";

        return Database::fetchAll($sql, ['cid' => $collectionId]);
    }

    /**
     * Koleksiyondaki tüm yazıların toplam tahmini okuma süresini dakika cinsinden hesaplar.
     */
    public static function getTotalReadingTime(int $collectionId): int
    {
        return (int) Database::fetchColumn(
            "SELECT COALESCE(SUM(p.reading_minutes), 0)
             FROM collection_posts cp
             INNER JOIN posts p ON p.id = cp.post_id
             WHERE cp.collection_id = :cid AND p.status = 'published'",
            ['cid' => $collectionId]
        );
    }

    /**
     * Verilen bir yazının ait olduğu koleksiyonu ve o koleksiyondaki tüm bölümleri getirir.
     */
    public static function getCollectionForPost(int $postId): ?array
    {
        if (class_exists('\\Core\\Cache') && \Core\Cache::isEnabled()) {
            $cacheKey = "blog:post_series:{$postId}";
            $cached = \Core\Cache::get($cacheKey, null, 'blog');
            if (is_array($cached) && array_key_exists('has_series', $cached)) {
                return $cached['has_series'] ? $cached['data'] : null;
            }
        }

        $pivot = Database::fetchOne(
            "SELECT collection_id, sort_order FROM collection_posts WHERE post_id = :pid LIMIT 1",
            ['pid' => $postId]
        );

        if (!$pivot) {
            if (class_exists('\\Core\\Cache') && \Core\Cache::isEnabled()) {
                \Core\Cache::put("blog:post_series:{$postId}", ['has_series' => false, 'data' => null], null, 'blog', "Yazı Koleksiyon Bağı (#{$postId})");
            }
            return null;
        }

        $collection = self::find((int) $pivot['collection_id']);
        if (!$collection || $collection['status'] !== 'published') {
            if (class_exists('\\Core\\Cache') && \Core\Cache::isEnabled()) {
                \Core\Cache::put("blog:post_series:{$postId}", ['has_series' => false, 'data' => null], null, 'blog', "Yazı Koleksiyon Bağı (#{$postId})");
            }
            return null;
        }

        $allPosts = self::getPosts((int) $collection['id'], true);
        if (empty($allPosts)) {
            if (class_exists('\\Core\\Cache') && \Core\Cache::isEnabled()) {
                \Core\Cache::put("blog:post_series:{$postId}", ['has_series' => false, 'data' => null], null, 'blog', "Yazı Koleksiyon Bağı (#{$postId})");
            }
            return null;
        }

        $currentIndex = 0;
        $prevPost = null;
        $nextPost = null;

        foreach ($allPosts as $idx => $p) {
            if ((int) $p['id'] === $postId) {
                $currentIndex = $idx + 1; // 1-based index (Bölüm 1, 2...)
                $prevPost = $allPosts[$idx - 1] ?? null;
                $nextPost = $allPosts[$idx + 1] ?? null;
                break;
            }
        }

        $result = [
            'collection'    => $collection,
            'posts'         => $allPosts,
            'total_parts'   => count($allPosts),
            'current_part'  => $currentIndex ?: (int) $pivot['sort_order'],
            'prev_post'     => $prevPost,
            'next_post'     => $nextPost,
        ];

        if (class_exists('\\Core\\Cache') && \Core\Cache::isEnabled()) {
            \Core\Cache::put("blog:post_series:{$postId}", ['has_series' => true, 'data' => $result], null, 'blog', "Yazı Koleksiyon Serisi (#{$postId})");
        }

        return $result;
    }
}
