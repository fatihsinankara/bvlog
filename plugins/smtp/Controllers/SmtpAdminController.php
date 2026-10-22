<?php

namespace Plugins\Smtp\Controllers;

use App\Models\Setting;
use Core\Controller;
use Core\Request;
use Core\Response;
use Core\Session;
use Plugins\Smtp\SmtpClient;

class SmtpAdminController extends Controller
{
    public function index(): void
    {
        $config = SmtpClient::getDefaultConfig();
        $this->adminView('plugins/smtp/views/admin/index', [
            'config'    => $config,
            'pageTitle' => 'SMTP E-posta Yapılandırması & Test',
        ]);
    }

    public function save(): void
    {
        $enabled = Request::post('smtp_enabled') ? '1' : '0';
        $host = trim(Request::post('smtp_host', ''));
        $port = max(1, min(65535, (int) Request::post('smtp_port', 587)));
        $encryption = strtolower(Request::post('smtp_encryption', 'tls'));
        if (!in_array($encryption, ['tls', 'ssl', 'none'], true)) {
            $encryption = 'tls';
        }

        $username = trim(Request::post('smtp_username', ''));
        $newPassword = Request::post('smtp_password', '');

        $fromEmail = trim(Request::post('smtp_from_email', ''));
        $fromName = trim(Request::post('smtp_from_name', ''));
        $timeout = max(5, min(60, (int) Request::post('smtp_timeout', 15)));

        $notifyContact = Request::post('smtp_notify_contact') ? '1' : '0';
        $adminEmail = trim(Request::post('smtp_admin_email', ''));

        Setting::set('smtp_enabled', $enabled);
        Setting::set('smtp_host', $host);
        Setting::set('smtp_port', (string) $port);
        Setting::set('smtp_encryption', $encryption);
        Setting::set('smtp_username', $username);

        // Yeni şifre girilmişse güncelle; boş bırakılmışsa eski şifreyi koru
        if ($newPassword !== '') {
            Setting::set('smtp_password', $newPassword);
        }

        Setting::set('smtp_from_email', $fromEmail);
        Setting::set('smtp_from_name', $fromName);
        Setting::set('smtp_timeout', (string) $timeout);

        Setting::set('smtp_notify_contact', $notifyContact);
        Setting::set('smtp_admin_email', $adminEmail);

        Session::flash('success', 'SMTP e-posta ayarları başarıyla kaydedildi.');
        $this->redirect('/admin/smtp');
    }

    public function test(): void
    {
        $testEmail = trim(Request::post('test_email', ''));
        if ($testEmail === '') {
            $testEmail = Request::post('smtp_from_email', setting('social_email', ''));
        }

        // Formdaki güncel değerlerle anında test edebilme imkanı
        $currentConfig = SmtpClient::getDefaultConfig();
        
        $host = trim(Request::post('smtp_host', ''));
        if ($host !== '') {
            $currentConfig['host'] = $host;
            $currentConfig['port'] = (int) Request::post('smtp_port', $currentConfig['port']);
            $currentConfig['encryption'] = Request::post('smtp_encryption', $currentConfig['encryption']);
            $currentConfig['username'] = Request::post('smtp_username', $currentConfig['username']);
            
            $postPass = Request::post('smtp_password', '');
            if ($postPass !== '') {
                $currentConfig['password'] = $postPass;
            }

            $currentConfig['from_email'] = Request::post('smtp_from_email', $currentConfig['from_email']);
            $currentConfig['from_name'] = Request::post('smtp_from_name', $currentConfig['from_name']);
        }

        $client = new SmtpClient($currentConfig);
        $result = $client->testConnection($testEmail);

        if (Request::isAjax() || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            Response::json($result);
            return;
        }

        if ($result['success']) {
            Session::flash('success', $result['message']);
        } else {
            Session::flash('error', 'SMTP Test Hatası: ' . $result['message']);
        }

        $this->redirect('/admin/smtp');
    }
}
