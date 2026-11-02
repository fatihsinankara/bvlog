<?php

use App\Models\Post;
use App\Models\Setting;
use Core\Hook;
use Core\Request;
use Core\Router;
use Core\View;
use Plugins\Newsletter\Controllers\NewsletterAdminController;
use Plugins\Newsletter\Controllers\NewsletterPublicController;
use Plugins\Newsletter\Models\Campaign;
use Plugins\Newsletter\Models\Subscriber;
use Plugins\Newsletter\NewsletterMailer;
use Plugins\Newsletter\NewsletterSchema;

// 1. Eklenti İçi Sınıf Otomatik Yükleyicisi (Autoloader)
spl_autoload_register(function (string $class) {
    $prefix = 'Plugins\\Newsletter\\';
    if (str_starts_with($class, $prefix)) {
        $relativeClass = substr($class, strlen($prefix));
        $file = __DIR__ . '/' . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

// 2. Veritabanı Tablolarını Güvenceye Al
NewsletterSchema::ensureTables();

// 3. Rotalar — Yönetim Paneli
Router::get('/admin/newsletter', [NewsletterAdminController::class, 'index'], ['auth']);
Router::get('/admin/newsletter/subscribers', [NewsletterAdminController::class, 'subscribers'], ['auth']);
Router::get('/admin/newsletter/subscribers/export', [NewsletterAdminController::class, 'exportCsv'], ['auth']);
Router::post('/admin/newsletter/subscribers/add', [NewsletterAdminController::class, 'addSubscriber'], ['auth']);
Router::post('/admin/newsletter/subscribers/{id}/status', [NewsletterAdminController::class, 'toggleStatus'], ['auth']);
Router::post('/admin/newsletter/subscribers/{id}/delete', [NewsletterAdminController::class, 'deleteSubscriber'], ['auth']);
Router::get('/admin/newsletter/campaigns', [NewsletterAdminController::class, 'campaigns'], ['auth']);
Router::post('/admin/newsletter/campaigns/{id}/delete', [NewsletterAdminController::class, 'deleteCampaign'], ['auth']);
Router::get('/admin/newsletter/settings', [NewsletterAdminController::class, 'settings'], ['auth']);
Router::post('/admin/newsletter/settings', [NewsletterAdminController::class, 'saveSettings'], ['auth']);
Router::post('/admin/newsletter/send', [NewsletterAdminController::class, 'send'], ['auth']);

// 4. Rotalar — Ön Yüz (Public)
Router::post('/newsletter/subscribe', [NewsletterPublicController::class, 'subscribe']);
Router::get('/newsletter/verify', [NewsletterPublicController::class, 'verify']);
Router::get('/newsletter/unsubscribe', [NewsletterPublicController::class, 'unsubscribe']);
Router::post('/newsletter/unsubscribe', [NewsletterPublicController::class, 'unsubscribe']);

// 5. Kanca: Admin Yan Menüsüne "E-posta Bülteni" Linki Ekleme
Hook::addAction('admin_sidebar_menu', function () {
    $currentPath = Request::path();
    $isActive = str_starts_with($currentPath, '/admin/newsletter');
    $linkClass = $isActive
        ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900'
        : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800';
    $activeAttr = $isActive ? 'data-sidebar-active="true"' : '';

    $stats = Subscriber::getStats();
    $activeCount = (int) ($stats['active'] ?? 0);
    $badgeHtml = $activeCount > 0
        ? '<span class="text-[11px] font-bold px-2 py-0.5 rounded bg-emerald-600 text-white">' . $activeCount . '</span>'
        : '';

    $url = e(url('/admin/newsletter'));
    echo <<<HTML
    <a href="{$url}" {$activeAttr} class="flex items-center justify-between px-3 py-2 rounded-lg font-medium transition-colors {$linkClass}">
        <span class="flex items-center gap-3">
            <i class="fa-regular fa-paper-plane w-4 text-center"></i>
            <span>E-posta Bülteni</span>
        </span>
        {$badgeHtml}
    </a>
HTML;
});

// 6. Kanca: Ön Yüzde Footer Üstüne Bülten Abonelik Kutusunu Basma
Hook::addAction('before_footer', function () {
    echo View::renderPartial('plugins/newsletter/views/components/footer-box');
});

// 7. Kanca: Yeni Blog Yazısı Yayınlandığında Otomatik Bülten Bildirimi (Aktifse)
Hook::addAction('post_saved', function ($postId) {
    if (setting('newsletter_auto_notify_post', '0') !== '1') {
        return;
    }

    $post = Post::find((int) $postId);
    if (!$post || ($post['status'] ?? '') !== 'published') {
        return;
    }

    // Aynı yazının düzenlemelerde tekrar gönderilmesini önle
    $notifiedIds = Setting::getJson('newsletter_notified_posts', []);
    if (in_array((int) $postId, $notifiedIds, true)) {
        return;
    }

    $recipients = Subscriber::getActiveSubscribers();
    if (empty($recipients)) {
        return;
    }

    $notifiedIds[] = (int) $postId;
    Setting::setJson('newsletter_notified_posts', array_values(array_slice($notifiedIds, -200)));

    $title = htmlspecialchars((string) $post['title'], ENT_QUOTES, 'UTF-8');
    $summary = htmlspecialchars((string) (($post['summary'] ?? '') ?: excerpt((string) $post['content'], 180)), ENT_QUOTES, 'UTF-8');
    $postLink = htmlspecialchars(rtrim(base_url(), '/') . post_url($post), ENT_QUOTES, 'UTF-8');

    $subject = 'Yeni Yazı: ' . (string) $post['title'];
    $bodyHtml = <<<HTML
<h2 style="margin: 0 0 12px; font-size: 20px; color: #18181b;">{$title}</h2>
<p style="margin: 0 0 18px; color: #3f3f46; line-height: 1.7;">{$summary}</p>
<div style="margin: 24px 0;">
    <a href="{$postLink}" style="display: inline-block; padding: 11px 22px; background-color: #18181b; color: #ffffff; text-decoration: none; font-size: 13px; font-weight: 700; border-radius: 8px;">
        Yazının Devamını Oku &rarr;
    </a>
</div>
HTML;

    $sent = 0;
    $failed = 0;
    $lastErr = null;

    foreach ($recipients as $sub) {
        $unsubToken = urlencode((string) ($sub['unsubscribe_token'] ?? ''));
        $unsubFullUrl = rtrim(base_url(), '/') . url('/newsletter/unsubscribe?token=' . $unsubToken);
        $wrapped = NewsletterMailer::wrapNewsletterHtml($subject, $bodyHtml, $sub);
        $res = NewsletterMailer::sendSingle((string) $sub['email'], $subject, $wrapped, [
            'unsubscribe_url' => $unsubFullUrl,
        ]);
        if ($res['success']) {
            $sent++;
        } else {
            $failed++;
            $lastErr = $res['error'];
        }
    }

    Campaign::create([
        'subject'          => $subject,
        'content_html'     => $bodyHtml,
        'recipient_scope'  => 'auto_post',
        'total_recipients' => count($recipients),
        'sent_count'       => $sent,
        'failed_count'     => $failed,
        'last_error'       => $lastErr,
        'sent_at'          => date('Y-m-d H:i:s'),
    ]);
});
