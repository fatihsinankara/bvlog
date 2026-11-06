<?php

namespace App\Controllers\Admin;

use App\Models\User;
use Core\Controller;
use Core\Csrf;
use Core\Database;
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

        if (!captcha_verify('login')) {
            Session::flash('error', 'Güvenlik doğrulaması (CAPTCHA) başarısız oldu. Lütfen tekrar deneyin.');
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

        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            User::updateById((int) $user['id'], [
                'password_hash' => Security::hashPassword($password),
            ]);
        }

        RateLimiter::clear($rateKey);
        Session::regenerate();
        Csrf::rotate();
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

        if (!captcha_verify('forgot_password')) {
            Session::flash('error', 'Güvenlik doğrulaması (CAPTCHA) başarısız oldu. Lütfen tekrar deneyin.');
            $this->redirect('/admin/forgot-password');
        }

        $identity = trim(Request::post('identity', ''));
        RateLimiter::hit($rateKey, 900);

        $generic = 'Bu bilgiler bir yönetici hesabıyla eşleşiyorsa, parola sıfırlama bağlantısı kayıtlı e-posta adresine gönderildi. Bağlantı 1 saat geçerlidir.';
        if ($identity === '') {
            Session::flash('success', $generic);
            $this->redirect('/admin/login');
        }

        $user = User::findByEmailOrUsername($identity);
        $userEmail = trim((string) ($user['email'] ?? ''));
        if (!$user || $userEmail === '' || !filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
            Session::flash('success', $generic);
            $this->redirect('/admin/login');
        }

        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600);
        Database::query('DELETE FROM password_resets WHERE user_id = :id', ['id' => (int) $user['id']]);
        Database::insert('password_resets', [
            'user_id'    => (int) $user['id'],
            'token_hash' => hash('sha256', $token),
            'expires_at' => $expires,
        ]);

        $siteTitle = setting('site_title', 'Bvlog');
        $userName = htmlspecialchars((string) $user['name'], ENT_QUOTES, 'UTF-8');
        $resetUrl = url('/admin/reset-password?token=' . $token);
        $date = date('d.m.Y H:i');
        $mailSubject = "Parola sıfırlama bağlantısı — {$siteTitle}";
        $htmlBody = <<<HTML
<div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 560px; margin: 0 auto; padding: 28px; border: 1px solid #e4e4e7; border-radius: 12px; background: #ffffff; color: #18181b;">
    <h2 style="margin: 0 0 12px; font-size: 18px;">{$siteTitle}</h2>
    <p style="font-size: 14px; line-height: 1.6;">Merhaba <strong>{$userName}</strong>,</p>
    <p style="font-size: 13px; line-height: 1.6; color: #3f3f46;">Hesabınız için parola sıfırlama istendi ({$date}). Parola ancak aşağıdaki bağlantıyı açıp yeni bir parola belirlediğinizde değişir. Bağlantı 1 saat geçerlidir.</p>
    <p style="margin: 24px 0;"><a href="{$resetUrl}" style="display: inline-block; padding: 11px 24px; font-size: 13px; font-weight: 600; color: #ffffff; background: #18181b; border-radius: 8px; text-decoration: none;">Yeni parola belirle</a></p>
    <p style="font-size: 12px; color: #71717a;">Bu isteği siz yapmadıysanız e-postayı yok sayın. Mevcut parolanız değişmez.</p>
</div>
HTML;

        $host = parse_url(base_url(), PHP_URL_HOST) ?: 'localhost';
        if (function_exists('bvlog_mail')) {
            bvlog_mail($userEmail, $mailSubject, $htmlBody);
        } else {
            @mail(
                $userEmail,
                '=?UTF-8?B?' . base64_encode($mailSubject) . '?=',
                $htmlBody,
                "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\nFrom: {$siteTitle} <noreply@{$host}>\r\n"
            );
        }

        Session::flash('success', $generic);
        $this->redirect('/admin/login');
    }

    public function showResetPassword(): void
    {
        $token = (string) Request::get('token', '');
        $valid = $this->findResetToken($token) !== null;
        View::render('admin/auth/reset-password', [
            'meta'  => ['title' => 'Yeni Parola'],
            'token' => $valid ? $token : '',
            'valid' => $valid,
        ], null);
    }

    public function resetPassword(): void
    {
        $ip = Security::getClientIp();
        $rateKey = 'admin_reset_' . $ip;
        if (RateLimiter::tooManyAttempts($rateKey, 8, 900)) {
            Session::flash('error', 'Çok fazla deneme. Lütfen daha sonra tekrar deneyin.');
            $this->redirect('/admin/forgot-password');
        }
        RateLimiter::hit($rateKey, 900);

        $token = (string) Request::post('token', '');
        $password = (string) Request::post('password', '');
        $confirm = (string) Request::post('password_confirm', '');
        $row = $this->findResetToken($token);

        if (!$row) {
            Session::flash('error', 'Sıfırlama bağlantısı geçersiz veya süresi dolmuş.');
            $this->redirect('/admin/forgot-password');
        }
        if (strlen($password) < 8 || $password !== $confirm) {
            Session::flash('error', 'Parola en az 8 karakter olmalı ve iki alan eşleşmelidir.');
            $this->redirect('/admin/reset-password?token=' . urlencode($token));
        }

        User::updateById((int) $row['user_id'], [
            'password_hash' => Security::hashPassword($password),
        ]);
        Database::query('DELETE FROM password_resets WHERE user_id = :id', ['id' => (int) $row['user_id']]);
        RateLimiter::clear('admin_login_' . $ip);

        Session::flash('success', 'Parolanız güncellendi. Yeni parolanızla giriş yapabilirsiniz.');
        $this->redirect('/admin/login');
    }

    private function findResetToken(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        return Database::fetchOne(
            'SELECT * FROM password_resets WHERE token_hash = :h AND expires_at > :now LIMIT 1',
            ['h' => hash('sha256', $token), 'now' => date('Y-m-d H:i:s')]
        );
    }
}
