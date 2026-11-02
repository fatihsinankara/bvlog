<?php

namespace Plugins\Newsletter\Controllers;

use Core\Controller;
use Core\RateLimiter;
use Core\Request;
use Core\Response;
use Core\Session;
use Core\View;
use Plugins\Newsletter\Models\Subscriber;
use Plugins\Newsletter\NewsletterMailer;
use Plugins\Newsletter\NewsletterSchema;

class NewsletterPublicController extends Controller
{
    public function subscribe(): void
    {
        $isAjax = Request::isAjax();

        // 1. Honeypot Koruması
        if (!empty(Request::post('website_hp', ''))) {
            $this->respond($isAjax, true, 'Abonelik işleminiz alındı.');
            return;
        }

        // 2. IP Hız Sınırı (10 dakikada en fazla 8 deneme)
        $ip = \Core\Security::getClientIp();
        $rateKey = 'newsletter_subscribe:' . $ip;
        if (RateLimiter::tooManyAttempts($rateKey, 8, 600)) {
            $this->respond($isAjax, false, 'Çok fazla deneme yaptınız. Lütfen birkaç dakika sonra tekrar deneyin.', 429);
            return;
        }
        RateLimiter::hit($rateKey, 600);

        // 3. E-posta Doğrulaması
        $email = strtolower(trim((string) Request::post('email', '')));
        $name = trim((string) Request::post('name', ''));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->respond($isAjax, false, 'Lütfen geçerli bir e-posta adresi girin.', 422);
            return;
        }

        // 4. Yasal Zorunlu KVKK & 6563 Sayılı ETK Açık Rıza Onayı Kontrolü
        $kvkkConsent = (string) Request::post('kvkk_consent', '');
        if ($kvkkConsent !== '1') {
            $this->respond(
                $isAjax,
                false,
                'Bültene abone olabilmek için KVKK Aydınlatma Metni ve Elektronik İleti Açık Rıza onay kutusunu işaretlemeniz yasal olarak zorunludur.',
                422
            );
            return;
        }

        $config = NewsletterSchema::getDefaultConfig();
        $consentHash = hash('sha256', (string) ($config['legal_modal_body'] ?? ''));
        $initialStatus = !empty($config['double_opt_in']) ? 'pending' : 'active';

        $result = Subscriber::subscribeOrUpdate(
            $email,
            $name !== '' ? $name : null,
            $initialStatus,
            $ip,
            $consentHash
        );

        $subscriber = $result['subscriber'];

        if (!empty($result['was_active'])) {
            $this->respond($isAjax, true, 'Bu e-posta adresi zaten bültenimize kayıtlıdır. İlginiz için teşekkür ederiz!');
            return;
        }

        if ($initialStatus === 'pending') {
            NewsletterMailer::sendVerificationEmail($subscriber);
            $this->respond(
                $isAjax,
                true,
                'Kaydınız alındı! Aboneliğinizi tamamlamak için lütfen e-posta adresinize gönderdiğimiz doğrulama bağlantısına tıklayın.'
            );
            return;
        }

        if (!empty($config['send_welcome']) && !empty($subscriber['email'])) {
            NewsletterMailer::sendWelcomeEmail($subscriber);
        }

        $this->respond(
            $isAjax,
            true,
            'Bülten aboneliğiniz başarıyla tamamlandı! Yeni içeriklerde görüşmek üzere.'
        );
    }

    public function verify(): void
    {
        $token = trim((string) Request::get('token', ''));
        $subscriber = Subscriber::findByVerifyToken($token);

        if (!$subscriber) {
            View::render('plugins/newsletter/views/public/status', [
                'pageTitle' => 'Geçersiz Doğrulama Bağlantısı',
                'status'    => 'error',
                'heading'   => 'Doğrulama Bağlantısı Geçersiz',
                'message'   => 'Aradığınız doğrulama anahtarı bulunamadı veya süresi dolmuş olabilir.',
            ]);
            return;
        }

        if (($subscriber['status'] ?? '') !== 'active') {
            Subscriber::markVerified((int) $subscriber['id'], \Core\Security::getClientIp());
            $config = NewsletterSchema::getDefaultConfig();
            if (!empty($config['send_welcome'])) {
                $updated = Subscriber::find((int) $subscriber['id']) ?? $subscriber;
                NewsletterMailer::sendWelcomeEmail($updated);
            }
        }

        View::render('plugins/newsletter/views/public/status', [
            'pageTitle'  => 'Bülten Aboneliğiniz Doğrulandı',
            'status'     => 'verified',
            'heading'    => 'E-posta Adresiniz Doğrulandı!',
            'message'    => htmlspecialchars($subscriber['email'], ENT_QUOTES, 'UTF-8') . ' adresi için bülten aboneliğiniz başarıyla aktif edildi.',
            'subscriber' => $subscriber,
        ]);
    }

    public function unsubscribe(): void
    {
        $token = trim((string) (Request::get('token', '') ?: Request::post('token', '')));
        $subscriber = Subscriber::findByUnsubscribeToken($token);

        if (!$subscriber) {
            View::render('plugins/newsletter/views/public/status', [
                'pageTitle' => 'Abonelikten Çıkış',
                'status'    => 'error',
                'heading'   => 'Geçersiz Abonelik Bağlantısı',
                'message'   => 'Abonelikten çıkış bağlantısı geçersiz veya kaydınız daha önce silinmiş.',
            ]);
            return;
        }

        if (($subscriber['status'] ?? '') !== 'unsubscribed') {
            Subscriber::markUnsubscribed((int) $subscriber['id'], \Core\Security::getClientIp());
        }

        // RFC 8058 One-Click POST isteği ise 200 OK döndür
        if (Request::method() === 'POST' && isset($_POST['List-Unsubscribe'])) {
            http_response_code(200);
            echo 'Unsubscribed';
            exit;
        }

        View::render('plugins/newsletter/views/public/status', [
            'pageTitle'  => 'Abonelikten Ayrıldınız',
            'status'     => 'unsubscribed',
            'heading'    => 'Bülten Aboneliğiniz Sonlandırıldı',
            'message'    => htmlspecialchars($subscriber['email'], ENT_QUOTES, 'UTF-8') . ' adresine yönelik elektronik ileti izniniz iptal edilmiş ve bülten gönderimi durdurulmuştur.',
            'subscriber' => $subscriber,
        ]);
    }

    private function respond(bool $isAjax, bool $success, string $message, int $httpCode = 200): void
    {
        if ($isAjax) {
            Response::json([
                'success' => $success,
                'message' => $message,
            ], $httpCode);
        }

        Session::flash($success ? 'success' : 'error', $message);
        $referer = $_SERVER['HTTP_REFERER'] ?? url('/');
        header('Location: ' . $referer);
        exit;
    }
}
