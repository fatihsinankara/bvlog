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
            'SELECT c.*, p.title AS post_title, p.slug AS post_slug
             FROM comments c
             LEFT JOIN posts p ON p.id = c.post_id
             ORDER BY c.id DESC'
        );
    }
}
