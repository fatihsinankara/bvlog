<?php

namespace App\Models;

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
}
