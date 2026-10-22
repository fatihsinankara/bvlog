<?php

use Core\Hook;
use Core\Request;
use Core\Router;
use Plugins\Smtp\Controllers\SmtpAdminController;
use Plugins\Smtp\SmtpClient;

// 1. Eklenti İçi Sınıf Otomatik Yükleyicisi
spl_autoload_register(function (string $class) {
    $prefix = 'Plugins\\Smtp\\';
    if (str_starts_with($class, $prefix)) {
        $relativeClass = substr($class, strlen($prefix));
        $file = __DIR__ . '/' . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

// 2. Sistem Geneli Global E-posta Gönderim Yardımcısı (bvlog_mail)
if (!function_exists('bvlog_mail')) {
    /**
     * Sistem geneli e-posta gönderir. SMTP aktifse SMTP soketi üzerinden;
     * değilse yerel mail() fonksiyonu ile iletir.
     *
     * @param string|array $to
     * @param string $subject
     * @param string $htmlBody
     * @param array $options ['reply_to' => '', 'from_email' => '', 'from_name' => '']
     * @return bool
     */
    function bvlog_mail(string|array $to, string $subject, string $htmlBody, array $options = []): bool
    {
        $client = new SmtpClient();
        return $client->send($to, $subject, $htmlBody, $options);
    }
}

// 3. Rotalar — Yönetim Paneli
Router::get('/admin/smtp', [SmtpAdminController::class, 'index'], ['auth']);
Router::post('/admin/smtp/save', [SmtpAdminController::class, 'save'], ['auth']);
Router::post('/admin/smtp/test', [SmtpAdminController::class, 'test'], ['auth']);

// 4. Kanca: Admin Yan Menüsüne (Görünüm & Sistem) "SMTP E-posta" Linki Ekleme
Hook::addAction('admin_sidebar_system_menu', function () {
    $currentPath = Request::path();
    $isActive = str_starts_with($currentPath, '/admin/smtp');
    $linkClass = $isActive 
        ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' 
        : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800';

    $url = e(url('/admin/smtp'));
    echo <<<HTML
    <a href="{$url}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {$linkClass}">
        <i class="fa-solid fa-envelope-open-text w-4 text-center"></i>
        <span>SMTP E-posta</span>
    </a>
HTML;
});

// 5. Kanca: İletişim Formundan Yeni Mesaj Geldiğinde Yöneticiye Bildirim E-postası Gönderme
Hook::addAction('contact_message_received', function ($messageData) {
    if (setting('smtp_notify_contact', '0') !== '1') {
        return;
    }

    $adminEmail = trim((string) setting('smtp_admin_email', setting('social_email', '')));
    if ($adminEmail === '' || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        return;
    }

    $siteTitle = setting('site_title', 'Bvlog');
    $senderName = htmlspecialchars($messageData['name'] ?? 'Ziyaretçi', ENT_QUOTES, 'UTF-8');
    $senderEmail = htmlspecialchars($messageData['email'] ?? '', ENT_QUOTES, 'UTF-8');
    $subject = htmlspecialchars($messageData['subject'] ?? 'Yeni İletişim Mesajı', ENT_QUOTES, 'UTF-8');
    $content = nl2br(htmlspecialchars($messageData['message'] ?? '', ENT_QUOTES, 'UTF-8'));
    $ip = htmlspecialchars($messageData['ip_address'] ?? '', ENT_QUOTES, 'UTF-8');
    $date = date('d.m.Y H:i');

    $mailSubject = "Yeni İletişim Mesajı: {$subject} — {$siteTitle}";

    $html = <<<HTML
<div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #e4e4e7; border-radius: 12px; background: #ffffff; color: #18181b;">
    <div style="margin-bottom: 20px; border-bottom: 2px solid #3b82f6; padding-bottom: 12px;">
        <h2 style="margin: 0; font-size: 18px; color: #18181b;">{$siteTitle} — Yeni İletişim Formu Mesajı</h2>
        <span style="font-size: 12px; color: #71717a;">Gönderim Tarihi: {$date}</span>
    </div>

    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin-bottom: 20px; font-size: 13px; line-height: 1.6;">
        <div><strong>Gönderen:</strong> {$senderName}</div>
        <div><strong>E-posta:</strong> <a href="mailto:{$senderEmail}" style="color: #2563eb;">{$senderEmail}</a></div>
        <div><strong>Konu:</strong> {$subject}</div>
        <div><strong>IP Adresi:</strong> {$ip}</div>
    </div>

    <div style="margin-bottom: 24px;">
        <div style="font-size: 12px; font-weight: bold; text-transform: uppercase; color: #64748b; margin-bottom: 8px;">Mesaj İçeriği:</div>
        <div style="font-size: 14px; line-height: 1.7; background: #ffffff; padding: 16px; border: 1px solid #e2e8f0; border-radius: 8px;">
            {$content}
        </div>
    </div>

    <div style="text-align: center; border-top: 1px solid #f1f5f9; padding-top: 16px;">
        <a href="mailto:{$senderEmail}?subject=Re: {$subject}" style="display: inline-block; padding: 10px 20px; font-size: 13px; font-weight: 600; color: #ffffff; background: #2563eb; border-radius: 6px; text-decoration: none;">
            Bu Mesajı Yanıtla
        </a>
    </div>
</div>
HTML;

    bvlog_mail($adminEmail, $mailSubject, $html, [
        'reply_to' => $messageData['email'] ?? '',
    ]);
});
