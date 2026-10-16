<?php

namespace App\Controllers\Admin;

use App\Models\Setting;
use Core\Controller;
use Core\PluginManager;
use Core\Request;
use Core\Session;
use Core\Uploader;

class PluginController extends Controller
{
    public function index(): void
    {
        $plugins = PluginManager::discover();
        $this->adminView('admin/plugins/index', [
            'plugins'    => $plugins,
            'isSafeMode' => PluginManager::isSafeMode(),
            'pageTitle'  => 'Eklenti (Plugin) Yönetimi',
        ]);
    }

    public function toggle(): void
    {
        $pluginId = preg_replace('/[^a-z0-9_-]/i', '', Request::post('plugin_id', ''));
        $action = Request::post('action', 'activate');

        if ($pluginId === '') {
            $this->redirect('/admin/plugins');
        }

        if ($action === 'deactivate') {
            PluginManager::deactivate($pluginId);
            Session::flash('success', "Eklenti ({$pluginId}) devre dışı bırakıldı.");
        } else {
            if (PluginManager::activate($pluginId)) {
                Session::flash('success', "Eklenti ({$pluginId}) başarıyla aktifleştirildi.");
            }
        }

        $this->redirect('/admin/plugins');
    }

    public function clearQuarantine(): void
    {
        $pluginId = preg_replace('/[^a-z0-9_-]/i', '', Request::post('plugin_id', ''));
        $quarantined = Setting::getJson('quarantined_plugins', []);
        unset($quarantined[$pluginId]);
        Setting::setJson('quarantined_plugins', $quarantined);

        Session::flash('success', 'Karantina kaydı temizlendi.');
        $this->redirect('/admin/plugins');
    }

    public function upload(): void
    {
        $file = Request::file('plugin_zip');
        if (!$file || empty($file['tmp_name'])) {
            Session::flash('error', 'Lütfen bir eklenti .zip paketi seçin.');
            $this->redirect('/admin/plugins');
        }

        try {
            $pluginId = Uploader::extractPackageZip($file, PluginManager::getPluginsDir(), 'plugin.json');
            Session::flash('success', "Eklenti ({$pluginId}) başarıyla yüklendi. Listeden aktifleştirebilirsiniz.");
        } catch (\Throwable $e) {
            Session::flash('error', 'Eklenti yüklenemedi: ' . $e->getMessage());
        }

        $this->redirect('/admin/plugins');
    }

    public function delete(): void
    {
        $pluginId = preg_replace('/[^a-z0-9_-]/i', '', Request::post('plugin_id', ''));
        if ($pluginId === '') {
            $this->redirect('/admin/plugins');
        }

        PluginManager::deactivate($pluginId);
        $quarantined = Setting::getJson('quarantined_plugins', []);
        unset($quarantined[$pluginId]);
        Setting::setJson('quarantined_plugins', $quarantined);

        $dir = PluginManager::getPluginsDir() . '/' . $pluginId;
        if (is_dir($dir)) {
            $this->deleteDirectory($dir);
        }

        Session::flash('success', 'Eklenti silindi.');
        $this->redirect('/admin/plugins');
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
