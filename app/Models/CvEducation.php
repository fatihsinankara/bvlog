<?php

namespace App\Models;

use Core\Model;

class CvEducation extends Model
{
    protected static string $table = 'cv_educations';

    public static function ordered(): array
    {
        return self::all('display_order ASC, id DESC');
    }
}
