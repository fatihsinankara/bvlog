<?php

namespace App\Models;

use Core\Cache;
use Core\Database;
use Core\Model;

class User extends Model
{
    protected static string $table = 'users';
    private static ?array $authorCache = null;

    public static function findByEmailOrUsername(string $identity): ?array
    {
        return Database::fetchOne(
            'SELECT * FROM users WHERE email = :e OR username = :u LIMIT 1',
            ['e' => $identity, 'u' => $identity]
        );
    }

    public static function firstAuthor(): ?array
    {
        if (self::$authorCache !== null) {
            return self::$authorCache;
        }

        $author = Cache::remember('settings:first_author', function () {
            return Database::fetchOne('SELECT * FROM users ORDER BY id ASC LIMIT 1');
        }, null, 'settings', 'Yazar Profil Bilgileri');

        if (is_array($author)) {
            self::$authorCache = $author;
        }

        return is_array($author) ? $author : null;
    }

    public static function clearAuthorCache(): void
    {
        self::$authorCache = null;
    }
}
