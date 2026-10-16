<?php

namespace App\Controllers\Admin;

use App\Models\Setting;
use Core\Controller;
use Core\Request;
use Core\Session;
use Core\ThemeManager;
use Core\Uploader;

class ThemeController extends Controller
{
    public function index(): void
    {
        $themes = ThemeManager::discover();
        $this->adminView('admin/themes/index', [
            'themes'    => $themes,
            'pageTitle' => 'Tema Yönetimi',
        ]);
    }

    public function activate(): void
    {
        $themeId = preg_replace('/[^a-z0-9_-]/i', '', Request::post('theme_id', 'default'));
        $themes = ThemeManager::discover();
        if (!isset($themes[$themeId])) {
            Session::flash('error', 'Seçilen tema bulunamadı.');
            $this->redirect('/admin/themes');
        }

        Setting::set('active_theme', $themeId);
        Session::flash('success', "'{$themes[$themeId]['name']}' teması başarıyla aktifleştirildi.");
        $this->redirect('/admin/themes');
    }

    public function upload(): void
    {
        $file = Request::file('theme_zip');
        if (!$file || empty($file['tmp_name'])) {
            Session::flash('error', 'Lütfen bir tema .zip paketi seçin.');
            $this->redirect('/admin/themes');
        }

        try {
            $themeId = Uploader::extractPackageZip($file, ThemeManager::getThemesDir(), 'theme.json');
            Session::flash('success', "Tema ({$themeId}) başarıyla yüklendi.");
        } catch (\Throwable $e) {
            Session::flash('error', 'Tema yüklenemedi: ' . $e->getMessage());
        }

        $this->redirect('/admin/themes');
    }

    public function delete(): void
    {
        $themeId = preg_replace('/[^a-z0-9_-]/i', '', Request::post('theme_id', ''));
        if ($themeId === '' || $themeId === 'default') {
            Session::flash('error', 'Varsayılan tema silinemez.');
            $this->redirect('/admin/themes');
        }

        if (ThemeManager::getActiveTheme() === $themeId) {
            Setting::set('active_theme', 'default');
        }

        $dir = ThemeManager::getThemesDir() . '/' . $themeId;
        if (is_dir($dir)) {
            $this->deleteDirectory($dir);
        }

        Session::flash('success', 'Tema silindi.');
        $this->redirect('/admin/themes');
    }

    private function deleteDirectory(string $dir): void
    {
        $items = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($items as $item) {
            $path = "{$dir}/{$item}";
            is_dir($path) ? $this->deleteDirectory($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}
