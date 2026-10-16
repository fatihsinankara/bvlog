<?php

namespace App\Models;

use Core\Database;
use Core\Model;

class CvSkill extends Model
{
    protected static string $table = 'cv_skills';

    public static function ordered(): array
    {
        return Database::fetchAll(
            'SELECT s.*, 
                    COALESCE(c.name, s.category, "Genel") AS category_name,
                    COALESCE(c.display_order, 999) AS cat_order
             FROM cv_skills s
             LEFT JOIN cv_skill_categories c ON c.id = s.category_id
             ORDER BY cat_order ASC, c.id ASC, s.display_order ASC, s.id ASC'
        );
    }

    public static function groupedByCategory(): array
    {
        $skills = self::ordered();
        $grouped = [];
        foreach ($skills as $s) {
            $catName = $s['category_name'];
            $grouped[$catName][] = $s;
        }
        return $grouped;
    }

    public static function byCategoryId(int $categoryId): array
    {
        return Database::fetchAll(
            'SELECT * FROM cv_skills WHERE category_id = :cat_id ORDER BY display_order ASC, id ASC',
            ['cat_id' => $categoryId]
        );
    }
}
