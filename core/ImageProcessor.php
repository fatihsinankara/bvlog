<?php

namespace Core;

use Exception;

class ImageProcessor
{
    /**
     * Öne çıkan görsel: Orantılı 1080px resize + WebP sıkıştırma
     */
    public static function processFeatured(array $file, ?string $title = null, ?string $alt = null): array
    {
        return self::process($file, 1080, 'featured', 85, $title, $alt);
    }

    /**
     * Inline editör ve medya galerisi: Boyut koruyarak kayıpsız/yüksek kaliteli WebP dönüşümü
     */
    public static function processInline(array $file, string $folder = 'content', ?string $title = null, ?string $alt = null): array
    {
        return self::process($file, null, $folder, 90, $title, $alt);
    }

    public static function process(
        array $file,
        ?int $maxWidth,
        string $folder = 'general',
        int $quality = 85,
        ?string $title = null,
        ?string $alt = null
    ): array {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new Exception('Geçerli bir yüklenen dosya bulunamadı.');
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new Exception('Dosya yükleme sırasında bir hata oluştu (Kod: ' . ($file['error'] ?? -1) . ').');
        }

        // Maksimum 15MB kontrolü
        if (($file['size'] ?? 0) > 15 * 1024 * 1024) {
            throw new Exception('Görsel boyutu 15MB sınırını aşamaz.');
        }

        $tmpPath = $file['tmp_name'];
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmpPath);

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array($mime, $allowedMimes, true)) {
            throw new Exception('Yalnızca JPEG, PNG, WebP ve GIF formatındaki görseller kabul edilir.');
        }

        $src = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($tmpPath),
            'image/png'  => @imagecreatefrompng($tmpPath),
            'image/webp' => @imagecreatefromwebp($tmpPath),
            'image/gif'  => @imagecreatefromgif($tmpPath),
            default      => false,
        };

        if (!$src) {
            throw new Exception('Görsel dosyası okunamadı veya bozuk.');
        }

        // EXIF yön düzeltmesi (Mobil kamera çekimleri için)
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data($tmpPath);
            if (!empty($exif['Orientation'])) {
                $src = self::fixOrientation($src, (int) $exif['Orientation']);
            }
        }

        $origW = imagesx($src);
        $origH = imagesy($src);

        if ($maxWidth !== null && $origW > $maxWidth) {
            $newW = $maxWidth;
            $newH = (int) max(1, round(($origH / $origW) * $newW));
        } else {
            $newW = $origW;
            $newH = $origH;
        }

        $dst = imagecreatetruecolor($newW, $newH);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefilledrectangle($dst, 0, 0, $newW, $newH, $transparent);

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

        $yearMonth = date('Y/m');
        $relDir = "/storage/uploads/{$folder}/{$yearMonth}";
        $absDir = BVLOG_ROOT . $relDir;

        if (!is_dir($absDir)) {
            mkdir($absDir, 0755, true);
        }

        $filename = bin2hex(random_bytes(16)) . '.webp';
        $absPath = "{$absDir}/{$filename}";
        $relPath = "{$relDir}/{$filename}";

        if (!imagewebp($dst, $absPath, $quality)) {
            imagedestroy($src);
            imagedestroy($dst);
            throw new Exception('Görsel WebP formatına dönüştürülemedi.');
        }

        imagedestroy($src);
        imagedestroy($dst);

        $finalSize = filesize($absPath) ?: 0;
        $origName = pathinfo($file['name'] ?? 'image', PATHINFO_FILENAME);
        $cleanTitle = $title ?: $origName;
        $cleanAlt = $alt ?: $cleanTitle;

        $mediaData = [
            'filename'  => $filename,
            'filepath'  => $relPath,
            'mime_type' => 'image/webp',
            'filesize'  => $finalSize,
            'width'     => $newW,
            'height'    => $newH,
            'alt_text'  => $cleanAlt,
            'title'     => $cleanTitle,
            'folder'    => $folder,
        ];

        // Veritabanı kuruluysa media tablosuna otomatik kaydet
        try {
            if (is_installed()) {
                $mediaData['id'] = Database::insert('media', $mediaData);
            }
        } catch (\Throwable $e) {
            // Sessizce geç
        }

        return $mediaData;
    }

    private static function fixOrientation(\GdImage $image, int $orientation): \GdImage
    {
        return match ($orientation) {
            3 => imagerotate($image, 180, 0) ?: $image,
            6 => imagerotate($image, -90, 0) ?: $image,
            8 => imagerotate($image, 90, 0) ?: $image,
            default => $image,
        };
    }
}
