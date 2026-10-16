<?php

namespace App\Models;

use Core\Model;

class CvSkill extends Model
{
    protected static string $table = 'cv_skills';

    public static function ordered(): array
    {
        return self::all('category ASC, display_order ASC, id ASC');
    }

    public static function groupedByCategory(): array
    {
        $skills = self::ordered();
        $grouped = [];
        foreach ($skills as $s) {
            $grouped[$s['category']][] = $s;
        }
        return $grouped;
    }
}
