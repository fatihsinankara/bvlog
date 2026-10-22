<?php

namespace App\Controllers;

use App\Models\Message;
use App\Models\User;
use Core\Controller;
use Core\Hook;
use Core\RateLimiter;
use Core\Request;
use Core\Security;
use Core\Session;

class ContactController extends Controller
{
    public function index(): void
    {
        $author = User::firstAuthor() ?? [];
        $this->view('contact/index', [
            'author' => $author,
            'meta'   => [
                'title'       => 'İletişim',
                'description' => 'Proje talepleri, sorularınız veya iş birliği için iletişime geçin.',
            ],
        ]);
    }

    public function send(): void
    {
        // Honeypot spam kontrolü
        if (!empty(Request::post('company_hp'))) {
            Session::flash('success', 'Mesajınız başarıyla iletildi.');
            $this->redirect('/contact');
        }

        if (!captcha_verify('contact')) {
            Session::flash('error', 'Güvenlik doğrulaması (CAPTCHA) onaylanmadı veya geçersiz. Lütfen kutucuğu işaretleyin.');
            $this->redirect('/contact');
        }

        $ip = Security::getClientIp();
        $rateKey = 'contact_' . $ip;
        if (RateLimiter::tooManyAttempts($rateKey, 3, 600)) {
            Session::flash('error', 'Çok fazla mesaj gönderdiniz. Lütfen 10 dakika sonra tekrar deneyin.');
            $this->redirect('/contact');
        }

        $name = Request::post('name', '');
        $email = Request::post('email', '');
        $subject = Request::post('subject', '');
        $message = Request::post('message', '');

        if (mb_strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($subject) < 2 || mb_strlen($message) < 5) {
            Session::flash('error', 'Lütfen tüm alanları doğru ve eksiksiz doldurun.');
            $this->redirect('/contact');
        }

        RateLimiter::hit($rateKey, 600);

        $messageData = [
            'name'       => mb_substr($name, 0, 100),
            'email'      => mb_substr($email, 0, 150),
            'subject'    => mb_substr($subject, 0, 200),
            'message'    => mb_substr(strip_tags($message), 0, 4000),
            'is_read'    => 0,
            'ip_address' => $ip,
        ];

        $messageId = Message::create($messageData);
        Hook::doAction('contact_message_received', array_merge($messageData, ['id' => $messageId]));

        Session::flash('success', 'Mesajınız başarıyla iletildi! En kısa sürede dönüş yapılacaktır.');
        $this->redirect('/contact');
    }
}
