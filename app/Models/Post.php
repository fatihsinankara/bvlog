<?php

namespace App\Models;

use Core\Database;
use Core\Model;

class Post extends Model
{
    protected static string $table = 'posts';

    public static function publishedList(int $limit = 10, int $offset = 0, ?int $categoryId = null, ?string $search = null): array
    {
        $where = ['p.status = "published"'];
        $params = [];

        if ($categoryId !== null) {
            $where[] = '(p.category_id = :cat_id OR EXISTS (SELECT 1 FROM post_categories pc WHERE pc.post_id = p.id AND pc.category_id = :cat_id2))';
            $params['cat_id'] = $categoryId;
            $params['cat_id2'] = $categoryId;
        }

        if (!empty($search)) {
            $term = trim(preg_replace('/[+\-<>()~*"@]+/u', ' ', $search) ?? '');
            if (mb_strlen($term) >= 3) {
                $where[] = 'MATCH(p.title, p.summary, p.content) AGAINST (:q IN NATURAL LANGUAGE MODE)';
                $params['q'] = $term;
            } else {
                $where[] = '(p.title LIKE :q1 OR p.summary LIKE :q2)';
                $params['q1'] = '%' . $search . '%';
                $params['q2'] = '%' . $search . '%';
            }
        }

        $whereSql = implode(' AND ', $where);
        try {
            $posts = Database::fetchAll(
                'SELECT ' . self::listColumns() . "
                 WHERE {$whereSql}
                 ORDER BY COALESCE(p.published_at, p.created_at) DESC, p.id DESC
                 LIMIT {$limit} OFFSET {$offset}",
                $params
            );
        } catch (\Throwable $e) {
            if (empty($search)) {
                throw $e;
            }
            $where = array_values(array_filter($where, static fn ($part) => !str_contains($part, 'MATCH(')));
            $params = array_diff_key($params, ['q' => true]);
            $like = '%' . $search . '%';
            $where[] = '(p.title LIKE :q1 OR p.summary LIKE :q2)';
            $params['q1'] = $like;
            $params['q2'] = $like;
            $posts = Database::fetchAll(
                'SELECT ' . self::listColumns() . '
                 WHERE ' . implode(' AND ', $where) . "
                 ORDER BY COALESCE(p.published_at, p.created_at) DESC, p.id DESC
                 LIMIT {$limit} OFFSET {$offset}",
                $params
            );
        }

        return self::attachCategories($posts);
    }

    public static function countPublished(?int $categoryId = null, ?string $search = null): int
    {
        $where = ['p.status = "published"'];
        $params = [];

        if ($categoryId !== null) {
            $where[] = '(p.category_id = :cat_id OR EXISTS (SELECT 1 FROM post_categories pc WHERE pc.post_id = p.id AND pc.category_id = :cat_id2))';
            $params['cat_id'] = $categoryId;
            $params['cat_id2'] = $categoryId;
        }

        if (!empty($search)) {
            $term = trim(preg_replace('/[+\-<>()~*"@]+/u', ' ', $search) ?? '');
            if (mb_strlen($term) >= 3) {
                $where[] = 'MATCH(p.title, p.summary, p.content) AGAINST (:q IN NATURAL LANGUAGE MODE)';
                $params['q'] = $term;
            } else {
                $where[] = '(p.title LIKE :q1 OR p.summary LIKE :q2)';
                $params['q1'] = '%' . $search . '%';
                $params['q2'] = '%' . $search . '%';
            }
        }

        try {
            return (int) Database::fetchColumn(
                'SELECT COUNT(*) FROM posts p WHERE ' . implode(' AND ', $where),
                $params
            );
        } catch (\Throwable $e) {
            if (empty($search)) {
                throw $e;
            }
            $where = array_values(array_filter($where, static fn ($part) => !str_contains($part, 'MATCH(')));
            unset($params['q']);
            $where[] = '(p.title LIKE :q1 OR p.summary LIKE :q2)';
            $params['q1'] = '%' . $search . '%';
            $params['q2'] = '%' . $search . '%';
            return (int) Database::fetchColumn(
                'SELECT COUNT(*) FROM posts p WHERE ' . implode(' AND ', $where),
                $params
            );
        }
    }

    public static function featuredPosts(int $limit = 3): array
    {
        $posts = Database::fetchAll(
            'SELECT ' . self::listColumns() . "
             WHERE p.status = 'published' AND p.is_featured = 1
             ORDER BY COALESCE(p.published_at, p.created_at) DESC
             LIMIT {$limit}"
        );

        return self::attachCategories($posts);
    }

    public static function findDetailedBySlug(string $slug): ?array
    {
        $post = Database::fetchOne(
            'SELECT p.*, c.name AS category_name, c.slug AS category_slug, c.icon AS category_icon, c.color AS category_color, c.image AS category_image,
                    u.name AS author_name, u.role_title AS author_title, u.bio AS author_bio, u.avatar AS author_avatar
             FROM posts p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN users u ON u.id = p.user_id
             WHERE p.slug = :slug LIMIT 1',
            ['slug' => $slug]
        );

        if (!$post) {
            return null;
        }

        $categories = self::getCategories((int) $post['id']);
        $post['categories'] = $categories;

        if (empty($post['category_name']) && !empty($categories[0])) {
            $post['category_id']    = (int) $categories[0]['id'];
            $post['category_name']  = $categories[0]['name'];
            $post['category_slug']  = $categories[0]['slug'];
            $post['category_icon']  = $categories[0]['icon'];
            $post['category_color'] = $categories[0]['color'];
        }

        return $post;
    }

    public static function allForAdmin(): array
    {
        $posts = Database::fetchAll(
            'SELECT p.*, c.name AS category_name, c.slug AS category_slug, c.color AS category_color, c.icon AS category_icon
             FROM posts p
             LEFT JOIN categories c ON c.id = p.category_id
             ORDER BY p.id DESC'
        );

        return self::attachCategories($posts);
    }

    public static function latestForAdmin(int $limit = 5): array
    {
        $posts = Database::fetchAll(
            "SELECT p.id, p.title, p.slug, p.status, p.category_id, p.created_at, p.published_at, p.views_count,
                    c.name AS category_name, c.slug AS category_slug, c.color AS category_color, c.icon AS category_icon
             FROM posts p
             LEFT JOIN categories c ON c.id = p.category_id
             ORDER BY p.id DESC
             LIMIT {$limit}"
        );

        return self::attachCategories($posts);
    }

    public static function ensureUniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $base = $slug;
        $counter = 1;
        while (true) {
            $sql = 'SELECT id FROM posts WHERE slug = :slug';
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

    /**
     * Yazının çoklu kategorilerini post_categories tablosuyla senkronize eder
     * ve ilk kategoriyi ana kategori (posts.category_id) olarak günceller.
     *
     * @param int   $postId
     * @param int[] $categoryIds
     */
    public static function syncCategories(int $postId, array $categoryIds): void
    {
        $cleanIds = [];
        foreach ($categoryIds as $id) {
            $cid = (int) $id;
            if ($cid > 0 && !in_array($cid, $cleanIds, true)) {
                $cleanIds[] = $cid;
            }
        }

        try {
            Database::delete('post_categories', 'post_id = :pid', ['pid' => $postId]);
            $order = 1;
            foreach ($cleanIds as $cid) {
                Database::query(
                    'INSERT IGNORE INTO post_categories (post_id, category_id, sort_order) VALUES (:p, :c, :o)',
                    ['p' => $postId, 'c' => $cid, 'o' => $order++]
                );
            }
        } catch (\Throwable $e) {
            // Tablo henüz yoksa sessizce geç
        }

        $primaryCatId = $cleanIds[0] ?? null;
        Database::update('posts', ['category_id' => $primaryCatId], 'id = :id', ['id' => $postId]);
    }

    /**
     * Bir yazıya atanmış tüm kategorileri sıralı olarak döndürür.
     */
    public static function getCategories(int $postId): array
    {
        try {
            $rows = Database::fetchAll(
                'SELECT c.*, pc.sort_order
                 FROM categories c
                 INNER JOIN post_categories pc ON pc.category_id = c.id
                 WHERE pc.post_id = :pid
                 ORDER BY pc.sort_order ASC, c.name ASC',
                ['pid' => $postId]
            );
            if (!empty($rows)) {
                return $rows;
            }
        } catch (\Throwable $e) {
            // Geriye dönük uyumluluk
        }

        return Database::fetchAll(
            'SELECT c.*
             FROM categories c
             INNER JOIN posts p ON p.category_id = c.id
             WHERE p.id = :pid',
            ['pid' => $postId]
        );
    }

    /**
     * Yazı dizisine tek sorguyla çoklu kategori listesini ($post['categories']) ekler.
     */
    public static function attachCategories(array $posts): array
    {
        if (empty($posts)) {
            return [];
        }

        $postIds = array_values(array_unique(array_filter(array_map(fn($p) => (int) ($p['id'] ?? 0), $posts))));
        $grouped = [];

        if (!empty($postIds)) {
            $inList = implode(',', $postIds);
            try {
                $rows = Database::fetchAll(
                    "SELECT pc.post_id, c.id, c.name, c.slug, c.icon, c.color, pc.sort_order
                     FROM post_categories pc
                     INNER JOIN categories c ON c.id = pc.category_id
                     WHERE pc.post_id IN ({$inList})
                     ORDER BY pc.sort_order ASC, c.name ASC"
                );
                foreach ($rows as $r) {
                    $pid = (int) $r['post_id'];
                    $grouped[$pid][] = [
                        'id'    => (int) $r['id'],
                        'name'  => $r['name'],
                        'slug'  => $r['slug'],
                        'icon'  => $r['icon'],
                        'color' => $r['color'],
                    ];
                }
            } catch (\Throwable $e) {
                // Tablo henüz yoksa yedek mekanizma çalışır
            }
        }

        foreach ($posts as &$p) {
            $pid = (int) ($p['id'] ?? 0);
            if (!empty($grouped[$pid])) {
                $p['categories'] = $grouped[$pid];
                if (empty($p['category_name'])) {
                    $first = $grouped[$pid][0];
                    $p['category_id']    = $first['id'];
                    $p['category_name']  = $first['name'];
                    $p['category_slug']  = $first['slug'];
                    $p['category_icon']  = $first['icon'];
                    $p['category_color'] = $first['color'];
                }
            } elseif (!empty($p['category_name'])) {
                $p['categories'] = [[
                    'id'    => (int) ($p['category_id'] ?? 0),
                    'name'  => $p['category_name'],
                    'slug'  => $p['category_slug'] ?? '',
                    'icon'  => $p['category_icon'] ?? 'fa-solid fa-folder',
                    'color' => $p['category_color'] ?? '#18181b',
                ]];
            } else {
                $p['categories'] = [];
            }
        }
        unset($p);

        return $posts;
    }

    public static function syncTags(int $postId, string $tagsString): void
    {
        Database::delete('post_tags', 'post_id = :pid', ['pid' => $postId]);
        $tagNames = array_filter(array_map('trim', explode(',', $tagsString)));
        foreach ($tagNames as $name) {
            $slug = slugify($name);
            $tag = Database::fetchOne('SELECT id FROM tags WHERE slug = :s LIMIT 1', ['s' => $slug]);
            if ($tag) {
                $tagId = (int) $tag['id'];
            } else {
                $tagId = Database::insert('tags', ['name' => $name, 'slug' => $slug]);
            }
            Database::query(
                'INSERT IGNORE INTO post_tags (post_id, tag_id) VALUES (:p, :t)',
                ['p' => $postId, 't' => $tagId]
            );
        }
    }

    public static function getTags(int $postId): array
    {
        return Database::fetchAll(
            'SELECT t.* FROM tags t
             INNER JOIN post_tags pt ON pt.tag_id = t.id
             WHERE pt.post_id = :pid
             ORDER BY t.name ASC',
            ['pid' => $postId]
        );
    }

    /**
     * Yayın tarihi ve saati gelmiş (published_at <= NOW()) zamanlanmış yazıları yayına alır.
     *
     * @return array<int, array<string, mixed>> Yayına alınan yazıların listesi
     */
    public static function publishDueScheduledPosts(): array
    {
        $now = date('Y-m-d H:i:s');
        $duePosts = Database::fetchAll(
            "SELECT id, title, slug, published_at
             FROM posts
             WHERE status = 'scheduled'
               AND published_at IS NOT NULL
               AND published_at <= :now
             ORDER BY published_at ASC",
            ['now' => $now]
        );

        if (empty($duePosts)) {
            return [];
        }

        $published = [];
        foreach ($duePosts as $post) {
            $postId = (int) $post['id'];
            Database::update(
                'posts',
                ['status' => 'published'],
                'id = :id AND status = "scheduled"',
                ['id' => $postId]
            );
            \Core\Hook::doAction('post_published_scheduled', $post);
            \Core\Hook::doAction('post_saved', $postId);
            $published[] = $post;
        }

        return $published;
    }

    public static function countScheduled(): int
    {
        return self::count('status = "scheduled"');
    }

    private static function listColumns(): string
    {
        return 'p.id, p.user_id, p.category_id, p.title, p.slug, p.summary, p.cover_image, p.is_featured, p.status, p.views_count, p.published_at, p.created_at, p.updated_at, p.reading_minutes, c.name AS category_name, c.slug AS category_slug, c.icon AS category_icon, c.color AS category_color, u.name AS author_name
             FROM posts p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN users u ON u.id = p.user_id';
    }
}
