<?php

namespace App\Controllers\Admin;

use App\Models\Category;
use App\Models\Post;
use Core\Controller;
use Core\Hook;
use Core\HtmlSanitizer;
use Core\ImageProcessor;
use Core\Request;
use Core\Session;

class PostController extends Controller
{
    public function index(): void
    {
        $posts = Post::allForAdmin();
        $this->adminView('admin/posts/index', [
            'posts'     => $posts,
            'pageTitle' => 'Blog Yazıları',
        ]);
    }

    public function create(): void
    {
        $categories = Category::all('name ASC');
        $this->adminView('admin/posts/form', [
            'post'       => null,
            'categories' => $categories,
            'tagsString' => '',
            'pageTitle'  => 'Yeni Yazı Oluştur',
        ]);
    }

    public function store(): void
    {
        $title = Request::post('title', '');
        if ($title === '') {
            Session::flash('error', 'Yazı başlığı boş bırakılamaz.');
            $this->redirect('/admin/posts/create');
        }

        $customSlug = Request::post('slug', '');
        $slug = Post::ensureUniqueSlug(slugify($customSlug !== '' ? $customSlug : $title));

        $rawContent = (string) ($_POST['content'] ?? '');
        $cleanContent = HtmlSanitizer::clean($rawContent);

        $coverImage = Request::post('cover_image', '');
        $file = Request::file('cover_image_file');
        if ($file && !empty($file['tmp_name'])) {
            try {
                $media = ImageProcessor::processFeatured($file, $title, $title);
                $coverImage = $media['filepath'];
            } catch (\Throwable $e) {
                Session::flash('error', 'Kapak görseli işlenemedi: ' . $e->getMessage());
                $this->redirect('/admin/posts/create');
            }
        }

        $status = Request::post('status', 'published') === 'draft' ? 'draft' : 'published';
        $categoryId = (int) Request::post('category_id', 0);

        $postId = Post::create([
            'user_id'         => (int) Session::get('admin_user_id', 1),
            'category_id'     => $categoryId > 0 ? $categoryId : null,
            'title'           => $title,
            'slug'            => $slug,
            'summary'         => mb_substr(Request::post('summary', ''), 0, 500),
            'content'         => $cleanContent,
            'cover_image'     => $coverImage ?: null,
            'seo_title'       => mb_substr(Request::post('seo_title', ''), 0, 255) ?: null,
            'seo_description' => mb_substr(Request::post('seo_description', ''), 0, 255) ?: null,
            'is_featured'     => Request::post('is_featured') ? 1 : 0,
            'status'          => $status,
            'published_at'    => date('Y-m-d H:i:s'),
        ]);

        Post::syncTags($postId, Request::post('tags', ''));
        Hook::doAction('post_saved', $postId);

        Session::flash('success', 'Yazı başarıyla kaydedildi.');
        $this->redirect('/admin/posts');
    }

    public function edit(string $id): void
    {
        $post = Post::find((int) $id);
        if (!$post) {
            Session::flash('error', 'Yazı bulunamadı.');
            $this->redirect('/admin/posts');
        }

        $categories = Category::all('name ASC');
        $tags = Post::getTags((int) $post['id']);
        $tagsString = implode(', ', array_map(fn($t) => $t['name'], $tags));

        $this->adminView('admin/posts/form', [
            'post'       => $post,
            'categories' => $categories,
            'tagsString' => $tagsString,
            'pageTitle'  => 'Yazıyı Düzenle',
        ]);
    }

    public function update(string $id): void
    {
        $post = Post::find((int) $id);
        if (!$post) {
            Session::flash('error', 'Yazı bulunamadı.');
            $this->redirect('/admin/posts');
        }

        $title = Request::post('title', '');
        if ($title === '') {
            Session::flash('error', 'Yazı başlığı boş bırakılamaz.');
            $this->redirect('/admin/posts/' . $post['id'] . '/edit');
        }

        $customSlug = Request::post('slug', '');
        $slug = Post::ensureUniqueSlug(slugify($customSlug !== '' ? $customSlug : $title), (int) $post['id']);

        $rawContent = (string) ($_POST['content'] ?? '');
        $cleanContent = HtmlSanitizer::clean($rawContent);

        $coverImage = Request::post('cover_image', $post['cover_image'] ?? '');
        $file = Request::file('cover_image_file');
        if ($file && !empty($file['tmp_name'])) {
            try {
                $media = ImageProcessor::processFeatured($file, $title, $title);
                $coverImage = $media['filepath'];
            } catch (\Throwable $e) {
                Session::flash('error', 'Kapak görseli işlenemedi: ' . $e->getMessage());
                $this->redirect('/admin/posts/' . $post['id'] . '/edit');
            }
        }

        $status = Request::post('status', 'published') === 'draft' ? 'draft' : 'published';
        $categoryId = (int) Request::post('category_id', 0);

        Post::updateById((int) $post['id'], [
            'category_id'     => $categoryId > 0 ? $categoryId : null,
            'title'           => $title,
            'slug'            => $slug,
            'summary'         => mb_substr(Request::post('summary', ''), 0, 500),
            'content'         => $cleanContent,
            'cover_image'     => $coverImage ?: null,
            'seo_title'       => mb_substr(Request::post('seo_title', ''), 0, 255) ?: null,
            'seo_description' => mb_substr(Request::post('seo_description', ''), 0, 255) ?: null,
            'is_featured'     => Request::post('is_featured') ? 1 : 0,
            'status'          => $status,
        ]);

        Post::syncTags((int) $post['id'], Request::post('tags', ''));
        Hook::doAction('post_saved', (int) $post['id']);

        Session::flash('success', 'Yazı başarıyla güncellendi.');
        $this->redirect('/admin/posts');
    }

    public function delete(string $id): void
    {
        $postId = (int) $id;
        Post::deleteById($postId);
        Hook::doAction('post_deleted', $postId);
        Session::flash('success', 'Yazı silindi.');
        $this->redirect('/admin/posts');
    }
}
