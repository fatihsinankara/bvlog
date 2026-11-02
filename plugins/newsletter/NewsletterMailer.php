<?php

namespace Plugins\Newsletter;

use Core\PluginManager;
use Plugins\Smtp\SmtpClient;

class NewsletterMailer
{
    public static function isSmtpPluginActive(): bool
    {
        return class_exists(PluginManager::class) && PluginManager::isActive('smtp');
    }

    private static function ensureSmtpClass(): void
    {
        if (!self::isSmtpPluginActive()) {
            return;
        }
        if (!class_exists(SmtpClient::class)) {
            $smtpFile = BVLOG_ROOT . '/plugins/smtp/SmtpClient.php';
            if (is_file($smtpFile)) {
                require_once $smtpFile;
            }
        }
    }

    public static function getSmtpStatus(): array
    {
        $pluginActive = self::isSmtpPluginActive();

        if ($pluginActive) {
            self::ensureSmtpClass();
            if (class_exists(SmtpClient::class)) {
                $cfg = SmtpClient::getDefaultConfig();
                $smtpEnabled = !empty($cfg['enabled']);
                $hasHost = !empty($cfg['host']);
                $smtpReady = $smtpEnabled && $hasHost;

                $reason = '';
                if (!$smtpEnabled) {
                    $reason = 'SMTP eklentisi etkin ancak eklenti ayarlarından SMTP gönderimi kapatılmış.';
                } elseif (!$hasHost) {
                    $reason = 'SMTP eklentisi etkin ancak sunucu (Host) bilgileri henüz girilmemiş.';
                }

                return [
                    'plugin_active' => true,
                    'available'     => true,
                    'smtp_enabled'  => $smtpEnabled,
                    'smtp_ready'    => $smtpReady,
                    'reason'        => $reason,
                    'host'          => $cfg['host'] ?? '',
                    'port'          => $cfg['port'] ?? 587,
                    'encryption'    => $cfg['encryption'] ?? 'tls',
                    'from_email'    => $cfg['from_email'] ?? '',
                    'from_name'     => $cfg['from_name'] ?? setting('site_title', 'Bvlog'),
                ];
            }
        }

        return [
            'plugin_active' => false,
            'available'     => false,
            'smtp_enabled'  => false,
            'smtp_ready'    => false,
            'reason'        => 'SMTP eklentisi devre dışı bırakılmış durumda.',
            'host'          => '',
            'port'          => 587,
            'encryption'    => 'tls',
            'from_email'    => setting('social_email', 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost')),
            'from_name'     => setting('site_title', 'Bvlog'),
        ];
    }

    /**
     * Tekil alıcıya e-posta gönderir ve hata detayını döndürür.
     *
     * @return array ['success' => bool, 'error' => string]
     */
    public static function sendSingle(string $toEmail, string $subject, string $htmlBody, array $options = []): array
    {
        if (self::isSmtpPluginActive()) {
            self::ensureSmtpClass();

            if (class_exists(SmtpClient::class)) {
                $client = new SmtpClient();
                $ok = $client->send($toEmail, $subject, $htmlBody, $options);
                return [
                    'success' => $ok,
                    'error'   => $ok ? '' : ($client->getLastError() ?: 'E-posta gönderilemedi.'),
                ];
            }
        }

        if (function_exists('bvlog_mail')) {
            $ok = bvlog_mail($toEmail, $subject, $htmlBody, $options);
            return [
                'success' => $ok,
                'error'   => $ok ? '' : 'SMTP eklentisi devre dışı olduğu için yerel PHP mail() denendi ancak başarısız oldu.',
            ];
        }

        return [
            'success' => false,
            'error'   => 'SMTP eklentisi devre dışı ve yerel e-posta göndericisi bulunamadı.',
        ];
    }

    /**
     * Bülten gövdesini yasal zorunlu altbilgi (KVKK/ETK açıklaması + Tek Tıkla Abonelikten Çıkış linki) ile sarmalar.
     */
    public static function wrapNewsletterHtml(
        string $subject,
        string $bodyHtml,
        array $subscriber
    ): string {
        $siteTitle = htmlspecialchars(setting('site_title', 'Bvlog'), ENT_QUOTES, 'UTF-8');
        $siteUrl = htmlspecialchars(base_url(), ENT_QUOTES, 'UTF-8');
        $subEmail = htmlspecialchars($subscriber['email'] ?? '', ENT_QUOTES, 'UTF-8');
        $subName = trim((string) ($subscriber['name'] ?? ''));
        $consentDate = !empty($subscriber['consent_at'])
            ? date('d.m.Y H:i', strtotime((string) $subscriber['consent_at']))
            : date('d.m.Y');

        $token = urlencode((string) ($subscriber['unsubscribe_token'] ?? ''));
        $unsubUrl = htmlspecialchars(rtrim(base_url(), '/') . url('/newsletter/unsubscribe?token=' . $token), ENT_QUOTES, 'UTF-8');

        $greeting = $subName !== ''
            ? '<p style="margin: 0 0 16px; font-size: 15px; color: #18181b;">Merhaba <strong>' . htmlspecialchars($subName, ENT_QUOTES, 'UTF-8') . '</strong>,</p>'
            : '';

        // Eğer içerik düz metinse satır sonlarını koru, HTML etiketleri varsa olduğu gibi bırak
        $hasHtmlTags = ($bodyHtml !== strip_tags($bodyHtml));
        $formattedBody = $hasHtmlTags ? $bodyHtml : nl2br(htmlspecialchars($bodyHtml, ENT_QUOTES, 'UTF-8'));

        $escapedSubject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$escapedSubject}</title>
</head>
<body style="margin: 0; padding: 24px 12px; background-color: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #18181b;">
    <div style="max-width: 620px; margin: 0 auto; background-color: #ffffff; border: 1px solid #e4e4e7; border-radius: 14px; overflow: hidden;">
        <!-- Üst Başlık -->
        <div style="padding: 20px 28px; border-bottom: 1px solid #f4f4f5; background-color: #fafafa;">
            <a href="{$siteUrl}" style="text-decoration: none; color: #18181b; font-size: 17px; font-weight: 800; letter-spacing: -0.02em;">
                {$siteTitle}
            </a>
        </div>

        <!-- Ana İçerik -->
        <div style="padding: 28px; font-size: 15px; line-height: 1.75; color: #27272a;">
            {$greeting}
            <div>{$formattedBody}</div>
        </div>

        <!-- Yasal Zorunlu Altbilgi (6563 Sayılı ETK & 6698 Sayılı KVKK) -->
        <div style="padding: 20px 28px; background-color: #fafafa; border-top: 1px solid #e4e4e7; font-size: 12px; line-height: 1.6; color: #71717a;">
            <p style="margin: 0 0 8px;">
                <strong>Yasal Bilgilendirme:</strong> Bu e-posta, <strong>{$subEmail}</strong> adresiyle {$consentDate} tarihinde <strong>{$siteTitle}</strong> e-posta bültenine 6698 sayılı KVKK ve 6563 sayılı Elektronik Ticaretin Düzenlenmesi Hakkında Kanun kapsamında verdiğiniz açık rıza doğrultusunda gönderilmiştir.
            </p>
            <p style="margin: 0;">
                Artık bülten e-postaları almak istemiyorsanız hiçbir gerekçe göstermeksizin ve ücretsiz olarak
                <a href="{$unsubUrl}" style="color: #dc2626; font-weight: 700; text-decoration: underline;">buraya tıklayarak tek tıkla abonelikten çıkabilirsiniz (İzni İptal Et)</a>.
            </p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Çift Aşamalı Doğrulama (Double Opt-In) onay e-postası gönderir.
     */
    public static function sendVerificationEmail(array $subscriber): array
    {
        $siteTitle = setting('site_title', 'Bvlog');
        $token = urlencode((string) ($subscriber['verify_token'] ?? ''));
        $verifyUrl = htmlspecialchars(rtrim(base_url(), '/') . url('/newsletter/verify?token=' . $token), ENT_QUOTES, 'UTF-8');

        $subject = "{$siteTitle} — Bülten Aboneliğinizi Doğrulayın";
        $body = <<<HTML
<h2 style="margin: 0 0 12px; font-size: 19px; color: #18181b;">Bülten Aboneliğinizi Onaylayın</h2>
<p style="margin: 0 0 18px;">
    <strong>{$siteTitle}</strong> e-posta bültenine kayıt talebinizi aldık. E-posta adresinizin size ait olduğunu doğrulamak ve aboneliğinizi başlatmak için lütfen aşağıdaki butona tıklayın:
</p>
<div style="margin: 24px 0;">
    <a href="{$verifyUrl}" style="display: inline-block; padding: 12px 24px; background-color: #18181b; color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 700; border-radius: 8px;">
        ✓ E-posta Adresimi Doğrula ve Aboneliği Başlat
    </a>
</div>
<p style="margin: 0; font-size: 12px; color: #71717a;">
    Eğer bu talebi siz oluşturmadıysanız bu e-postayı dikkate almayabilirsiniz; onaylanmayan kayıtlar aktif edilmez.
</p>
HTML;

        $unsubToken = urlencode((string) ($subscriber['unsubscribe_token'] ?? ''));
        $unsubFullUrl = rtrim(base_url(), '/') . url('/newsletter/unsubscribe?token=' . $unsubToken);

        return self::sendSingle(
            (string) $subscriber['email'],
            $subject,
            self::wrapNewsletterHtml($subject, $body, $subscriber),
            ['unsubscribe_url' => $unsubFullUrl]
        );
    }

    /**
     * Yeni aktif aboneye Hoş Geldiniz e-postası gönderir.
     */
    public static function sendWelcomeEmail(array $subscriber): array
    {
        $siteTitle = setting('site_title', 'Bvlog');
        $siteUrl = htmlspecialchars(base_url(), ENT_QUOTES, 'UTF-8');

        $subject = "{$siteTitle} Bültenine Hoş Geldiniz!";
        $body = <<<HTML
<h2 style="margin: 0 0 12px; font-size: 19px; color: #18181b;">Aramıza Hoş Geldiniz! 🎉</h2>
<p style="margin: 0 0 16px;">
    <strong>{$siteTitle}</strong> e-posta bülteni aboneliğiniz başarıyla aktif edildi. Bundan böyle yeni yayınlanan makaleler, projeler ve önemli duyurular doğrudan e-posta kutunuza iletilecek.
</p>
<div style="margin: 22px 0;">
    <a href="{$siteUrl}" style="display: inline-block; padding: 10px 20px; background-color: #18181b; color: #ffffff; text-decoration: none; font-size: 13px; font-weight: 700; border-radius: 8px;">
        Son Yazılara Göz At &rarr;
    </a>
</div>
HTML;

        $unsubToken = urlencode((string) ($subscriber['unsubscribe_token'] ?? ''));
        $unsubFullUrl = rtrim(base_url(), '/') . url('/newsletter/unsubscribe?token=' . $unsubToken);

        return self::sendSingle(
            (string) $subscriber['email'],
            $subject,
            self::wrapNewsletterHtml($subject, $body, $subscriber),
            ['unsubscribe_url' => $unsubFullUrl]
        );
    }
}
