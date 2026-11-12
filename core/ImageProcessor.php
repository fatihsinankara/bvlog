<?php

namespace Core;

use Exception;

class ImageProcessor
{
    /** Yuklenen her gorsel icin (orijinalden kucukse) uretilen ek genislikler. */
    public const VARIANT_WIDTHS = [480, 768, 1080, 1600];

    /** Yazi govdesindeki gorseller icin varsayilan sizes degeri. */
    public const CONTENT_SIZES = '(min-width: 56rem) 848px, 100vw';

    /**
     * Öne çıkan görsel: Orantılı 1600px resize + WebP sıkıştırma. Daha küçük
     * ekranlar için srcset varyantları ayrıca üretilir.
     */
    public static function processFeatured(array $file, ?string $title = null, ?string $alt = null): array
    {
        return self::process($file, 1600, 'featured', 85, $title, $alt);
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

        $info = @getimagesize($tmpPath);
        $pixelW = (int) ($info[0] ?? 0);
        $pixelH = (int) ($info[1] ?? 0);
        if ($pixelW < 1 || $pixelH < 1 || $pixelW > 8192 || $pixelH > 8192 || ($pixelW * $pixelH) > 24000000) {
            throw new Exception('Görsel çözünürlüğü desteklenen sınırı aşıyor (en fazla 8192 px ve 24 megapiksel).');
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
        self::generateVariants($relPath, $dst);
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

    /**
     * Yerel yuklenen bir gorselin boyutu ([genislik, yukseklik]). Yalnizca
     * storage/uploads altindaki dosyalar okunur; dosya yoksa null.
     */
    public static function dimensions(string $path): ?array
    {
        static $memo = [];

        $rel = self::uploadPath($path);
        if ($rel === null) {
            return null;
        }
        if (array_key_exists($rel, $memo)) {
            return $memo[$rel];
        }

        $file = self::confinedFile($rel);
        $size = $file !== null ? @getimagesize($file) : false;

        return $memo[$rel] = (is_array($size) && $size[0] > 0 && $size[1] > 0)
            ? [(int) $size[0], (int) $size[1]]
            : null;
    }

    /**
     * Bir varyantin yolu: /a/b.webp + 480 => /a/b-480w.webp
     */
    public static function variantPath(string $relPath, int $width): string
    {
        return (string) preg_replace('/\.webp$/i', '-' . $width . 'w.webp', $relPath);
    }

    /**
     * Orijinalden dar olan her VARIANT_WIDTHS genisligi icin WebP varyanti
     * uretir; var olanlar atlanir. Uretilen varyant sayisini dondurur.
     * $source verilmezse dosya diskten okunur (mevcut gorseller icin).
     */
    public static function generateVariants(string $relPath, ?\GdImage $source = null): int
    {
        $rel = self::uploadPath($relPath);
        if ($rel === null || !str_ends_with(strtolower($rel), '.webp') || !function_exists('imagewebp')) {
            return 0;
        }
        $file = self::confinedFile($rel);
        if ($file === null || preg_match('/-\d+w\.webp$/i', $rel)) {
            return 0;
        }

        $owned = false;
        if ($source === null) {
            $source = @imagecreatefromwebp($file);
            if (!$source) {
                return 0;
            }
            $owned = true;
        }

        $origW = imagesx($source);
        $origH = imagesy($source);
        $created = 0;
        foreach (self::VARIANT_WIDTHS as $width) {
            if ($width >= $origW) {
                break;
            }
            $target = BVLOG_ROOT . self::variantPath($rel, $width);
            if (is_file($target)) {
                continue;
            }
            $height = (int) max(1, round(($origH / $origW) * $width));
            $dst = imagecreatetruecolor($width, $height);
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagefilledrectangle($dst, 0, 0, $width, $height, imagecolorallocatealpha($dst, 0, 0, 0, 127));
            imagecopyresampled($dst, $source, 0, 0, 0, 0, $width, $height, $origW, $origH);
            if (@imagewebp($dst, $target, 82)) {
                $created++;
            }
            imagedestroy($dst);
        }

        if ($owned) {
            imagedestroy($source);
        }
        return $created;
    }

    /**
     * Gorselin diskteki varyantlarini siler (medya silinirken).
     */
    public static function deleteVariants(string $relPath): void
    {
        $rel = self::uploadPath($relPath);
        if ($rel === null || !str_ends_with(strtolower($rel), '.webp')) {
            return;
        }
        foreach (self::VARIANT_WIDTHS as $width) {
            $file = self::confinedFile(self::variantPath($rel, $width));
            if ($file !== null) {
                @unlink($file);
            }
        }
    }

    /**
     * Diskte bulunan varyantlardan srcset ve sizes oznitelikleri. Varyant
     * yoksa bos dize doner; etiket yalnizca src ile calismaya devam eder.
     */
    public static function srcsetAttributes(string $path, string $sizes): string
    {
        $rel = self::uploadPath($path);
        $size = $rel !== null ? self::dimensions($rel) : null;
        if ($rel === null || $size === null || !str_ends_with(strtolower($rel), '.webp')) {
            return '';
        }

        $candidates = [];
        foreach (self::VARIANT_WIDTHS as $width) {
            if ($width < $size[0] && is_file(BVLOG_ROOT . self::variantPath($rel, $width))) {
                $candidates[] = url(self::variantPath($rel, $width)) . ' ' . $width . 'w';
            }
        }
        if ($candidates === []) {
            return '';
        }
        $candidates[] = url($rel) . ' ' . $size[0] . 'w';

        return 'srcset="' . htmlspecialchars(implode(', ', $candidates), ENT_QUOTES, 'UTF-8')
            . '" sizes="' . htmlspecialchars($sizes, ENT_QUOTES, 'UTF-8') . '"';
    }

    /**
     * URL ya da yolu /storage/uploads/... bicimine indirger (alt dizin
     * kurulumundaki taban yolu atilir). Yukleme dizini disindaysa null.
     */
    private static function uploadPath(string $path): ?string
    {
        $path = (string) parse_url($path, PHP_URL_PATH);
        $base = (string) parse_url(base_url(), PHP_URL_PATH);
        if ($base !== '' && $base !== '/' && str_starts_with($path, rtrim($base, '/') . '/')) {
            $path = substr($path, strlen(rtrim($base, '/')));
        }
        return str_starts_with($path, '/storage/uploads/') ? $path : null;
    }

    /**
     * Var olan ve gercekten storage/uploads altinda kalan dosyanin mutlak yolu.
     */
    private static function confinedFile(string $rel): ?string
    {
        $root = realpath(BVLOG_ROOT . '/storage/uploads');
        $file = realpath(BVLOG_ROOT . rawurldecode($rel));
        return ($root !== false && $file !== false && is_file($file) && str_starts_with($file, $root . DIRECTORY_SEPARATOR))
            ? $file
            : null;
    }

    /**
     * Gorsel etiketine yazilacak width/height oznitelikleri. Boyut bilinmiyorsa
     * $fallbackWidth (varsa) tek basina yazilir.
     */
    public static function sizeAttributes(string $path, ?int $fallbackWidth = null): string
    {
        $size = self::dimensions($path);
        if ($size !== null) {
            return 'width="' . $size[0] . '" height="' . $size[1] . '"';
        }
        return $fallbackWidth !== null ? 'width="' . $fallbackWidth . '"' : '';
    }

    /**
     * Yazi icerigindeki gorsellere loading="lazy", decoding="async" ve yerel
     * dosyalar icin width/height ile (varyant varsa) srcset ekler. Var olan oznitelikler korunur.
     * Yerlesim kaymasini (CLS) ve ilk yuklemedeki gereksiz indirmeleri azaltir.
     */
    public static function enhanceContentImages(string $html): string
    {
        if (stripos($html, '<img') === false) {
            return $html;
        }

        return (string) preg_replace_callback('/<img\b([^>]*)>/i', static function (array $m): string {
            $attrs = $m[1];
            $add = '';
            if (!preg_match('/\sloading\s*=/i', $attrs)) {
                $add .= ' loading="lazy"';
            }
            if (!preg_match('/\sdecoding\s*=/i', $attrs)) {
                $add .= ' decoding="async"';
            }
            if (!preg_match('/\ssrc\s*=\s*(["\'])(.*?)\1/i', $attrs, $src)) {
                return '<img' . $add . $attrs . '>';
            }
            $path = html_entity_decode($src[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (!preg_match('/\s(width|height)\s*=/i', $attrs)) {
                $size = self::dimensions($path);
                if ($size !== null) {
                    $add .= ' width="' . $size[0] . '" height="' . $size[1] . '"';
                }
            }
            if (!preg_match('/\ssrcset\s*=/i', $attrs)) {
                $srcset = self::srcsetAttributes($path, self::CONTENT_SIZES);
                if ($srcset !== '') {
                    $add .= ' ' . $srcset;
                }
            }
            return '<img' . $add . $attrs . '>';
        }, $html);
    }
}
