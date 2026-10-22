<?php

use Core\Hook;
use Core\Request;
use Core\Router;
use Plugins\Captcha\CaptchaService;
use Plugins\Captcha\Controllers\CaptchaAdminController;

// 1. Eklenti İçi Sınıf Otomatik Yükleyicisi
spl_autoload_register(function (string $class) {
    $prefix = 'Plugins\\Captcha\\';
    if (str_starts_with($class, $prefix)) {
        $relativeClass = substr($class, strlen($prefix));
        $file = __DIR__ . '/' . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

// 2. Rotalar — Yönetim Paneli
Router::get('/admin/captcha', [CaptchaAdminController::class, 'index'], ['auth']);
Router::post('/admin/captcha/save', [CaptchaAdminController::class, 'save'], ['auth']);
Router::post('/admin/captcha/test', [CaptchaAdminController::class, 'test'], ['auth']);

// 3. Kanca: Admin Yan Menüsüne (Görünüm & Sistem) "CAPTCHA Koruması" Linki Ekleme
Hook::addAction('admin_sidebar_system_menu', function () {
    $currentPath = Request::path();
    $isActive = str_starts_with($currentPath, '/admin/captcha');
    $linkClass = $isActive 
        ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' 
        : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800';

    $url = e(url('/admin/captcha'));
    echo <<<HTML
    <a href="{$url}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {$linkClass}">
        <i class="fa-solid fa-shield-halved w-4 text-center"></i>
        <span>CAPTCHA Koruması</span>
    </a>
HTML;
});

// 4. Kanca: Form Alanlarında CAPTCHA Kutusu Render Etme
Hook::addAction('render_captcha', function (string $form) {
    echo CaptchaService::render($form);
});

// 5. Filtre: Backend Form Gönderiminde CAPTCHA Doğrulama
Hook::addFilter('verify_captcha', function (bool $isValid, string $form): bool {
    if (!CaptchaService::isFormEnabled($form)) {
        return true;
    }
    $res = CaptchaService::verify($form);
    return !empty($res['success']);
});
