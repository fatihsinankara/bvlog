<?php

namespace App\Models;

use Core\Database;
use Core\Model;

class User extends Model
{
    protected static string $table = 'users';

    public static function findByEmailOrUsername(string $identity): ?array
    {
        return Database::fetchOne(
            'SELECT * FROM users WHERE email = :e OR username = :u LIMIT 1',
            ['e' => $identity, 'u' => $identity]
        );
    }

    public static function firstAuthor(): ?array
    {
        return Database::fetchOne('SELECT * FROM users ORDER BY id ASC LIMIT 1');
    }
}
