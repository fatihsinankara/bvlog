<?php

namespace App\Controllers;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use Core\Controller;
use Core\Database;
use Core\Hook;
use Core\RateLimiter;
use Core\Request;
use Core\Response;
use Core\Router;
use Core\Security;
use Core\Seo;
use Core\Session;

class BlogController extends Controller
{
    public function index(): void
    {
        $page = max(1, (int) Request::get('page', 1));
        $perPage = max(1, (int) Setting::get('posts_per_page', 10));
        $search = Request::get('q', '');
        $offset = ($page - 1) * $perPage;

        $posts = Post::publishedList($perPage, $offset, null, $search);
        $total = Post::countPublished(null, $search);
        $totalPages = (int) ceil($total / $perPage);
        $categories = Category::allWithCounts();

        $this->view('blog/index', [
            'posts'          => $posts,
            'categories'     => $categories,
            'activeCategory' => null,
            'search'         => $search,
            'page'           => $page,
            'totalPages'     => $totalPages,
            'total'          => $total,
            'postStyle'      => Setting::get('homepage_post_style', 'list'),
            'meta'           => [
                'title'       => $search ? "'{$search}' Arama Sonuçları" : 'Blog Yazıları',
                'description' => 'Tüm makaleler, teknik notlar ve düşünceler.',
            ],
        ]);
    }

    public function category(string $slug): void
    {
        $category = Category::findBySlug($slug);
        if (!$category) {
            Router::abort(404, 'Kategori bulunamadı.');
        }

        $page = max(1, (int) Request::get('page', 1));
        $perPage = max(1, (int) Setting::get('posts_per_page', 10));
        $offset = ($page - 1) * $perPage;

        $posts = Post::publishedList($perPage, $offset, (int) $category['id']);
        $total = Post::countPublished((int) $category['id']);
        $totalPages = (int) ceil($total / $perPage);
        $categories = Category::allWithCounts();

        $this->view('blog/index', [
            'posts'          => $posts,
            'categories'     => $categories,
            'activeCategory' => $category,
            'search'         => '',
            'page'           => $page,
            'totalPages'     => $totalPages,
            'total'          => $total,
            'postStyle'      => Setting::get('homepage_post_style', 'list'),
            'meta'           => [
                'title'       => $category['name'] . ' Kategorisi',
                'description' => $category['description'] ?: ($category['name'] . ' kategorisindeki tüm yazılar.'),
                'image'       => $category['image'] ?? '',
            ],
        ]);
    }

    public function show(string $arg1, ?string $arg2 = null): void
    {
        $slug = $arg2 !== null ? $arg2 : $arg1;

        $post = Post::findDetailedBySlug($slug);
        if (!$post || ($post['status'] !== 'published' && !Session::has('admin_user_id'))) {
            Router::abort(404, 'Aradığınız yazı bulunamadı.');
        }

        // Canonical URL ve kalıcı yönlendirme (301) kontrolü
        if ($post['status'] === 'published' && !Session::has('admin_user_id')) {
            $canonicalUrl = post_url($post);
            $canonicalPath = parse_url($canonicalUrl, PHP_URL_PATH) ?: '';
            $currentPath = Request::path();

            if ($canonicalPath !== '' && $currentPath !== $canonicalPath) {
                Response::redirect($canonicalUrl, 301);
            }
        }

        // Görüntülenme sayısını oturum başına bir kez artır
        $viewedKey = 'viewed_post_' . $post['id'];
        if (!Session::has($viewedKey)) {
            Database::query('UPDATE posts SET views_count = views_count + 1 WHERE id = :id', ['id' => $post['id']]);
            Session::set($viewedKey, true);
            $post['views_count']++;
        }

        $post['content'] = Hook::applyFilters('post_content', $post['content'], $post);
        $tags = Post::getTags((int) $post['id']);
        $comments = is_module_active('comments') ? Comment::approvedForPost((int) $post['id']) : [];
        $author = User::firstAuthor() ?? [];

        $schema = Seo::buildArticleSchema($post, $author);

        $this->view('blog/show', [
            'post'     => $post,
            'tags'     => $tags,
            'comments' => $comments,
            'author'   => $author,
            'meta'     => [
                'title'       => $post['seo_title'] ?: $post['title'],
                'description' => $post['seo_description'] ?: ($post['summary'] ?: mb_substr(strip_tags($post['content']), 0, 160)),
                'image'       => $post['cover_image'] ?? '',
                'type'        => 'article',
                'schema'      => $schema,
            ],
        ]);
    }

    public function storeComment(string $arg1, ?string $arg2 = null): void
    {
        $slug = $arg2 !== null ? $arg2 : $arg1;

        if (!is_module_active('comments')) {
            Router::abort(404);
        }

        $post = Post::findBySlug($slug);
        if (!$post || $post['status'] !== 'published') {
            Router::abort(404);
        }

        $postUrl = post_url($post);

        // Honeypot spam kontrolü
        if (!empty(Request::post('website_hp'))) {
            $this->redirect($postUrl);
        }

        $ip = Security::getClientIp();
        $rateKey = 'comment_' . $ip;
        if (RateLimiter::tooManyAttempts($rateKey, 3, 300)) {
            Session::flash('error', 'Çok sık yorum gönderiyorsunuz. Lütfen birkaç dakika bekleyin.');
            $this->redirect($postUrl . '#comments');
        }

        $name = Request::post('author_name', '');
        $email = Request::post('author_email', '');
        $content = Request::post('content', '');

        if (mb_strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($content) < 3) {
            Session::flash('error', 'Lütfen adınızı, geçerli e-posta adresinizi ve yorumunuzu eksiksiz girin.');
            $this->redirect($postUrl . '#comments');
        }

        RateLimiter::hit($rateKey, 300);

        $moderation = Setting::get('comments_moderation', '1') === '1';
        $status = $moderation ? 'pending' : 'approved';

        Comment::create([
            'post_id'      => (int) $post['id'],
            'author_name'  => mb_substr($name, 0, 100),
            'author_email' => mb_substr($email, 0, 150),
            'content'      => mb_substr(strip_tags($content), 0, 2000),
            'status'       => $status,
            'ip_address'   => $ip,
        ]);

        if ($moderation) {
            Session::flash('success', 'Yorumunuz alındı! Yönetici onayından sonra yayınlanacaktır.');
        } else {
            Session::flash('success', 'Yorumunuz başarıyla yayınlandı.');
        }

        $this->redirect($postUrl . '#comments');
    }
}
