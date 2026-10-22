<?php

namespace Plugins\Collections\Controllers;

use Core\Controller;
use Core\Router;
use Plugins\Collections\Models\Collection;

class CollectionPublicController extends Controller
{
    public function index(): void
    {
        $collections = Collection::allPublishedWithCount();

        $this->view('plugins/collections/views/public/index', [
            'collections' => $collections,
            'meta'        => [
                'title'       => 'Yazı Koleksiyonları & Seriler',
                'description' => 'Birbiriyle bağlantılı, adım adım rehberler ve çok parçalı blog serileri.',
                'type'        => 'website',
            ],
        ]);
    }

    public function show(string $slug): void
    {
        $collection = Collection::findBySlug($slug);
        if (!$collection || $collection['status'] !== 'published') {
            Router::abort(404, 'Aradığınız koleksiyon bulunamadı.');
        }

        $posts = Collection::getPosts((int) $collection['id'], true);
        $totalReadingTime = Collection::getTotalReadingTime((int) $collection['id']);

        $this->view('plugins/collections/views/public/show', [
            'collection'       => $collection,
            'posts'            => $posts,
            'totalReadingTime' => $totalReadingTime,
            'meta'             => [
                'title'       => $collection['title'] . ' — Koleksiyon',
                'description' => $collection['description'] ?: ($collection['title'] . ' serisine ait tüm yazılar ve bölümler.'),
                'image'       => $collection['cover_image'] ?? '',
                'type'        => 'website',
            ],
        ]);
    }
}
