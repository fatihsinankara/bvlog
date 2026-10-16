<?php

namespace App\Controllers\Admin;

use App\Models\Comment;
use Core\Controller;
use Core\Request;
use Core\Session;

class CommentController extends Controller
{
    public function index(): void
    {
        $comments = Comment::allWithPost();
        $this->adminView('admin/comments/index', [
            'comments'  => $comments,
            'pageTitle' => 'Yorum Yönetimi',
        ]);
    }

    public function updateStatus(string $id): void
    {
        $status = Request::post('status', 'approved');
        if (!in_array($status, ['approved', 'pending', 'spam'], true)) {
            $status = 'pending';
        }
        Comment::updateById((int) $id, ['status' => $status]);
        Session::flash('success', 'Yorum durumu güncellendi.');
        $this->redirect('/admin/comments');
    }

    public function delete(string $id): void
    {
        Comment::deleteById((int) $id);
        Session::flash('success', 'Yorum silindi.');
        $this->redirect('/admin/comments');
    }
}
