<?php

namespace Plugins\Collections\Controllers;

use App\Models\Post;
use Core\Controller;
use Core\ImageProcessor;
use Core\Request;
use Core\Session;
use Plugins\Collections\Models\Collection;
use Plugins\Collections\Models\CollectionPost;

class CollectionAdminController extends Controller
{
    public function index(): void
    {
        $collections = Collection::allWithCount();
        $this->adminView('plugins/collections/views/admin/index', [
            'collections' => $collections,
            'pageTitle'   => 'Yazı Koleksiyonları & Seriler',
        ]);
    }

    public function create(): void
    {
        $allPosts = Post::allForAdmin();

        $this->adminView('plugins/collections/views/admin/form', [
            'collection'    => null,
            'assignedPosts' => [],
            'allPosts'      => $allPosts,
            'pageTitle'     => 'Yeni Koleksiyon Oluştur',
        ]);
    }

    public function store(): void
    {
        $title = trim(Request::post('title', ''));
        if ($title === '') {
            Session::flash('error', 'Koleksiyon adı boş bırakılamaz.');
            $this->redirect('/admin/collections/create');
        }

        $customSlug = trim(Request::post('slug', ''));
        $slug = Collection::ensureUniqueSlug(slugify($customSlug !== '' ? $customSlug : $title));

        $color = Request::post('color', '#3b82f6');
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $color = '#3b82f6';
        }

        $icon = trim(Request::post('icon', 'fa-solid fa-layer-group'));
        if ($icon === '') {
            $icon = 'fa-solid fa-layer-group';
        }

        $coverImage = Request::post('cover_image', '');
        $file = Request::file('cover_image_file');
        if ($file && !empty($file['tmp_name'])) {
            try {
                $media = ImageProcessor::processFeatured($file, $title, $title);
                $coverImage = $media['filepath'];
            } catch (\Throwable $e) {
                Session::flash('error', 'Kapak görseli yüklenemedi: ' . $e->getMessage());
                $this->redirect('/admin/collections/create');
            }
        }

        $status = Request::post('status', 'published') === 'draft' ? 'draft' : 'published';

        $collectionId = Collection::create([
            'title'       => mb_substr($title, 0, 255),
            'slug'        => $slug,
            'description' => Request::post('description', '') ?: null,
            'icon'        => mb_substr($icon, 0, 100),
            'color'       => $color,
            'cover_image' => $coverImage ?: null,
            'status'      => $status,
        ]);

        // Yazı atamaları & sıralama
        $postOrders = Request::post('post_orders', []);
        $selectedPostIds = Request::post('selected_posts', []);

        if (is_array($selectedPostIds)) {
            $syncData = [];
            foreach ($selectedPostIds as $postId) {
                $order = isset($postOrders[$postId]) ? (int) $postOrders[$postId] : 1;
                $syncData[(int) $postId] = $order;
            }
            CollectionPost::syncCollectionPosts($collectionId, $syncData);
        }

        Session::flash('success', 'Koleksiyon başarıyla oluşturuldu.');
        $this->redirect('/admin/collections');
    }

    public function edit(string $id): void
    {
        $collection = Collection::find((int) $id);
        if (!$collection) {
            Session::flash('error', 'Koleksiyon bulunamadı.');
            $this->redirect('/admin/collections');
        }

        $assignedPosts = Collection::getPosts((int) $collection['id'], false);
        $allPosts = Post::allForAdmin();

        $this->adminView('plugins/collections/views/admin/form', [
            'collection'    => $collection,
            'assignedPosts' => $assignedPosts,
            'allPosts'      => $allPosts,
            'pageTitle'     => 'Koleksiyonu Düzenle: ' . $collection['title'],
        ]);
    }

    public function update(string $id): void
    {
        $collection = Collection::find((int) $id);
        if (!$collection) {
            Session::flash('error', 'Koleksiyon bulunamadı.');
            $this->redirect('/admin/collections');
        }

        $title = trim(Request::post('title', ''));
        if ($title === '') {
            Session::flash('error', 'Koleksiyon adı boş bırakılamaz.');
            $this->redirect('/admin/collections/' . $collection['id'] . '/edit');
        }

        $customSlug = trim(Request::post('slug', ''));
        $slug = Collection::ensureUniqueSlug(slugify($customSlug !== '' ? $customSlug : $title), (int) $collection['id']);

        $color = Request::post('color', '#3b82f6');
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $color = '#3b82f6';
        }

        $icon = trim(Request::post('icon', 'fa-solid fa-layer-group'));
        if ($icon === '') {
            $icon = 'fa-solid fa-layer-group';
        }

        $coverImage = Request::post('cover_image', $collection['cover_image'] ?? '');
        $file = Request::file('cover_image_file');
        if ($file && !empty($file['tmp_name'])) {
            try {
                $media = ImageProcessor::processFeatured($file, $title, $title);
                $coverImage = $media['filepath'];
            } catch (\Throwable $e) {
                Session::flash('error', 'Kapak görseli yüklenemedi: ' . $e->getMessage());
                $this->redirect('/admin/collections/' . $collection['id'] . '/edit');
            }
        }

        $status = Request::post('status', 'published') === 'draft' ? 'draft' : 'published';

        Collection::updateById((int) $collection['id'], [
            'title'       => mb_substr($title, 0, 255),
            'slug'        => $slug,
            'description' => Request::post('description', '') ?: null,
            'icon'        => mb_substr($icon, 0, 100),
            'color'       => $color,
            'cover_image' => $coverImage ?: null,
            'status'      => $status,
        ]);

        // Yazı atamaları & sıralama
        $postOrders = Request::post('post_orders', []);
        $selectedPostIds = Request::post('selected_posts', []);

        $syncData = [];
        if (is_array($selectedPostIds)) {
            foreach ($selectedPostIds as $postId) {
                $order = isset($postOrders[$postId]) ? (int) $postOrders[$postId] : 1;
                $syncData[(int) $postId] = $order;
            }
        }
        CollectionPost::syncCollectionPosts((int) $collection['id'], $syncData);

        Session::flash('success', 'Koleksiyon başarıyla güncellendi.');
        $this->redirect('/admin/collections');
    }

    public function delete(string $id): void
    {
        $collection = Collection::find((int) $id);
        if ($collection) {
            Collection::deleteById((int) $collection['id']);
            Session::flash('success', 'Koleksiyon silindi.');
        } else {
            Session::flash('error', 'Koleksiyon bulunamadı.');
        }

        $this->redirect('/admin/collections');
    }
}
