<?php

namespace App\Models;

use Core\Model;

class CvExperience extends Model
{
    protected static string $table = 'cv_experiences';

    public static function ordered(): array
    {
        return self::all('display_order ASC, id DESC');
    }
}
