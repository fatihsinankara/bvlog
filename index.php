<?php

declare(strict_types=1);

define('BVLOG_ROOT', __DIR__);

require_once BVLOG_ROOT . '/core/Autoloader.php';
Core\Autoloader::register();
require_once BVLOG_ROOT . '/core/Helpers.php';

$appConfig = require BVLOG_ROOT . '/config/app.php';
date_default_timezone_set($appConfig['timezone'] ?? 'Europe/Istanbul');

Core\Security::sendHeaders();
Core\Session::start();

$currentPath = Core\Request::path();

// 1. Kurulum Kontrolü
if (!is_installed()) {
    if (!str_starts_with($currentPath, '/install')) {
        Core\Response::redirect('/install');
    }

    Core\Router::get('/install', [App\Controllers\InstallController::class, 'index']);
    Core\Router::post('/install/database', [App\Controllers\InstallController::class, 'saveDatabase']);
    Core\Router::post('/install/complete', [App\Controllers\InstallController::class, 'complete']);
    Core\Router::dispatch();
    exit;
}

// 2. Sürüm & Veritabanı Otomatik Yükseltme Kontrolü (FTP Güncellemeleri İçin)
Core\Migrator::checkAndUpgrade($appConfig['version'] ?? '1.0.0');

// 3. Eklentileri Yükle (Hata korumalı ve otomatik karantina destekli)
Core\PluginManager::boot();
Core\Hook::doAction('app_init');

// 3. Bakım Modu Kontrolü (Yöneticiler hariç)
if (setting('maintenance_mode', '0') === '1' && !str_starts_with($currentPath, '/admin') && !Core\Session::has('admin_user_id')) {
    http_response_code(503);
    Core\View::render('errors/maintenance', [], null);
    exit;
}

// 4. Rotalar — Ön Yüz (Public)
Core\Router::get('/', [App\Controllers\HomeController::class, 'index']);
Core\Router::get('/blog', [App\Controllers\BlogController::class, 'index']);
Core\Router::get('/category/{slug}', [App\Controllers\BlogController::class, 'category']);
Core\Router::get('/blog/{slug}', [App\Controllers\BlogController::class, 'show']);
Core\Router::post('/blog/{slug}/comment', [App\Controllers\BlogController::class, 'storeComment']);

// Modüler Rotalar (Portfolyo, CV, İletişim)
Core\Router::get('/portfolio', [App\Controllers\PortfolioController::class, 'index'], ['module:portfolio']);
Core\Router::get('/portfolio/{slug}', [App\Controllers\PortfolioController::class, 'show'], ['module:portfolio']);
Core\Router::get('/cv', [App\Controllers\CvController::class, 'index'], ['module:cv']);
Core\Router::get('/contact', [App\Controllers\ContactController::class, 'index'], ['module:contact']);
Core\Router::post('/contact', [App\Controllers\ContactController::class, 'send'], ['module:contact']);

// SEO & Besleme Rotaları
Core\Router::get('/sitemap.xml', [App\Controllers\SeoController::class, 'sitemap']);
Core\Router::get('/rss.xml', [App\Controllers\SeoController::class, 'rss']);
Core\Router::get('/feed', [App\Controllers\SeoController::class, 'rss']);
Core\Router::get('/robots.txt', [App\Controllers\SeoController::class, 'robots']);

// Kurulum sonrası /install erişimini 403 ile engelle
Core\Router::get('/install', [App\Controllers\InstallController::class, 'index']);

// 5. Rotalar — Yönetim Paneli (Admin)
Core\Router::get('/admin/login', [App\Controllers\Admin\AuthController::class, 'showLogin'], ['guest']);
Core\Router::post('/admin/login', [App\Controllers\Admin\AuthController::class, 'login'], ['guest']);
Core\Router::post('/admin/logout', [App\Controllers\Admin\AuthController::class, 'logout'], ['auth']);

Core\Router::get('/admin', [App\Controllers\Admin\DashboardController::class, 'index'], ['auth']);

// Yazı Yönetimi
Core\Router::get('/admin/posts', [App\Controllers\Admin\PostController::class, 'index'], ['auth']);
Core\Router::get('/admin/posts/create', [App\Controllers\Admin\PostController::class, 'create'], ['auth']);
Core\Router::post('/admin/posts', [App\Controllers\Admin\PostController::class, 'store'], ['auth']);
Core\Router::get('/admin/posts/{id}/edit', [App\Controllers\Admin\PostController::class, 'edit'], ['auth']);
Core\Router::post('/admin/posts/{id}/update', [App\Controllers\Admin\PostController::class, 'update'], ['auth']);
Core\Router::post('/admin/posts/{id}/delete', [App\Controllers\Admin\PostController::class, 'delete'], ['auth']);

// Kategori Yönetimi
Core\Router::get('/admin/categories', [App\Controllers\Admin\CategoryController::class, 'index'], ['auth']);
Core\Router::get('/admin/categories/create', [App\Controllers\Admin\CategoryController::class, 'create'], ['auth']);
Core\Router::post('/admin/categories', [App\Controllers\Admin\CategoryController::class, 'store'], ['auth']);
Core\Router::get('/admin/categories/{id}/edit', [App\Controllers\Admin\CategoryController::class, 'edit'], ['auth']);
Core\Router::post('/admin/categories/{id}/update', [App\Controllers\Admin\CategoryController::class, 'update'], ['auth']);
Core\Router::post('/admin/categories/{id}/delete', [App\Controllers\Admin\CategoryController::class, 'delete'], ['auth']);

// Medya Galerisi
Core\Router::get('/admin/media', [App\Controllers\Admin\MediaController::class, 'index'], ['auth']);
Core\Router::get('/admin/media/json', [App\Controllers\Admin\MediaController::class, 'listJson'], ['auth']);
Core\Router::post('/admin/media/upload', [App\Controllers\Admin\MediaController::class, 'upload'], ['auth']);
Core\Router::post('/admin/media/{id}/update', [App\Controllers\Admin\MediaController::class, 'update'], ['auth']);
Core\Router::post('/admin/media/{id}/delete', [App\Controllers\Admin\MediaController::class, 'delete'], ['auth']);

// Portfolyo Yönetimi
Core\Router::get('/admin/portfolio', [App\Controllers\Admin\PortfolioController::class, 'index'], ['auth']);
Core\Router::get('/admin/portfolio/create', [App\Controllers\Admin\PortfolioController::class, 'create'], ['auth']);
Core\Router::post('/admin/portfolio', [App\Controllers\Admin\PortfolioController::class, 'store'], ['auth']);
Core\Router::get('/admin/portfolio/{id}/edit', [App\Controllers\Admin\PortfolioController::class, 'edit'], ['auth']);
Core\Router::post('/admin/portfolio/{id}/update', [App\Controllers\Admin\PortfolioController::class, 'update'], ['auth']);
Core\Router::post('/admin/portfolio/{id}/delete', [App\Controllers\Admin\PortfolioController::class, 'delete'], ['auth']);

// CV & Özgeçmiş Yönetimi
Core\Router::get('/admin/cv', [App\Controllers\Admin\CvController::class, 'index'], ['auth']);
Core\Router::get('/admin/cv/experience/create', [App\Controllers\Admin\CvController::class, 'createExperience'], ['auth']);
Core\Router::post('/admin/cv/experience', [App\Controllers\Admin\CvController::class, 'storeExperience'], ['auth']);
Core\Router::get('/admin/cv/experience/{id}/edit', [App\Controllers\Admin\CvController::class, 'editExperience'], ['auth']);
Core\Router::post('/admin/cv/experience/{id}/update', [App\Controllers\Admin\CvController::class, 'updateExperience'], ['auth']);
Core\Router::post('/admin/cv/experience/{id}/delete', [App\Controllers\Admin\CvController::class, 'deleteExperience'], ['auth']);

Core\Router::get('/admin/cv/education/create', [App\Controllers\Admin\CvController::class, 'createEducation'], ['auth']);
Core\Router::post('/admin/cv/education', [App\Controllers\Admin\CvController::class, 'storeEducation'], ['auth']);
Core\Router::get('/admin/cv/education/{id}/edit', [App\Controllers\Admin\CvController::class, 'editEducation'], ['auth']);
Core\Router::post('/admin/cv/education/{id}/update', [App\Controllers\Admin\CvController::class, 'updateEducation'], ['auth']);
Core\Router::post('/admin/cv/education/{id}/delete', [App\Controllers\Admin\CvController::class, 'deleteEducation'], ['auth']);

Core\Router::get('/admin/cv/category/create', [App\Controllers\Admin\CvController::class, 'createCategory'], ['auth']);
Core\Router::post('/admin/cv/category', [App\Controllers\Admin\CvController::class, 'storeCategory'], ['auth']);
Core\Router::post('/admin/cv/category/quick', [App\Controllers\Admin\CvController::class, 'quickStoreCategory'], ['auth']);
Core\Router::get('/admin/cv/category/{id}/edit', [App\Controllers\Admin\CvController::class, 'editCategory'], ['auth']);
Core\Router::post('/admin/cv/category/{id}/update', [App\Controllers\Admin\CvController::class, 'updateCategory'], ['auth']);
Core\Router::post('/admin/cv/category/{id}/delete', [App\Controllers\Admin\CvController::class, 'deleteCategory'], ['auth']);

Core\Router::get('/admin/cv/skill/create', [App\Controllers\Admin\CvController::class, 'createSkill'], ['auth']);
Core\Router::post('/admin/cv/skill', [App\Controllers\Admin\CvController::class, 'storeSkill'], ['auth']);
Core\Router::get('/admin/cv/skill/{id}/edit', [App\Controllers\Admin\CvController::class, 'editSkill'], ['auth']);
Core\Router::post('/admin/cv/skill/{id}/update', [App\Controllers\Admin\CvController::class, 'updateSkill'], ['auth']);
Core\Router::post('/admin/cv/skill/{id}/delete', [App\Controllers\Admin\CvController::class, 'deleteSkill'], ['auth']);

// Yorum ve Mesaj Yönetimi
Core\Router::get('/admin/comments', [App\Controllers\Admin\CommentController::class, 'index'], ['auth']);
Core\Router::post('/admin/comments/{id}/status', [App\Controllers\Admin\CommentController::class, 'updateStatus'], ['auth']);
Core\Router::post('/admin/comments/{id}/delete', [App\Controllers\Admin\CommentController::class, 'delete'], ['auth']);

Core\Router::get('/admin/messages', [App\Controllers\Admin\MessageController::class, 'index'], ['auth']);
Core\Router::post('/admin/messages/{id}/read', [App\Controllers\Admin\MessageController::class, 'markRead'], ['auth']);
Core\Router::post('/admin/messages/{id}/delete', [App\Controllers\Admin\MessageController::class, 'delete'], ['auth']);

// Tema ve Eklenti Yönetimi
Core\Router::get('/admin/themes', [App\Controllers\Admin\ThemeController::class, 'index'], ['auth']);
Core\Router::post('/admin/themes/activate', [App\Controllers\Admin\ThemeController::class, 'activate'], ['auth']);
Core\Router::post('/admin/themes/upload', [App\Controllers\Admin\ThemeController::class, 'upload'], ['auth']);
Core\Router::post('/admin/themes/delete', [App\Controllers\Admin\ThemeController::class, 'delete'], ['auth']);

Core\Router::get('/admin/plugins', [App\Controllers\Admin\PluginController::class, 'index'], ['auth']);
Core\Router::post('/admin/plugins/toggle', [App\Controllers\Admin\PluginController::class, 'toggle'], ['auth']);
Core\Router::post('/admin/plugins/clear-quarantine', [App\Controllers\Admin\PluginController::class, 'clearQuarantine'], ['auth']);
Core\Router::post('/admin/plugins/upload', [App\Controllers\Admin\PluginController::class, 'upload'], ['auth']);
Core\Router::post('/admin/plugins/delete', [App\Controllers\Admin\PluginController::class, 'delete'], ['auth']);

// Ayarlar
Core\Router::get('/admin/settings', [App\Controllers\Admin\SettingsController::class, 'index'], ['auth']);
Core\Router::post('/admin/settings', [App\Controllers\Admin\SettingsController::class, 'update'], ['auth']);
Core\Router::get('/admin/updates/check', [App\Controllers\Admin\SettingsController::class, 'checkUpdates'], ['auth']);
Core\Router::post('/admin/updates/check', [App\Controllers\Admin\SettingsController::class, 'checkUpdates'], ['auth']);

// 6. Dinamik Kalıcı Bağlantı (Permalink) Rotaları
// Tüm admin ve modül rotalarından sonra en son eşleşecek şekilde kaydedilir
Core\Router::get('/blog/{category}/{slug}', [App\Controllers\BlogController::class, 'show']);
Core\Router::post('/blog/{category}/{slug}/comment', [App\Controllers\BlogController::class, 'storeComment']);
Core\Router::get('/{category}/{slug}', [App\Controllers\BlogController::class, 'show']);
Core\Router::post('/{category}/{slug}/comment', [App\Controllers\BlogController::class, 'storeComment']);

if (setting('permalink_structure', 'default') === 'post_only') {
    Core\Router::get('/{slug}', [App\Controllers\BlogController::class, 'show']);
    Core\Router::post('/{slug}/comment', [App\Controllers\BlogController::class, 'storeComment']);
}

Core\Router::dispatch();
