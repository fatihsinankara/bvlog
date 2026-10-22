<?php

namespace App\Controllers\Admin;

use App\Models\User;
use Core\Controller;
use Core\RateLimiter;
use Core\Request;
use Core\Security;
use Core\Session;
use Core\View;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        View::render('admin/auth/login', [
            'meta' => ['title' => 'Yönetici Girişi'],
        ], null);
    }

    public function login(): void
    {
        $ip = Security::getClientIp();
        $rateKey = 'admin_login_' . $ip;

        if (RateLimiter::tooManyAttempts($rateKey, 5, 900)) {
            $waitMin = (int) ceil(RateLimiter::availableIn($rateKey, 900) / 60);
            Session::flash('error', "Çok fazla başarısız giriş denemesi! Lütfen {$waitMin} dakika sonra tekrar deneyin.");
            $this->redirect('/admin/login');
        }

        $identity = Request::post('identity', '');
        $password = Request::post('password', '');

        $user = User::findByEmailOrUsername($identity);
        if (!$user || !Security::verifyPassword($password, $user['password_hash'])) {
            RateLimiter::hit($rateKey, 900);
            Session::flash('error', 'Kullanıcı adı/e-posta veya parola hatalı.');
            $this->redirect('/admin/login');
        }

        RateLimiter::clear($rateKey);
        Session::regenerate();
        Session::set('admin_user_id', (int) $user['id']);
        Session::set('admin_user_name', $user['name']);

        Session::flash('success', 'Hoş geldiniz, ' . $user['name'] . '!');
        $this->redirect('/admin');
    }

    public function logout(): void
    {
        Session::destroy();
        $this->redirect('/admin/login');
    }

    public function showForgotPassword(): void
    {
        View::render('admin/auth/forgot-password', [
            'meta' => ['title' => 'Parolamı Unuttum'],
        ], null);
    }

    public function sendResetPassword(): void
    {
        $ip = Security::getClientIp();
        $rateKey = 'admin_forgot_' . $ip;

        if (RateLimiter::tooManyAttempts($rateKey, 3, 900)) {
            $waitMin = (int) ceil(RateLimiter::availableIn($rateKey, 900) / 60);
            Session::flash('error', "Çok fazla parola sıfırlama denemesi! Lütfen {$waitMin} dakika sonra tekrar deneyin.");
            $this->redirect('/admin/forgot-password');
        }

        $identity = trim(Request::post('identity', ''));
        if ($identity === '') {
            Session::flash('error', 'Lütfen kullanıcı adı veya e-posta adresinizi girin.');
            $this->redirect('/admin/forgot-password');
        }

        $user = User::findByEmailOrUsername($identity);
        if (!$user) {
            RateLimiter::hit($rateKey, 900);
            Session::flash('error', 'Bu kullanıcı adı veya e-posta adresiyle eşleşen bir yönetici hesabı bulunamadı.');
            $this->redirect('/admin/forgot-password');
        }

        $userEmail = trim((string) ($user['email'] ?? ''));
        if ($userEmail === '' || !filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Bu kullanıcı hesabına tanımlı geçerli bir e-posta adresi bulunmadığından parola sıfırlanamıyor.');
            $this->redirect('/admin/forgot-password');
        }

        // 10 karakterlik güvenli, okunaklı yeni parola üret
        $chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789#@%';
        $newPassword = substr(str_shuffle(str_repeat($chars, 3)), 0, 10);

        // Veritabanındaki parolayı güncelle
        User::updateById((int) $user['id'], [
            'password_hash' => Security::hashPassword($newPassword),
        ]);

        RateLimiter::clear($rateKey);

        // E-posta içeriği hazırla
        $siteTitle = setting('site_title', 'Bvlog');
        $userName = htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8');
        $loginUrl = url('/admin/login');
        $date = date('d.m.Y H:i');

        $mailSubject = "Yeni Yönetici Parolanız — {$siteTitle}";
        $htmlBody = <<<HTML
<div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 560px; margin: 0 auto; padding: 28px; border: 1px solid #e4e4e7; border-radius: 12px; background: #ffffff; color: #18181b;">
    <div style="margin-bottom: 20px; border-bottom: 2px solid #18181b; padding-bottom: 12px;">
        <h2 style="margin: 0; font-size: 18px; color: #18181b;">{$siteTitle} Yönetim Paneli</h2>
        <span style="font-size: 12px; color: #71717a;">Parola Sıfırlama Bildirimi &middot; {$date}</span>
    </div>

    <p style="font-size: 14px; line-height: 1.6; margin: 0 0 16px;">
        Merhaba <strong>{$userName}</strong>,
    </p>

    <p style="font-size: 13px; line-height: 1.6; color: #3f3f46; margin: 0 0 20px;">
        Yönetici hesabınız için yeni bir parola talebinde bulunuldu. Hesabınıza giriş yapabilmeniz için sistem tarafından geçici bir parola oluşturuldu:
    </p>

    <div style="background: #f4f4f5; border: 1px dashed #a1a1aa; border-radius: 8px; padding: 16px; text-align: center; margin-bottom: 24px;">
        <div style="font-size: 11px; text-transform: uppercase; font-weight: bold; color: #71717a; margin-bottom: 4px;">Yeni Parolanız</div>
        <div style="font-size: 22px; font-weight: bold; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; letter-spacing: 2px; color: #09090b;">
            {$newPassword}
        </div>
    </div>

    <div style="text-align: center; margin-bottom: 24px;">
        <a href="{$loginUrl}" style="display: inline-block; padding: 11px 24px; font-size: 13px; font-weight: 600; color: #ffffff; background: #18181b; border-radius: 8px; text-decoration: none;">
            Yönetim Paneline Giriş Yap &rarr;
        </a>
    </div>

    <div style="font-size: 12px; line-height: 1.6; color: #71717a; border-top: 1px solid #f4f4f5; padding-top: 16px;">
        <p style="margin: 0 0 6px;"><strong>Güvenlik Tavsiyesi:</strong> Giriş yaptıktan sonra parolanızı <em>Ayarlar & Profil</em> menüsünden kendi belirleyeceğiniz kalıcı bir parola ile değiştirmeniz önemle tavsiye edilir.</p>
        <p style="margin: 0;">Bu talebi siz yapmadıysanız lütfen derhal giriş yaparak parolanızı güncelleyin.</p>
    </div>
</div>
HTML;

        // E-postayı gönder (bvlog_mail varsa kullan, yoksa mail())
        $sent = function_exists('bvlog_mail')
            ? bvlog_mail($userEmail, $mailSubject, $htmlBody)
            : @mail($userEmail, '=?UTF-8?B?' . base64_encode($mailSubject) . '?=', $htmlBody, "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\nFrom: {$siteTitle} <noreply@" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ">\r\n");

        // E-posta adresini maskele (örn: a***n@site.com)
        $parts = explode('@', $userEmail);
        $namePart = $parts[0];
        $maskedName = mb_substr($namePart, 0, 1) . str_repeat('*', max(2, mb_strlen($namePart) - 2)) . mb_substr($namePart, -1);
        $maskedEmail = $maskedName . '@' . ($parts[1] ?? '');

        if ($sent) {
            Session::flash('success', "Yeni parolanız kayıtlı e-posta adresinize ({$maskedEmail}) başarıyla gönderildi. Lütfen gelen kutunuzu (ve gerekiyorsa spam klasörünü) kontrol edin.");
        } else {
            // E-posta iletilemediyse bile parola değişti; kullanıcıya bilgi ver
            Session::flash('error', "Parolanız sıfırlandı ancak e-posta sunucusuna ulaşılamadı. Lütfen SMTP ayarlarını kontrol edin veya sistem yöneticinize danışın.");
        }

        $this->redirect('/admin/login');
    }
}
