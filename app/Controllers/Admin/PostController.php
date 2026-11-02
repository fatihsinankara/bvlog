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
        // Sayfa açılışında zamanı gelmiş yazı varsa anında yayına al
        Post::publishDueScheduledPosts();

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
            'post'                => null,
            'categories'          => $categories,
            'selectedCategoryIds' => [],
            'tagsString'          => '',
            'pageTitle'           => 'Yeni Yazı Oluştur',
        ]);
    }

    public function store(): void
    {
        $title = Request::post('title', '');
        if ($title === '') {
            Session::flash('error', 'Yazı başlığı boş bırakılamaz.');
            $this->redirect('/admin/posts/create');
        }

        $categoryIds = $this->extractCategoryIds();
        if (empty($categoryIds)) {
            Session::flash('error', 'Yazıyı yayınlamak veya kaydetmek için listeden en az 1 kategori seçmelisiniz.');
            $this->redirect('/admin/posts/create');
        }

        $publishInfo = $this->resolvePublishStatusAndDate(null);
        if ($publishInfo['error'] !== null) {
            Session::flash('error', $publishInfo['error']);
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

        $primaryCategoryId = $categoryIds[0];

        $postId = Post::create([
            'user_id'         => (int) Session::get('admin_user_id', 1),
            'category_id'     => $primaryCategoryId,
            'title'           => $title,
            'slug'            => $slug,
            'summary'         => mb_substr(Request::post('summary', ''), 0, 500),
            'content'         => $cleanContent,
            'cover_image'     => $coverImage ?: null,
            'seo_title'       => mb_substr(Request::post('seo_title', ''), 0, 255) ?: null,
            'seo_description' => mb_substr(Request::post('seo_description', ''), 0, 255) ?: null,
            'is_featured'     => Request::post('is_featured') ? 1 : 0,
            'status'          => $publishInfo['status'],
            'published_at'    => $publishInfo['published_at'],
        ]);

        Post::syncCategories($postId, $categoryIds);
        Post::syncTags($postId, Request::post('tags', ''));
        Hook::doAction('post_saved', $postId);

        if ($publishInfo['status'] === 'scheduled') {
            Session::flash('success', 'Yazı ' . format_date($publishInfo['published_at'], true) . ' tarihinde yayınlanmak üzere zamanlandı.');
        } elseif ($publishInfo['status'] === 'draft') {
            Session::flash('success', 'Yazı taslak olarak kaydedildi.');
        } else {
            Session::flash('success', 'Yazı başarıyla yayınlandı.');
        }

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
        $postCategories = Post::getCategories((int) $post['id']);
        $selectedCategoryIds = array_values(array_map(fn($c) => (int) $c['id'], $postCategories));
        if (empty($selectedCategoryIds) && !empty($post['category_id'])) {
            $selectedCategoryIds = [(int) $post['category_id']];
        }

        $tags = Post::getTags((int) $post['id']);
        $tagsString = implode(', ', array_map(fn($t) => $t['name'], $tags));

        $this->adminView('admin/posts/form', [
            'post'                => $post,
            'categories'          => $categories,
            'selectedCategoryIds' => $selectedCategoryIds,
            'tagsString'          => $tagsString,
            'pageTitle'           => 'Yazıyı Düzenle',
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

        $categoryIds = $this->extractCategoryIds();
        if (empty($categoryIds)) {
            Session::flash('error', 'Yazıyı güncellemek için listeden en az 1 kategori seçmelisiniz.');
            $this->redirect('/admin/posts/' . $post['id'] . '/edit');
        }

        $publishInfo = $this->resolvePublishStatusAndDate($post);
        if ($publishInfo['error'] !== null) {
            Session::flash('error', $publishInfo['error']);
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

        $primaryCategoryId = $categoryIds[0];

        Post::updateById((int) $post['id'], [
            'category_id'     => $primaryCategoryId,
            'title'           => $title,
            'slug'            => $slug,
            'summary'         => mb_substr(Request::post('summary', ''), 0, 500),
            'content'         => $cleanContent,
            'cover_image'     => $coverImage ?: null,
            'seo_title'       => mb_substr(Request::post('seo_title', ''), 0, 255) ?: null,
            'seo_description' => mb_substr(Request::post('seo_description', ''), 0, 255) ?: null,
            'is_featured'     => Request::post('is_featured') ? 1 : 0,
            'status'          => $publishInfo['status'],
            'published_at'    => $publishInfo['published_at'],
        ]);

        Post::syncCategories((int) $post['id'], $categoryIds);
        Post::syncTags((int) $post['id'], Request::post('tags', ''));
        Hook::doAction('post_saved', (int) $post['id']);

        if ($publishInfo['status'] === 'scheduled') {
            Session::flash('success', 'Yazı ' . format_date($publishInfo['published_at'], true) . ' tarihinde yayınlanmak üzere zamanlandı.');
        } else {
            Session::flash('success', 'Yazı başarıyla güncellendi.');
        }

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

    /**
     * POST isteğinden gelen çoklu kategori ID dizisini doğrular ve döndürür.
     *
     * @return int[]
     */
    private function extractCategoryIds(): array
    {
        $raw = $_POST['category_ids'] ?? null;
        if (!is_array($raw)) {
            $single = (int) Request::post('category_id', 0);
            $raw = $single > 0 ? [$single] : [];
        }

        $ids = [];
        foreach ($raw as $val) {
            $cid = (int) $val;
            if ($cid > 0 && !in_array($cid, $ids, true)) {
                $ids[] = $cid;
            }
        }

        return $ids;
    }

    /**
     * Formdan gelen status ve published_at değerlerini ayrıştırır,
     * ileri tarihli yayınlarda otomatik olarak 'scheduled' durumuna geçirir.
     *
     * @return array{status: string, published_at: string|null, error: string|null}
     */
    private function resolvePublishStatusAndDate(?array $existingPost = null): array
    {
        $rawStatus = Request::post('status', 'published');
        $status = in_array($rawStatus, ['published', 'draft', 'scheduled'], true) ? $rawStatus : 'published';

        $rawDate = trim((string) Request::post('published_at', ''));
        $nowTs = time();
        $parsedTs = null;

        if ($rawDate !== '') {
            $ts = strtotime($rawDate);
            if ($ts === false) {
                return [
                    'status'       => $status,
                    'published_at' => null,
                    'error'        => 'Geçersiz yayın tarihi veya saat formatı girdiniz.',
                ];
            }
            $parsedTs = $ts;
        }

        if ($existingPost && ($existingPost['status'] ?? '') === 'scheduled' && $status === 'published') {
            $existingFormatted = !empty($existingPost['published_at']) ? date('Y-m-d\TH:i', strtotime((string) $existingPost['published_at'])) : '';
            if ($rawDate === '' || $rawDate === $existingFormatted) {
                return [
                    'status'       => 'published',
                    'published_at' => date('Y-m-d H:i:s', $nowTs),
                    'error'        => null,
                ];
            }
        }

        if ($status === 'scheduled') {
            if ($parsedTs === null) {
                return [
                    'status'       => 'scheduled',
                    'published_at' => null,
                    'error'        => 'Zamanlanmış yazı için ileri bir yayın tarihi ve saati seçmelisiniz.',
                ];
            }
            if ($parsedTs <= $nowTs) {
                return [
                    'status'       => 'published',
                    'published_at' => date('Y-m-d H:i:s', $parsedTs),
                    'error'        => null,
                ];
            }
            return [
                'status'       => 'scheduled',
                'published_at' => date('Y-m-d H:i:s', $parsedTs),
                'error'        => null,
            ];
        }

        if ($status === 'published') {
            if ($parsedTs !== null && $parsedTs > ($nowTs + 60)) {
                return [
                    'status'       => 'scheduled',
                    'published_at' => date('Y-m-d H:i:s', $parsedTs),
                    'error'        => null,
                ];
            }

            if ($parsedTs !== null) {
                $publishedAt = date('Y-m-d H:i:s', $parsedTs);
            } elseif (!empty($existingPost['published_at'])) {
                $publishedAt = (string) $existingPost['published_at'];
            } else {
                $publishedAt = date('Y-m-d H:i:s', $nowTs);
            }

            return [
                'status'       => 'published',
                'published_at' => $publishedAt,
                'error'        => null,
            ];
        }

        $draftDate = $parsedTs !== null
            ? date('Y-m-d H:i:s', $parsedTs)
            : ($existingPost['published_at'] ?? date('Y-m-d H:i:s', $nowTs));

        return [
            'status'       => 'draft',
            'published_at' => $draftDate,
            'error'        => null,
        ];
    }
}
