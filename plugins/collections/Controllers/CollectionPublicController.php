<?php

namespace Plugins\Collections\Controllers;

use Core\Cache;
use Core\Controller;
use Core\Router;
use Plugins\Collections\Models\Collection;

class CollectionPublicController extends Controller
{
    public function index(): void
    {
        $collections = Cache::remember('blog:collections_list', function (): array {
            return Collection::allPublishedWithCount();
        }, null, 'blog', 'Koleksiyonlar & Seriler Listesi');

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
        $bundle = Cache::remember("blog:collection:{$slug}", function () use ($slug): ?array {
            $collection = Collection::findBySlug($slug);
            if (!$collection || $collection['status'] !== 'published') {
                return null;
            }

            return [
                'collection'       => $collection,
                'posts'            => Collection::getPosts((int) $collection['id'], true),
                'totalReadingTime' => Collection::getTotalReadingTime((int) $collection['id']),
            ];
        }, null, 'blog', "Koleksiyon Detayı: {$slug}");

        if (!$bundle || empty($bundle['collection'])) {
            Router::abort(404, 'Aradığınız koleksiyon bulunamadı.');
        }

        $collection = $bundle['collection'];
        $posts = $bundle['posts'];
        $totalReadingTime = (int) $bundle['totalReadingTime'];

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
