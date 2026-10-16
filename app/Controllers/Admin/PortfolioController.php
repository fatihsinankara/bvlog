<?php

namespace App\Controllers\Admin;

use App\Models\Project;
use Core\Controller;
use Core\HtmlSanitizer;
use Core\ImageProcessor;
use Core\Request;
use Core\Session;

class PortfolioController extends Controller
{
    public function index(): void
    {
        $projects = Project::all('display_order ASC, id DESC');
        $this->adminView('admin/portfolio/index', [
            'projects'  => $projects,
            'pageTitle' => 'Portfolyo Projeleri',
        ]);
    }

    public function create(): void
    {
        $this->adminView('admin/portfolio/form', [
            'project'   => null,
            'pageTitle' => 'Yeni Proje Ekle',
        ]);
    }

    public function store(): void
    {
        $title = Request::post('title', '');
        if ($title === '') {
            Session::flash('error', 'Proje başlığı zorunludur.');
            $this->redirect('/admin/portfolio/create');
        }

        $customSlug = Request::post('slug', '');
        $slug = Project::ensureUniqueSlug(slugify($customSlug !== '' ? $customSlug : $title));

        $thumbnail = Request::post('thumbnail', '');
        $file = Request::file('thumbnail_file');
        if ($file && !empty($file['tmp_name'])) {
            try {
                $media = ImageProcessor::processFeatured($file, $title, $title);
                $thumbnail = $media['filepath'];
            } catch (\Throwable $e) {
                Session::flash('error', 'Görsel işlenemedi: ' . $e->getMessage());
                $this->redirect('/admin/portfolio/create');
            }
        }

        Project::create([
            'title'         => mb_substr($title, 0, 200),
            'slug'          => $slug,
            'summary'       => mb_substr(Request::post('summary', ''), 0, 400),
            'description'   => HtmlSanitizer::clean((string) ($_POST['description'] ?? '')),
            'thumbnail'     => $thumbnail ?: null,
            'demo_url'      => Request::post('demo_url', '') ?: null,
            'github_url'    => Request::post('github_url', '') ?: null,
            'tech_stack'    => mb_substr(Request::post('tech_stack', ''), 0, 255),
            'is_featured'   => Request::post('is_featured') ? 1 : 0,
            'display_order' => (int) Request::post('display_order', 0),
            'status'        => Request::post('status', 'published') === 'draft' ? 'draft' : 'published',
        ]);

        Session::flash('success', 'Proje başarıyla eklendi.');
        $this->redirect('/admin/portfolio');
    }

    public function edit(string $id): void
    {
        $project = Project::find((int) $id);
        if (!$project) {
            $this->redirect('/admin/portfolio');
        }

        $this->adminView('admin/portfolio/form', [
            'project'   => $project,
            'pageTitle' => 'Projeyi Düzenle',
        ]);
    }

    public function update(string $id): void
    {
        $project = Project::find((int) $id);
        if (!$project) {
            $this->redirect('/admin/portfolio');
        }

        $title = Request::post('title', '');
        if ($title === '') {
            Session::flash('error', 'Proje başlığı zorunludur.');
            $this->redirect('/admin/portfolio/' . $project['id'] . '/edit');
        }

        $customSlug = Request::post('slug', '');
        $slug = Project::ensureUniqueSlug(slugify($customSlug !== '' ? $customSlug : $title), (int) $project['id']);

        $thumbnail = Request::post('thumbnail', $project['thumbnail'] ?? '');
        $file = Request::file('thumbnail_file');
        if ($file && !empty($file['tmp_name'])) {
            try {
                $media = ImageProcessor::processFeatured($file, $title, $title);
                $thumbnail = $media['filepath'];
            } catch (\Throwable $e) {
                Session::flash('error', 'Görsel işlenemedi: ' . $e->getMessage());
                $this->redirect('/admin/portfolio/' . $project['id'] . '/edit');
            }
        }

        Project::updateById((int) $project['id'], [
            'title'         => mb_substr($title, 0, 200),
            'slug'          => $slug,
            'summary'       => mb_substr(Request::post('summary', ''), 0, 400),
            'description'   => HtmlSanitizer::clean((string) ($_POST['description'] ?? '')),
            'thumbnail'     => $thumbnail ?: null,
            'demo_url'      => Request::post('demo_url', '') ?: null,
            'github_url'    => Request::post('github_url', '') ?: null,
            'tech_stack'    => mb_substr(Request::post('tech_stack', ''), 0, 255),
            'is_featured'   => Request::post('is_featured') ? 1 : 0,
            'display_order' => (int) Request::post('display_order', 0),
            'status'        => Request::post('status', 'published') === 'draft' ? 'draft' : 'published',
        ]);

        Session::flash('success', 'Proje güncellendi.');
        $this->redirect('/admin/portfolio');
    }

    public function delete(string $id): void
    {
        Project::deleteById((int) $id);
        Session::flash('success', 'Proje silindi.');
        $this->redirect('/admin/portfolio');
    }
}
