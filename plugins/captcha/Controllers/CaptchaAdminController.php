<?php

namespace Plugins\Captcha\Controllers;

use App\Models\Setting;
use Core\Controller;
use Core\Request;
use Core\Response;
use Core\Session;
use Plugins\Captcha\CaptchaService;

class CaptchaAdminController extends Controller
{
    public function index(): void
    {
        $config = CaptchaService::getConfig();

        $this->adminView('plugins/captcha/views/admin/index', [
            'config'    => $config,
            'pageTitle' => 'CAPTCHA Güvenlik Koruması & Canlı Test',
        ]);
    }

    public function save(): void
    {
        $provider = Request::post('captcha_provider', CaptchaService::PROVIDER_OFF);
        if (!in_array($provider, [CaptchaService::PROVIDER_OFF, CaptchaService::PROVIDER_CLOUDFLARE, CaptchaService::PROVIDER_RECAPTCHA], true)) {
            $provider = CaptchaService::PROVIDER_OFF;
        }

        $cfSiteKey = trim((string) Request::post('captcha_cf_site_key', ''));
        $cfSecretKey = trim((string) Request::post('captcha_cf_secret_key', ''));

        $rcSiteKey = trim((string) Request::post('captcha_rc_site_key', ''));
        $rcSecretKey = trim((string) Request::post('captcha_rc_secret_key', ''));

        $forms = Request::post('captcha_forms', []);
        if (!is_array($forms)) {
            $forms = [];
        }
        $allowedForms = ['login', 'forgot_password', 'contact', 'comment'];
        $cleanForms = array_values(array_intersect($forms, $allowedForms));

        $theme = Request::post('captcha_theme', 'auto');
        if (!in_array($theme, ['auto', 'light', 'dark'], true)) {
            $theme = 'auto';
        }

        Setting::set('captcha_provider', $provider);
        Setting::set('captcha_cf_site_key', $cfSiteKey);
        if ($cfSecretKey !== '') {
            Setting::set('captcha_cf_secret_key', $cfSecretKey);
        }

        Setting::set('captcha_rc_site_key', $rcSiteKey);
        if ($rcSecretKey !== '') {
            Setting::set('captcha_rc_secret_key', $rcSecretKey);
        }

        Setting::setJson('captcha_forms', $cleanForms);
        Setting::set('captcha_theme', $theme);
        Setting::clearCache();

        Session::flash('success', 'CAPTCHA güvenlik ayarları başarıyla kaydedildi.');
        $this->redirect('/admin/captcha');
    }

    public function test(): void
    {
        $provider = Request::post('provider', '');
        $token = Request::post('token', '');
        $tempSecret = Request::post('secret_key', '');

        if ($provider === '' || $token === '') {
            $this->json([
                'success' => false,
                'error'   => 'Lütfen önce kutucuğu işaretleyerek güvenlik testini onaylayın.',
            ]);
        }

        $res = CaptchaService::verify('test', $token, $tempSecret ?: null, $provider);
        $this->json($res);
    }
}
