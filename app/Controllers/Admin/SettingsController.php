<?php

namespace App\Controllers\Admin;

use App\Models\Setting;
use App\Models\User;
use Core\Controller;
use Core\Request;
use Core\Security;
use Core\Session;
use Core\Uploader;

class SettingsController extends Controller
{
    public function index(): void
    {
        $settings = Setting::loadAll();
        $author = User::firstAuthor() ?? [];
        $sections = Setting::getJson('homepage_sections', [
            ['id' => 'hero', 'title' => 'Yazar Tanıtım Kartı (Hero)', 'enabled' => true],
            ['id' => 'featured_posts', 'title' => 'Öne Çıkan Yazılar', 'enabled' => true],
            ['id' => 'latest_posts', 'title' => 'Son Blog Yazıları', 'enabled' => true],
            ['id' => 'featured_projects', 'title' => 'Seçkin Portfolyo Projeleri', 'enabled' => true],
            ['id' => 'cv_skills_summary', 'title' => 'Yetenek & Deneyim Özeti', 'enabled' => true],
            ['id' => 'contact_cta', 'title' => 'İletişim Çağrı Kutusu', 'enabled' => true],
        ]);

        $updateInfo = \Core\Updater::check(false);

        $this->adminView('admin/settings/index', [
            'settings'   => $settings,
            'author'     => $author,
            'sections'   => $sections,
            'updateInfo' => $updateInfo,
            'pageTitle'  => 'Site & Modül Ayarları',
        ]);
    }

    public function update(): void
    {
        $tab = Request::post('active_tab', 'general');

        if ($tab === 'general') {
            Setting::set('site_title', Request::post('site_title', 'Bvlog'));
            Setting::set('site_tagline', Request::post('site_tagline', ''));
            Setting::set('site_description', Request::post('site_description', ''));
            Setting::set('site_logo', Request::post('site_logo', ''));
            Setting::set('site_favicon', Request::post('site_favicon', ''));
            Setting::set('posts_per_page', (string) max(1, min(50, (int) Request::post('posts_per_page', 10))));
        } elseif ($tab === 'profile') {
            $author = User::firstAuthor();
            $name = Request::post('author_name', 'Yazar');
            $title = Request::post('author_title', '');
            $bio = Request::post('author_bio', '');
            $avatar = Request::post('author_avatar', '');
            $cvPdf = $author['cv_pdf'] ?? '';

            $pdfFile = Request::file('cv_pdf_file');
            if ($pdfFile && !empty($pdfFile['tmp_name'])) {
                try {
                    $cvPdf = Uploader::uploadPdf($pdfFile);
                } catch (\Throwable $e) {
                    Session::flash('error', 'CV PDF yüklenemedi: ' . $e->getMessage());
                    $this->redirect('/admin/settings?tab=profile');
                }
            }

            Setting::set('author_name', $name);
            Setting::set('author_title', $title);
            Setting::set('author_bio', $bio);
            Setting::set('social_github', Request::post('social_github', ''));
            Setting::set('social_linkedin', Request::post('social_linkedin', ''));
            Setting::set('social_twitter', Request::post('social_twitter', ''));
            Setting::set('social_instagram', Request::post('social_instagram', ''));
            Setting::set('social_youtube', Request::post('social_youtube', ''));
            Setting::set('social_email', Request::post('social_email', ''));

            if ($author) {
                User::updateById((int) $author['id'], [
                    'name'       => $name,
                    'role_title' => $title,
                    'bio'        => $bio,
                    'avatar'     => $avatar ?: null,
                    'cv_pdf'     => $cvPdf ?: null,
                ]);
                Session::set('admin_user_name', $name);
            }
        } elseif ($tab === 'homepage') {
            $heroStyle = Request::post('homepage_hero_style', 'minimal_bio');
            if (!in_array($heroStyle, ['minimal_bio', 'featured_post', 'split', 'none'], true)) {
                $heroStyle = 'minimal_bio';
            }
            $postStyle = Request::post('homepage_post_style', 'list') === 'grid' ? 'grid' : 'list';

            Setting::set('homepage_hero_style', $heroStyle);
            Setting::set('homepage_post_style', $postStyle);

            $sectionsJson = (string) ($_POST['homepage_sections_json'] ?? '');
            $decoded = json_decode($sectionsJson, true);
            if (is_array($decoded)) {
                Setting::setJson('homepage_sections', $decoded);
            }
        } elseif ($tab === 'modules') {
            Setting::set('module_portfolio', Request::post('module_portfolio') ? '1' : '0');
            Setting::set('module_cv', Request::post('module_cv') ? '1' : '0');
            Setting::set('module_comments', Request::post('module_comments') ? '1' : '0');
            Setting::set('comments_moderation', Request::post('comments_moderation') ? '1' : '0');
            Setting::set('module_contact', Request::post('module_contact') ? '1' : '0');
        } elseif ($tab === 'seo') {
            Setting::set('og_image', Request::post('og_image', ''));
            Setting::set('seo_allow_index', Request::post('seo_allow_index') ? '1' : '0');
        } elseif ($tab === 'permalinks') {
            $structure = Request::post('permalink_structure', 'default');
            if (!in_array($structure, ['default', 'category_slug', 'blog_category_slug', 'post_only'], true)) {
                $structure = 'default';
            }
            Setting::set('permalink_structure', $structure);
        } elseif ($tab === 'security') {
            Setting::set('maintenance_mode', Request::post('maintenance_mode') ? '1' : '0');
            Setting::set('maintenance_message', Request::post('maintenance_message', ''));

            $newPass = Request::post('new_password', '');
            $currentPass = Request::post('current_password', '');
            if ($newPass !== '') {
                $author = User::find((int) Session::get('admin_user_id', 1));
                if (!$author || !Security::verifyPassword($currentPass, $author['password_hash'])) {
                    Session::flash('error', 'Mevcut parolanız hatalı olduğu için parola güncellenemedi.');
                    $this->redirect('/admin/settings?tab=security');
                }
                if (strlen($newPass) < 6) {
                    Session::flash('error', 'Yeni parola en az 6 karakter olmalıdır.');
                    $this->redirect('/admin/settings?tab=security');
                }
                User::updateById((int) $author['id'], [
                    'password_hash' => Security::hashPassword($newPass),
                ]);
            }
        }

        Session::flash('success', 'Ayarlar başarıyla kaydedildi.');
        $this->redirect('/admin/settings?tab=' . urlencode($tab));
    }

    public function checkUpdates(): void
    {
        $result = \Core\Updater::check(true);

        if (Request::isAjax()) {
            $this->json($result);
        }

        if (!empty($result['has_update'])) {
            Session::flash('info', 'Yeni bir Bvlog sürümü mevcut: v' . $result['latest_version'] . '! Aşağıdaki bağlantıdan güncellemeyi inceleyebilirsiniz.');
        } elseif (!empty($result['error'])) {
            Session::flash('error', $result['error']);
        } else {
            Session::flash('success', 'Tebrikler! Bvlog sisteminiz güncel (v' . $result['current_version'] . ').');
        }

        $this->redirect('/admin/settings?tab=updates');
    }
}
