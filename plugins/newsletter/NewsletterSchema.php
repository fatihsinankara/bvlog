<?php

namespace Plugins\Newsletter;

use Core\Database;

class NewsletterSchema
{
    private static bool $checked = false;

    public static function ensureTables(): void
    {
        if (self::$checked) {
            return;
        }
        self::$checked = true;

        try {
            $pdo = Database::getInstance();

            $pdo->exec("CREATE TABLE IF NOT EXISTS `newsletter_subscribers` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `email` VARCHAR(190) NOT NULL UNIQUE,
                `name` VARCHAR(120) NULL,
                `status` ENUM('active', 'pending', 'unsubscribed') DEFAULT 'active',
                `verify_token` VARCHAR(64) NOT NULL,
                `unsubscribe_token` VARCHAR(64) NOT NULL,
                `consent_approved` TINYINT(1) DEFAULT 1,
                `consent_at` DATETIME NULL,
                `consent_ip` VARCHAR(45) NULL,
                `consent_text_hash` VARCHAR(64) NULL,
                `verified_at` DATETIME NULL,
                `verified_ip` VARCHAR(45) NULL,
                `unsubscribed_at` DATETIME NULL,
                `unsubscribed_ip` VARCHAR(45) NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                KEY `idx_newsletter_status` (`status`),
                KEY `idx_newsletter_unsub_token` (`unsubscribe_token`),
                KEY `idx_newsletter_verify_token` (`verify_token`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            $pdo->exec("CREATE TABLE IF NOT EXISTS `newsletter_campaigns` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `subject` VARCHAR(255) NOT NULL,
                `content_html` LONGTEXT NOT NULL,
                `recipient_scope` VARCHAR(50) DEFAULT 'all_active',
                `total_recipients` INT DEFAULT 0,
                `sent_count` INT DEFAULT 0,
                `failed_count` INT DEFAULT 0,
                `last_error` TEXT NULL,
                `sent_at` DATETIME NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        } catch (\Throwable $e) {
            // Kurulum öncesi veya geçici DB hatasında sessizce geç
        }
    }

    public static function getDefaultConfig(): array
    {
        $siteTitle = setting('site_title', 'Bvlog');

        return [
            'title'             => setting('newsletter_title', 'Yeni Yazıları Kaçırmayın'),
            'description'       => setting('newsletter_description', 'Yeni makaleler, yazılım notları ve proje güncellemeleri yayınlandığında doğrudan e-posta kutunuza gelsin. Spam yok, dilediğiniz an tek tıkla ayrılabilirsiniz.'),
            'button_text'       => setting('newsletter_button_text', 'Bültene Abone Ol'),
            'placeholder_email' => setting('newsletter_placeholder_email', 'ornek@eposta.com'),
            'show_name_field'   => setting('newsletter_show_name', '0') === '1',
            'double_opt_in'     => setting('newsletter_double_opt_in', '0') === '1',
            'send_welcome'      => setting('newsletter_send_welcome', '1') === '1',
            'auto_notify_post'  => setting('newsletter_auto_notify_post', '0') === '1',
            'consent_label'     => setting('newsletter_consent_label', '6698 sayılı KVKK Aydınlatma Metni\'ni okudum; e-posta adresimin bülten gönderimi amacıyla işlenmesine ve 6563 sayılı ETK kapsamında tarafıma elektronik ileti gönderilmesine açık rıza veriyorum.'),
            'legal_modal_title' => setting('newsletter_legal_modal_title', 'KVKK Aydınlatma Metni ve Elektronik İleti Açık Rıza Beyanı'),
            'legal_modal_body'  => setting('newsletter_legal_modal_body', self::getDefaultLegalText($siteTitle)),
        ];
    }

    public static function getDefaultLegalText(string $siteTitle = 'Bvlog'): string
    {
        return <<<TEXT
1. VERİ SORUMLUSU VE İŞLEME AMACI (6698 SAYILI KVKK KAPSAMINDA AYDINLATMA)
{$siteTitle} ("Veri Sorumlusu") olarak, bülten abonelik formu aracılığıyla paylaştığınız e-posta adresiniz ve (varsa) ad-soyad bilginiz ile onay zaman damgası ve IP adresi kayıtlarınız; 6698 sayılı Kişisel Verilerin Korunması Kanunu ("KVKK") Madde 5/1 kapsamında açık rızanıza dayanarak sadece yeni blog yazıları, teknik içerikler ve site duyurularının tarafınıza iletilmesi ve yasal onay ispat yükümlülüğünün yerine getirilmesi amacıyla işlenmektedir.

2. KİŞİSEL VERİLERİN AKTARILMASI VE SAKLANMASI
Toplanan kişisel verileriniz üçüncü kişi veya kurumlara ticari amaçla satılmaz, kiralanmaz veya paylaşılmaz; yalnızca e-posta gönderim altyapısının (SMTP sunucusu) teknik olarak çalıştırılması amacıyla sınırlı olarak kullanılır. Verileriniz, abonelikten ayrılma talebinize kadar güvenli veritabanında muhafaza edilir.

3. TİCARİ ELEKTRONİK İLETİ ONAYI VE İPTAL HAKKI (6563 SAYILI ETK)
6563 sayılı Elektronik Ticaretin Düzenlenmesi Hakkında Kanun ve ilgili mevzuat uyarınca, bülten kutusundaki onay kutucuğunu işaretleyerek {$siteTitle} tarafından e-posta adresinize bilgilendirme ve bülten iletileri gönderilmesine onay vermiş olursunuz. Gönderilen her e-postanın alt kısmında yer alan "Abonelikten Çık (İzni İptal Et)" bağlantısına tıklayarak hiçbir gerekçe göstermeksizin ve ücretsiz olarak izninizi dilediğiniz an geri alabilirsiniz.

4. KVKK MADDE 11 KAPSAMINDAKİ HAKLARINIZ
KVKK'nın 11. maddesi uyarınca veri sorumlusuna başvurarak; kişisel verilerinizin işlenip işlenmediğini öğrenme, düzeltilmesini veya silinmesini talep etme haklarına sahipsiniz.
TEXT;
    }
}
