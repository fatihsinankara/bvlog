<?php

use Core\Hook;
use Core\Request;
use Core\Router;
use Core\View;
use Plugins\Collections\Controllers\CollectionAdminController;
use Plugins\Collections\Controllers\CollectionPublicController;
use Plugins\Collections\Models\Collection;
use Plugins\Collections\Models\CollectionPost;

// 1. Eklenti İçi Sınıf Otomatik Yükleyicisi (Autoloader)
spl_autoload_register(function (string $class) {
    $prefix = 'Plugins\\Collections\\';
    if (str_starts_with($class, $prefix)) {
        $relativeClass = substr($class, strlen($prefix));
        $file = __DIR__ . '/' . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

// 2. Rotalar — Yönetim Paneli
Router::get('/admin/collections', [CollectionAdminController::class, 'index'], ['auth']);
Router::get('/admin/collections/create', [CollectionAdminController::class, 'create'], ['auth']);
Router::post('/admin/collections', [CollectionAdminController::class, 'store'], ['auth']);
Router::get('/admin/collections/{id}/edit', [CollectionAdminController::class, 'edit'], ['auth']);
Router::post('/admin/collections/{id}/update', [CollectionAdminController::class, 'update'], ['auth']);
Router::post('/admin/collections/{id}/delete', [CollectionAdminController::class, 'delete'], ['auth']);

// 3. Rotalar — Ön Yüz (Public)
Router::get('/collections', [CollectionPublicController::class, 'index']);
Router::get('/collections/{slug}', [CollectionPublicController::class, 'show']);
Router::get('/koleksiyonlar', [CollectionPublicController::class, 'index']);
Router::get('/koleksiyonlar/{slug}', [CollectionPublicController::class, 'show']);

// 4. Kanca: Admin Yan Menüsüne "Koleksiyonlar" Linki Ekleme
Hook::addAction('admin_sidebar_menu', function () {
    $currentPath = Request::path();
    $isActive = str_starts_with($currentPath, '/admin/collections');
    $linkClass = $isActive 
        ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' 
        : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800';

    $url = e(url('/admin/collections'));
    echo <<<HTML
    <a href="{$url}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {$linkClass}">
        <i class="fa-solid fa-layer-group w-4 text-center"></i>
        <span>Koleksiyonlar</span>
    </a>
HTML;
});

// 5. Kanca: Yazı Ekleme/Düzenleme Formuna "Koleksiyon" Seçim Kartı Ekleme
Hook::addAction('admin_post_form_sidebar', function ($post) {
    $collections = Collection::all('title ASC');
    $currentRelation = !empty($post['id']) ? CollectionPost::getPostRelation((int) $post['id']) : null;

    echo View::renderPartial('plugins/collections/views/components/admin-post-selector', [
        'post'            => $post,
        'collections'     => $collections,
        'currentRelation' => $currentRelation,
    ]);
});

// 6. Kanca: Yazı Kaydedildiğinde Koleksiyon İlişkisini Güncelleme
Hook::addAction('post_saved', function ($postId) {
    if (isset($_POST['collection_id'])) {
        $collectionId = (int) Request::post('collection_id', 0);
        $order = (int) Request::post('collection_order', 1);
        CollectionPost::syncPost((int) $postId, $collectionId, $order);
    }
});

// 7. Kanca: Yazı Silindiğinde Koleksiyon İlişkisini Temizleme
Hook::addAction('post_deleted', function ($postId) {
    CollectionPost::removePost((int) $postId);
});

// 8. Kanca: Yazı Sayfasında Makale Üstüne Şık Seri Kartı Basma
Hook::addAction('post_before_content', function ($post) {
    if (empty($post['id'])) {
        return;
    }

    $seriesData = Collection::getCollectionForPost((int) $post['id']);
    if (!$seriesData) {
        return;
    }

    echo View::renderPartial('plugins/collections/views/components/post-series-box', array_merge($seriesData, [
        'current_post' => $post,
    ]));
});

// 9. Kanca: Ön Yüz Masaüstü Menüsüne "Koleksiyonlar" Ekleme
Hook::addAction('frontend_nav_items', function () {
    $currentPath = Request::path();
    $isActive = str_starts_with($currentPath, '/collections') || str_starts_with($currentPath, '/koleksiyonlar');
    $cls = $isActive 
        ? 'bg-zinc-100 dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 font-semibold' 
        : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100';

    $url = e(url('/collections'));
    echo <<<HTML
    <a href="{$url}" class="px-3 py-1.5 rounded-lg transition-colors {$cls}">
        Koleksiyonlar
    </a>
HTML;
});

// 10. Kanca: Ön Yüz Mobil Menüye "Koleksiyonlar" Ekleme
Hook::addAction('frontend_mobile_nav_items', function () {
    $url = e(url('/collections'));
    echo <<<HTML
    <a href="{$url}" class="block px-3 py-2 rounded-lg text-sm font-medium hover:bg-zinc-100 dark:hover:bg-zinc-900">Koleksiyonlar</a>
HTML;
});
