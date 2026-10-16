<?php

namespace App\Controllers;

use App\Models\CvEducation;
use App\Models\CvExperience;
use App\Models\CvSkill;
use App\Models\User;
use Core\Controller;
use Core\Seo;

class CvController extends Controller
{
    public function index(): void
    {
        $author = User::firstAuthor() ?? [];
        $experiences = CvExperience::ordered();
        $educations = CvEducation::ordered();
        $skillsRaw = CvSkill::ordered();
        $skillsGrouped = CvSkill::groupedByCategory();

        $schema = Seo::buildPersonSchema($author, $skillsRaw);

        $this->view('cv/index', [
            'author'        => $author,
            'experiences'   => $experiences,
            'educations'    => $educations,
            'skillsGrouped' => $skillsGrouped,
            'meta'          => [
                'title'       => 'Özgeçmiş (CV)',
                'description' => ($author['name'] ?? setting('author_name')) . ' — İş deneyimleri, eğitim geçmişi ve teknik yetenekler.',
                'schema'      => $schema,
            ],
        ]);
    }
}
