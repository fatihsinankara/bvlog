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
            $where[] = 'p.category_id = :cat_id';
            $params['cat_id'] = $categoryId;
        }

        if (!empty($search)) {
            $where[] = '(p.title LIKE :q1 OR p.summary LIKE :q2 OR p.content LIKE :q3)';
            $params['q1'] = "%{$search}%";
            $params['q2'] = "%{$search}%";
            $params['q3'] = "%{$search}%";
        }

        $whereSql = implode(' AND ', $where);
        return Database::fetchAll(
            "SELECT p.*, c.name AS category_name, c.slug AS category_slug, c.icon AS category_icon, c.color AS category_color, u.name AS author_name
             FROM posts p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN users u ON u.id = p.user_id
             WHERE {$whereSql}
             ORDER BY COALESCE(p.published_at, p.created_at) DESC, p.id DESC
             LIMIT {$limit} OFFSET {$offset}",
            $params
        );
    }

    public static function countPublished(?int $categoryId = null, ?string $search = null): int
    {
        $where = ['status = "published"'];
        $params = [];

        if ($categoryId !== null) {
            $where[] = 'category_id = :cat_id';
            $params['cat_id'] = $categoryId;
        }

        if (!empty($search)) {
            $where[] = '(title LIKE :q1 OR summary LIKE :q2 OR content LIKE :q3)';
            $params['q1'] = "%{$search}%";
            $params['q2'] = "%{$search}%";
            $params['q3'] = "%{$search}%";
        }

        return (int) Database::fetchColumn(
            'SELECT COUNT(*) FROM posts WHERE ' . implode(' AND ', $where),
            $params
        );
    }

    public static function featuredPosts(int $limit = 3): array
    {
        return Database::fetchAll(
            "SELECT p.*, c.name AS category_name, c.slug AS category_slug, c.icon AS category_icon, c.color AS category_color, u.name AS author_name
             FROM posts p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN users u ON u.id = p.user_id
             WHERE p.status = 'published' AND p.is_featured = 1
             ORDER BY COALESCE(p.published_at, p.created_at) DESC
             LIMIT {$limit}"
        );
    }

    public static function findDetailedBySlug(string $slug): ?array
    {
        return Database::fetchOne(
            'SELECT p.*, c.name AS category_name, c.slug AS category_slug, c.icon AS category_icon, c.color AS category_color, c.image AS category_image,
                    u.name AS author_name, u.role_title AS author_title, u.bio AS author_bio, u.avatar AS author_avatar
             FROM posts p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN users u ON u.id = p.user_id
             WHERE p.slug = :slug LIMIT 1',
            ['slug' => $slug]
        );
    }

    public static function allForAdmin(): array
    {
        return Database::fetchAll(
            'SELECT p.*, c.name AS category_name, c.color AS category_color, c.icon AS category_icon
             FROM posts p
             LEFT JOIN categories c ON c.id = p.category_id
             ORDER BY p.id DESC'
        );
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
}
