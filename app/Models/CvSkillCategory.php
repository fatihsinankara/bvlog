<?php

namespace App\Models;

use Core\Database;
use Core\Model;

class CvSkillCategory extends Model
{
    protected static string $table = 'cv_skill_categories';

    public static function ordered(): array
    {
        return self::all('display_order ASC, id ASC');
    }

    public static function allWithSkillCounts(): array
    {
        return Database::fetchAll(
            'SELECT c.*, COUNT(s.id) AS skill_count
             FROM cv_skill_categories c
             LEFT JOIN cv_skills s ON s.category_id = c.id
             GROUP BY c.id
             ORDER BY c.display_order ASC, c.id ASC'
        );
    }
}
