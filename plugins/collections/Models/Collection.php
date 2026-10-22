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

        $sql = "SELECT p.*, cp.sort_order AS part_order, cat.name AS category_name, cat.slug AS category_slug
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
        $posts = self::getPosts($collectionId, true);
        $totalMinutes = 0;
        foreach ($posts as $post) {
            $totalMinutes += reading_time($post['content'] ?? '');
        }
        return $totalMinutes;
    }

    /**
     * Verilen bir yazının ait olduğu koleksiyonu ve o koleksiyondaki tüm bölümleri getirir.
     */
    public static function getCollectionForPost(int $postId): ?array
    {
        $pivot = Database::fetchOne(
            "SELECT collection_id, sort_order FROM collection_posts WHERE post_id = :pid LIMIT 1",
            ['pid' => $postId]
        );

        if (!$pivot) {
            return null;
        }

        $collection = self::find((int) $pivot['collection_id']);
        if (!$collection || $collection['status'] !== 'published') {
            return null;
        }

        $allPosts = self::getPosts((int) $collection['id'], true);
        if (empty($allPosts)) {
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

        return [
            'collection'    => $collection,
            'posts'         => $allPosts,
            'total_parts'   => count($allPosts),
            'current_part'  => $currentIndex ?: (int) $pivot['sort_order'],
            'prev_post'     => $prevPost,
            'next_post'     => $nextPost,
        ];
    }
}
