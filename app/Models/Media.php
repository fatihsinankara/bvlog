<?php

namespace App\Models;

use Core\Database;
use Core\Model;

class Media extends Model
{
    protected static string $table = 'media';

    public static function search(?string $query = null, int $limit = 60, int $offset = 0): array
    {
        if (!empty($query)) {
            $q = '%' . $query . '%';
            return Database::fetchAll(
                "SELECT * FROM media WHERE title LIKE :q1 OR alt_text LIKE :q2 OR filename LIKE :q3 ORDER BY id DESC LIMIT {$limit} OFFSET {$offset}",
                ['q1' => $q, 'q2' => $q, 'q3' => $q]
            );
        }

        return Database::fetchAll("SELECT * FROM media ORDER BY id DESC LIMIT {$limit} OFFSET {$offset}");
    }
}
