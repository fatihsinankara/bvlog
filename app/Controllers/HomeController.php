<?php

namespace App\Controllers;

use App\Models\Category;
use App\Models\CvExperience;
use App\Models\CvSkill;
use App\Models\Post;
use App\Models\Project;
use App\Models\Setting;
use App\Models\User;
use Core\Controller;

class HomeController extends Controller
{
    public function index(): void
    {
        $author = User::firstAuthor() ?? [];
        $heroStyle = Setting::get('homepage_hero_style', 'minimal_bio');
        $postStyle = Setting::get('homepage_post_style', 'list');
        $sections = Setting::getJson('homepage_sections', [
            ['id' => 'hero', 'title' => 'Yazar Tanıtım Kartı (Hero)', 'enabled' => true],
            ['id' => 'featured_posts', 'title' => 'Öne Çıkan Yazılar', 'enabled' => true],
            ['id' => 'latest_posts', 'title' => 'Son Blog Yazıları', 'enabled' => true],
            ['id' => 'featured_projects', 'title' => 'Seçkin Portfolyo Projeleri', 'enabled' => true],
            ['id' => 'cv_skills_summary', 'title' => 'Yetenek & Deneyim Özeti', 'enabled' => true],
            ['id' => 'contact_cta', 'title' => 'İletişim Çağrı Kutusu', 'enabled' => true],
        ]);

        $limit = max(1, (int) Setting::get('posts_per_page', 10));
        $latestPosts = Post::publishedList($limit, 0);
        $featuredPosts = Post::featuredPosts(3);
        $categories = Category::allWithCounts();

        $featuredProjects = is_module_active('portfolio') ? Project::featuredList(4) : [];
        $skillsGrouped = is_module_active('cv') ? CvSkill::groupedByCategory() : [];
        $experiences = is_module_active('cv') ? array_slice(CvExperience::ordered(), 0, 3) : [];

        $this->view('home/index', [
            'author'           => $author,
            'heroStyle'        => $heroStyle,
            'postStyle'        => $postStyle,
            'sections'         => $sections,
            'latestPosts'      => $latestPosts,
            'featuredPosts'    => $featuredPosts,
            'categories'       => $categories,
            'featuredProjects' => $featuredProjects,
            'skillsGrouped'    => $skillsGrouped,
            'experiences'      => $experiences,
            'meta'             => [
                'title'       => '',
                'description' => Setting::get('site_description', ''),
            ],
        ]);
    }
}
