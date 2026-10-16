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
}
