<?php

namespace App\Controllers;

use App\Models\CvEducation;
use App\Models\CvExperience;
use App\Models\CvSkill;
use App\Models\User;
use Core\Cache;
use Core\Controller;
use Core\Seo;

class CvController extends Controller
{
    public function index(): void
    {
        $cvData = Cache::remember('cv:page_data', function (): array {
            $author = User::firstAuthor() ?? [];
            $experiences = CvExperience::ordered();
            $educations = CvEducation::ordered();
            $skillsRaw = CvSkill::ordered();
            $skillsGrouped = CvSkill::groupedByCategory();
            $schema = Seo::buildPersonSchema($author, $skillsRaw);

            return [
                'author'        => $author,
                'experiences'   => $experiences,
                'educations'    => $educations,
                'skillsGrouped' => $skillsGrouped,
                'schema'        => $schema,
            ];
        }, null, 'cv', 'CV & Özgeçmiş Sayfa Verileri');

        $author = $cvData['author'] ?? [];

        $this->view('cv/index', [
            'author'        => $author,
            'experiences'   => $cvData['experiences'] ?? [],
            'educations'    => $cvData['educations'] ?? [],
            'skillsGrouped' => $cvData['skillsGrouped'] ?? [],
            'meta'          => [
                'title'       => 'Özgeçmiş (CV)',
                'description' => ($author['name'] ?? setting('author_name')) . ' — İş deneyimleri, eğitim geçmişi ve teknik yetenekler.',
                'schema'      => $cvData['schema'] ?? [],
            ],
        ]);
    }
}
