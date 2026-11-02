<?php

namespace App\Controllers\Admin;

use App\Models\Setting;
use Core\Cache;
use Core\Controller;
use Core\Request;
use Core\Session;

class CacheController extends Controller
{
    public function index(): void
    {
        $diagnostics = Cache::getDiagnostics();

        $this->adminView('admin/cache/index', [
            'diagnostics' => $diagnostics,
            'pageTitle'   => 'Önbellek Yönetimi',
        ]);
    }

    public function updateSettings(): void
    {
        $enabled = Request::post('cache_enabled') ? '1' : '0';
        $feeds   = Request::post('cache_feeds') ? '1' : '0';
        $auto    = Request::post('cache_auto_flush') ? '1' : '0';

        $allowedTtls = [300, 900, 1800, 3600, 10800, 21600, 43200, 86400, 604800];
        $rawTtl = (int) Request::post('cache_ttl', 3600);
        $ttl = in_array($rawTtl, $allowedTtls, true) ? $rawTtl : 3600;

        Setting::set('cache_enabled', $enabled);
        Setting::set('cache_ttl', (string) $ttl);
        Setting::set('cache_feeds', $feeds);
        Setting::set('cache_auto_flush', $auto);

        if ($enabled === '0') {
            Cache::flushAll(false);
            Session::flash('success', 'Dahili önbellek sistemi devre dışı bırakıldı ve mevcut önbellek dosyaları temizlendi.');
        } else {
            Session::flash('success', 'Önbellek yapılandırma ayarları başarıyla kaydedildi.');
        }

        $this->redirect('/admin/cache');
    }

    public function clear(): void
    {
        $mode = Request::post('mode', 'all');

        if ($mode === 'expired') {
            $purged = Cache::purgeExpired();
            if ($purged > 0) {
                Session::flash('success', "{$purged} adet süresi dolmuş önbellek kaydı temizlendi.");
            } else {
                Session::flash('success', 'Temizlenecek süresi dolmuş önbellek kaydı bulunmadı.');
            }
            $this->redirect('/admin/cache');
        }

        if ($mode === 'opcache') {
            if (function_exists('opcache_reset')) {
                @opcache_reset();
                Session::flash('success', 'PHP OPcache (sunucu derlenmiş betik önbelleği) başarıyla sıfırlandı.');
            } else {
                Session::flash('error', 'Sunucunuzda PHP OPcache eklentisi aktif değil veya sıfırlama yetkisi kapalı.');
            }
            $this->redirect('/admin/cache');
        }

        $includeSystem = (bool) Request::post('include_system', false);
        $deleted = Cache::flushAll($includeSystem);

        Session::flash('success', "Tüm dahili önbellek ({$deleted} dosya) ve PHP OPcache başarıyla temizlendi.");
        $this->redirect('/admin/cache');
    }

    public function clearGroup(): void
    {
        $group = trim((string) Request::post('group', ''));
        $groups = Cache::getGroups();

        if (!isset($groups[$group])) {
            Session::flash('error', 'Geçersiz önbellek grubu seçildi.');
            $this->redirect('/admin/cache');
        }

        $deleted = Cache::flushGroup($group);
        $title = $groups[$group]['title'];

        Session::flash('success', "“{$title}” grubuna ait {$deleted} önbellek kaydı temizlendi.");
        $this->redirect('/admin/cache');
    }

    public function deleteItem(): void
    {
        $filename = trim((string) Request::post('filename', ''));
        if ($filename !== '' && Cache::forgetByFilename($filename)) {
            Session::flash('success', 'Seçilen önbellek kaydı başarıyla silindi.');
        } else {
            Session::flash('error', 'Önbellek kaydı bulunamadı veya zaten silinmiş.');
        }

        $this->redirect('/admin/cache');
    }

    public function warmup(): void
    {
        if (!Cache::isEnabled()) {
            Session::flash('error', 'Önbelleği ısıtmak (Preload) için önce dahili önbellek sistemini etkinleştirmelisiniz.');
            $this->redirect('/admin/cache');
        }

        $result = Cache::warmup();
        Session::flash(
            'success',
            "Önbellek ısıtma tamamlandı! {$result['warmed_count']} temel sayfa ve besleme verisi {$result['duration_ms']} ms içinde önbelleğe alındı."
        );

        $this->redirect('/admin/cache');
    }
}
