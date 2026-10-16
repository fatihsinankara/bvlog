<?php

namespace App\Controllers\Admin;

use App\Models\Message;
use Core\Controller;
use Core\Session;

class MessageController extends Controller
{
    public function index(): void
    {
        $messages = Message::all('id DESC');
        $this->adminView('admin/messages/index', [
            'messages'  => $messages,
            'pageTitle' => 'İletişim Mesajları',
        ]);
    }

    public function markRead(string $id): void
    {
        Message::updateById((int) $id, ['is_read' => 1]);
        Session::flash('success', 'Mesaj okundu olarak işaretlendi.');
        $this->redirect('/admin/messages');
    }

    public function delete(string $id): void
    {
        Message::deleteById((int) $id);
        Session::flash('success', 'Mesaj silindi.');
        $this->redirect('/admin/messages');
    }
}
