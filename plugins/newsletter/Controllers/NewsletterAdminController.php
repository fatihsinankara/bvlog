<?php

namespace Plugins\Newsletter\Controllers;

use App\Models\Post;
use App\Models\Setting;
use Core\Controller;
use Core\Database;
use Core\Request;
use Core\Session;
use Core\View;
use Plugins\Newsletter\Models\Campaign;
use Plugins\Newsletter\Models\Subscriber;
use Plugins\Newsletter\NewsletterMailer;
use Plugins\Newsletter\NewsletterSchema;

class NewsletterAdminController extends Controller
{
    public function index(): void
    {
        $this->renderSection('compose', 'E-posta Bülteni — Bülten Gönder');
    }

    public function subscribers(): void
    {
        $this->renderSection('subscribers', 'E-posta Bülteni — Aboneler & İzin Kayıtları');
    }

    public function campaigns(): void
    {
        $this->renderSection('campaigns', 'E-posta Bülteni — Gönderim Geçmişi');
    }

    public function settings(): void
    {
        $this->renderSection('settings', 'E-posta Bülteni — Ayarlar & Yasal Metinler');
    }

    private function renderSection(string $section, string $pageTitle): void
    {
        NewsletterSchema::ensureTables(true);

        $statusFilter = (string) Request::get('status', '');
        $search = (string) Request::get('q', '');

        $stats = Subscriber::getStats();
        $smtpStatus = NewsletterMailer::getSmtpStatus();
        $config = NewsletterSchema::getDefaultConfig();

        $subscribers = ($section === 'subscribers' || $section === 'compose')
            ? Subscriber::listSubscribers($statusFilter, $search)
            : [];

        $campaigns = ($section === 'campaigns' || $section === 'compose')
            ? Campaign::all(30)
            : [];

        // Hızlı ve toplu şablon doldurma için yayınlanan blog yazıları (son 60 yazı)
        $recentPosts = [];
        if ($section === 'compose') {
            $rows = Database::fetchAll(
                "SELECT p.id, p.title, p.slug, p.category_id, p.summary, p.content, p.published_at, p.created_at,
                        c.name AS category_name
                 FROM posts p
                 LEFT JOIN categories c ON c.id = p.category_id
                 WHERE p.status = 'published'
                 ORDER BY COALESCE(p.published_at, p.created_at) DESC, p.id DESC
                 LIMIT 60"
            );
            foreach ($rows as $r) {
                $rawDate = (string) ($r['published_at'] ?: $r['created_at']);
                $ts = $rawDate !== '' ? (int) strtotime($rawDate) : time();
                $recentPosts[] = [
                    'id'           => (int) $r['id'],
                    'title'        => (string) $r['title'],
                    'category'     => (string) ($r['category_name'] ?? 'Genel'),
                    'excerpt'      => (string) (($r['summary'] ?? '') ?: excerpt((string) $r['content'], 180)),
                    'url'          => rtrim(base_url(), '/') . post_url($r),
                    'published_ts' => $ts,
                    'date_label'   => format_date($rawDate),
                ];
            }
        }

        $this->adminView('plugins/newsletter/views/admin/index', [
            'section'      => $section,
            'stats'        => $stats,
            'smtpStatus'   => $smtpStatus,
            'config'       => $config,
            'subscribers'  => $subscribers,
            'campaigns'    => $campaigns,
            'recentPosts'  => $recentPosts,
            'statusFilter' => $statusFilter,
            'search'       => $search,
            'pageTitle'    => $pageTitle,
        ]);
    }

    public function send(): void
    {
        $subject = trim((string) Request::post('subject', ''));
        $contentHtml = trim((string) ($_POST['content_html'] ?? ''));
        $sendMode = (string) Request::post('send_mode', 'all_active'); // 'test', 'all_active', 'selected'

        if ($subject === '' || $contentHtml === '') {
            Session::flash('error', 'Lütfen bülten konusunu ve e-posta içeriğini doldurun.');
            $this->redirect('/admin/newsletter');
        }

        // 1. Test Gönderimi Modu
        if ($sendMode === 'test') {
            $testEmail = trim((string) Request::post('test_email', ''));
            if ($testEmail === '' || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
                Session::flash('error', 'Test gönderimi için lütfen geçerli bir alıcı e-posta adresi girin.');
                $this->redirect('/admin/newsletter');
            }

            $dummySubscriber = [
                'email'             => $testEmail,
                'name'              => Session::get('admin_user_name', 'Test Okuyucu'),
                'consent_at'        => date('Y-m-d H:i:s'),
                'unsubscribe_token' => 'test-preview-token',
            ];

            $wrappedHtml = NewsletterMailer::wrapNewsletterHtml('[TEST] ' . $subject, $contentHtml, $dummySubscriber);
            $res = NewsletterMailer::sendSingle($testEmail, '[TEST] ' . $subject, $wrappedHtml);

            if ($res['success']) {
                Session::flash('success', "Test bülteni {$testEmail} adresine başarıyla gönderildi!");
            } else {
                Session::flash('error', 'Test e-postası gönderilemedi: ' . $res['error']);
            }

            $this->redirect('/admin/newsletter');
        }

        // 2. Gerçek Abonelere Gönderim
        $selectedIds = isset($_POST['subscriber_ids']) && is_array($_POST['subscriber_ids'])
            ? $_POST['subscriber_ids']
            : [];

        $recipients = ($sendMode === 'selected')
            ? Subscriber::getActiveSubscribers($selectedIds)
            : Subscriber::getActiveSubscribers(null);

        if (empty($recipients)) {
            Session::flash('error', 'Gönderim yapılacak aktif abone bulunamadı.');
            $this->redirect('/admin/newsletter');
        }

        @set_time_limit(300);

        $sentCount = 0;
        $failedCount = 0;
        $lastError = null;

        foreach ($recipients as $sub) {
            $unsubToken = urlencode((string) ($sub['unsubscribe_token'] ?? ''));
            $unsubFullUrl = rtrim(base_url(), '/') . url('/newsletter/unsubscribe?token=' . $unsubToken);

            $wrapped = NewsletterMailer::wrapNewsletterHtml($subject, $contentHtml, $sub);
            $res = NewsletterMailer::sendSingle((string) $sub['email'], $subject, $wrapped, [
                'unsubscribe_url' => $unsubFullUrl,
            ]);

            if ($res['success']) {
                $sentCount++;
            } else {
                $failedCount++;
                $lastError = $res['error'];
            }
        }

        Campaign::create([
            'subject'          => $subject,
            'content_html'     => $contentHtml,
            'recipient_scope'  => $sendMode,
            'total_recipients' => count($recipients),
            'sent_count'       => $sentCount,
            'failed_count'     => $failedCount,
            'last_error'       => $lastError,
            'sent_at'          => date('Y-m-d H:i:s'),
        ]);

        if ($sentCount > 0 && $failedCount === 0) {
            Session::flash('success', "Bülten toplam {$sentCount} aktif aboneye başarıyla gönderildi!");
        } elseif ($sentCount > 0) {
            Session::flash('success', "Bülten {$sentCount} aboneye iletildi ({$failedCount} başarısız: {$lastError}).");
        } else {
            Session::flash('error', 'Bülten gönderimi başarısız oldu: ' . ($lastError ?: 'SMTP ayarlarınızı kontrol edin.'));
        }

        $this->redirect('/admin/newsletter/campaigns');
    }

    public function addSubscriber(): void
    {
        $email = strtolower(trim((string) Request::post('email', '')));
        $name = trim((string) Request::post('name', ''));
        $hasConsent = Request::post('admin_consent_confirm') === '1';

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Lütfen geçerli bir e-posta adresi girin.');
            $this->redirect('/admin/newsletter/subscribers');
        }

        if (!$hasConsent) {
            Session::flash('error', 'Manuel abone eklerken kişinin açık rızasının alındığını onaylamanız gereklidir.');
            $this->redirect('/admin/newsletter/subscribers');
        }

        $config = NewsletterSchema::getDefaultConfig();
        $consentHash = hash('sha256', (string) ($config['legal_modal_body'] ?? ''));

        Subscriber::subscribeOrUpdate(
            $email,
            $name !== '' ? $name : null,
            'active',
            \Core\Security::getClientIp(),
            $consentHash
        );

        Session::flash('success', "{$email} adresi aktif abone olarak eklendi.");
        $this->redirect('/admin/newsletter/subscribers');
    }

    public function toggleStatus(string $id): void
    {
        $subId = (int) $id;
        $newStatus = (string) Request::post('status', 'active');
        Subscriber::updateStatus($subId, $newStatus);
        Session::flash('success', 'Abone durumu güncellendi.');
        $this->redirect('/admin/newsletter/subscribers');
    }

    public function deleteSubscriber(string $id): void
    {
        Subscriber::delete((int) $id);
        Session::flash('success', 'Abone kaydı silindi.');
        $this->redirect('/admin/newsletter/subscribers');
    }

    public function exportCsv(): void
    {
        $subscribers = Subscriber::listSubscribers();

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="bvlog-aboneler-' . date('Y-m-d') . '.csv"');

        $out = fopen('php://output', 'w');
        // UTF-8 BOM (Excel uyumluluğu için)
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['ID', 'E-posta', 'Ad Soyad', 'Durum', 'KVKK Onay Tarihi', 'Onay IP', 'Dogrulama Tarihi', 'Ayrilma Tarihi', 'Kayit Tarihi']);

        foreach ($subscribers as $s) {
            fputcsv($out, [
                $s['id'],
                $s['email'],
                $s['name'] ?? '',
                $s['status'],
                $s['consent_at'] ?? '',
                $s['consent_ip'] ?? '',
                $s['verified_at'] ?? '',
                $s['unsubscribed_at'] ?? '',
                $s['created_at'] ?? '',
            ]);
        }

        fclose($out);
        exit;
    }

    public function deleteCampaign(string $id): void
    {
        Campaign::delete((int) $id);
        Session::flash('success', 'Gönderim geçmişi kaydı silindi.');
        $this->redirect('/admin/newsletter/campaigns');
    }

    public function saveSettings(): void
    {
        Setting::set('newsletter_title', trim((string) Request::post('newsletter_title', 'Yeni Yazıları Kaçırmayın')));
        Setting::set('newsletter_description', trim((string) Request::post('newsletter_description', '')));
        Setting::set('newsletter_button_text', trim((string) Request::post('newsletter_button_text', 'Bültene Abone Ol')));
        Setting::set('newsletter_placeholder_email', trim((string) Request::post('newsletter_placeholder_email', 'ornek@eposta.com')));
        Setting::set('newsletter_show_name', Request::post('newsletter_show_name') ? '1' : '0');
        Setting::set('newsletter_double_opt_in', Request::post('newsletter_double_opt_in') ? '1' : '0');
        Setting::set('newsletter_send_welcome', Request::post('newsletter_send_welcome') ? '1' : '0');
        Setting::set('newsletter_auto_notify_post', Request::post('newsletter_auto_notify_post') ? '1' : '0');
        Setting::set('newsletter_consent_label', trim((string) Request::post('newsletter_consent_label', '')));
        Setting::set('newsletter_legal_modal_title', trim((string) Request::post('newsletter_legal_modal_title', '')));
        Setting::set('newsletter_legal_modal_body', trim((string) ($_POST['newsletter_legal_modal_body'] ?? '')));

        Session::flash('success', 'E-posta bülteni ve KVKK yasal metin ayarları kaydedildi.');
        $this->redirect('/admin/newsletter/settings');
    }
}
