<?php

namespace App\Models;

use Core\Cache;
use Core\Database;
use Core\Model;

class Comment extends Model
{
    protected static string $table = 'comments';

    public static function approvedForPost(int $postId): array
    {
        return Database::fetchAll(
            'SELECT * FROM comments WHERE post_id = :pid AND status = "approved" ORDER BY id ASC',
            ['pid' => $postId]
        );
    }

    public static function allWithPost(): array
    {
        return Database::fetchAll(
            'SELECT c.*, p.title AS post_title, p.slug AS post_slug, cat.slug AS category_slug
             FROM comments c
             LEFT JOIN posts p ON p.id = c.post_id
             LEFT JOIN categories cat ON cat.id = p.category_id
             ORDER BY c.id DESC'
        );
    }

    public static function latestWithPost(int $limit = 5): array
    {
        return Database::fetchAll(
            "SELECT c.*, p.title AS post_title, p.slug AS post_slug, cat.slug AS category_slug
             FROM comments c
             LEFT JOIN posts p ON p.id = c.post_id
             LEFT JOIN categories cat ON cat.id = p.category_id
             ORDER BY c.id DESC
             LIMIT {$limit}"
        );
    }

    public static function create(array $data): int
    {
        $id = parent::create($data);
        // Onay bekleyen yorum sayfada gorunmez; onbellege dokunulmaz.
        if (($data['status'] ?? '') === 'approved') {
            self::forgetPostCache((int) ($data['post_id'] ?? 0));
        }
        return $id;
    }

    public static function updateById(int $id, array $data): int
    {
        $postId = self::postIdOf($id);
        $affected = parent::updateById($id, $data);
        self::forgetPostCache($postId);
        return $affected;
    }

    public static function deleteById(int $id): int
    {
        $postId = self::postIdOf($id);
        $affected = parent::deleteById($id);
        self::forgetPostCache($postId);
        return $affected;
    }

    /**
     * Yalnizca yorumun ait oldugu yazinin sayfa onbellegini siler. Onceden her
     * yorum tum blog grubunu temizliyordu; yorum spam'i onbellegi bosaltiyordu.
     */
    public static function forgetPostCache(int $postId): void
    {
        if ($postId <= 0 || !Cache::isAutoFlushEnabled()) {
            return;
        }
        $slug = Database::fetchColumn('SELECT slug FROM posts WHERE id = :id', ['id' => $postId]);
        if (is_string($slug) && $slug !== '') {
            Cache::forget("blog:post:{$slug}", 'blog');
        }
    }

    private static function postIdOf(int $commentId): int
    {
        return (int) Database::fetchColumn('SELECT post_id FROM comments WHERE id = :id', ['id' => $commentId]);
    }
}
