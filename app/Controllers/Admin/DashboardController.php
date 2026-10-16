<?php

namespace App\Controllers\Admin;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Media;
use App\Models\Message;
use App\Models\Post;
use App\Models\Project;
use App\Models\Setting;
use Core\Controller;
use Core\Database;

class DashboardController extends Controller
{
    public function index(): void
    {
        $stats = [
            'posts_total'     => Post::count(),
            'posts_published' => Post::count('status = "published"'),
            'views_total'     => (int) Database::fetchColumn('SELECT COALESCE(SUM(views_count), 0) FROM posts'),
            'categories'      => Category::count(),
            'projects'        => Project::count(),
            'media_count'     => Media::count(),
            'pending_comments'=> Comment::count('status = "pending"'),
            'unread_messages' => Message::count('is_read = 0'),
        ];

        $recentPosts = array_slice(Post::allForAdmin(), 0, 5);
        $recentComments = array_slice(Comment::allWithPost(), 0, 5);
        $quarantinedPlugins = Setting::getJson('quarantined_plugins', []);

        $this->adminView('admin/dashboard/index', [
            'stats'              => $stats,
            'recentPosts'        => $recentPosts,
            'recentComments'     => $recentComments,
            'quarantinedPlugins' => $quarantinedPlugins,
            'pageTitle'          => 'Genel Bakış',
        ]);
    }
}
