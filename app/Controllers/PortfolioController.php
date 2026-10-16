<?php

namespace App\Controllers;

use App\Models\Project;
use Core\Controller;
use Core\Router;

class PortfolioController extends Controller
{
    public function index(): void
    {
        $projects = Project::publishedList();
        $this->view('portfolio/index', [
            'projects' => $projects,
            'meta'     => [
                'title'       => 'Portfolyo & Projeler',
                'description' => 'Geliştirdiğim yazılım projeleri, açık kaynak çalışmalar ve ürünler.',
            ],
        ]);
    }

    public function show(string $slug): void
    {
        $project = Project::findBySlug($slug);
        if (!$project || $project['status'] !== 'published') {
            Router::abort(404, 'Proje bulunamadı.');
        }

        $this->view('portfolio/show', [
            'project' => $project,
            'meta'    => [
                'title'       => $project['title'] . ' — Portfolyo',
                'description' => $project['summary'] ?: mb_substr(strip_tags($project['description']), 0, 160),
                'image'       => $project['thumbnail'] ?? '',
            ],
        ]);
    }
}
