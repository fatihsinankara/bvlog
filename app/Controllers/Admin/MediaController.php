<?php

namespace App\Controllers\Admin;

use App\Models\Media;
use Core\Controller;
use Core\ImageProcessor;
use Core\Request;
use Core\Session;

class MediaController extends Controller
{
    public function index(): void
    {
        $query = Request::get('q', '');
        $items = Media::search($query, 100, 0);

        $this->adminView('admin/media/index', [
            'items'     => $items,
            'query'     => $query,
            'pageTitle' => 'Medya Galerisi',
        ]);
    }

    public function listJson(): void
    {
        $query = Request::get('q', '');
        $items = Media::search($query, 80, 0);
        $this->json(['success' => true, 'items' => $items]);
    }

    public function upload(): void
    {
        $file = Request::file('file') ?? Request::file('image');
        $mode = Request::post('mode', 'inline'); // 'featured' (1600px + srcset varyantlari) veya 'inline' (orijinal boyut WebP)
        $title = Request::post('title', '');
        $alt = Request::post('alt_text', '');

        if (!$file || empty($file['tmp_name'])) {
            if (Request::isAjax()) {
                $this->json(['success' => false, 'message' => 'Yüklenecek görsel seçilmedi.'], 400);
            }
            Session::flash('error', 'Yüklenecek görsel seçilmedi.');
            $this->redirect('/admin/media');
        }

        try {
            if ($mode === 'featured') {
                $media = ImageProcessor::processFeatured($file, $title ?: null, $alt ?: null);
            } else {
                $media = ImageProcessor::processInline($file, 'content', $title ?: null, $alt ?: null);
            }

            if (Request::isAjax()) {
                $this->json([
                    'success' => true,
                    'media'   => $media,
                    'url'     => $media['filepath'],
                ]);
            }

            Session::flash('success', 'Görsel başarıyla medya kütüphanesine eklendi.');
            $this->redirect('/admin/media');
        } catch (\Throwable $e) {
            if (Request::isAjax()) {
                $this->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            Session::flash('error', $e->getMessage());
            $this->redirect('/admin/media');
        }
    }

    public function update(string $id): void
    {
        $item = Media::find((int) $id);
        if (!$item) {
            if (Request::isAjax()) {
                $this->json(['success' => false, 'message' => 'Medya bulunamadı.'], 404);
            }
            $this->redirect('/admin/media');
        }

        $title = mb_substr(Request::post('title', ''), 0, 255);
        $alt = mb_substr(Request::post('alt_text', ''), 0, 255);

        Media::updateById((int) $item['id'], [
            'title'    => $title,
            'alt_text' => $alt,
        ]);

        if (Request::isAjax()) {
            $this->json(['success' => true, 'message' => 'Görsel bilgileri güncellendi.']);
        }

        Session::flash('success', 'Görsel bilgileri güncellendi.');
        $this->redirect('/admin/media');
    }

    public function delete(string $id): void
    {
        $item = Media::find((int) $id);
        if ($item) {
            $absFile = BVLOG_ROOT . '/' . ltrim($item['filepath'], '/');
            if (file_exists($absFile) && is_file($absFile)) {
                @unlink($absFile);
            }
            ImageProcessor::deleteVariants((string) $item['filepath']);
            Media::deleteById((int) $item['id']);
        }

        if (Request::isAjax()) {
            $this->json(['success' => true]);
        }

        Session::flash('success', 'Görsel silindi.');
        $this->redirect('/admin/media');
    }

    /**
     * Mevcut gorseller icin eksik srcset varyantlarini uretir. Uzun surmemesi
     * icin her istekte en fazla ~20 saniye calisir; kalanlar icin tekrar
     * calistirilabilir.
     */
    public function generateVariants(): void
    {
        if (!function_exists('imagewebp')) {
            Session::flash('error', 'Sunucuda GD/WebP desteği bulunmadığı için boyutlar oluşturulamadı.');
            $this->redirect('/admin/media');
        }

        @set_time_limit(60);
        $deadline = microtime(true) + 20;
        $rows = \Core\Database::fetchAll(
            "SELECT id, filepath FROM media WHERE mime_type = 'image/webp' AND width > :w ORDER BY id DESC",
            ['w' => min(ImageProcessor::VARIANT_WIDTHS)]
        );

        $created = 0;
        $done = 0;
        foreach ($rows as $row) {
            if (microtime(true) > $deadline) {
                break;
            }
            $created += ImageProcessor::generateVariants((string) $row['filepath']);
            $done++;
        }

        if ($created > 0) {
            \Core\Cache::flushAll();
        }

        $remaining = count($rows) - $done;
        $message = "{$done} görsel tarandı, {$created} yeni boyut oluşturuldu.";
        if ($remaining > 0) {
            $message .= " Kalan {$remaining} görsel için işlemi tekrar çalıştırın.";
        }
        Session::flash('success', $message);
        $this->redirect('/admin/media');
    }
}
