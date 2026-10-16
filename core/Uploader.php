<?php

namespace Core;

use Exception;
use ZipArchive;

class Uploader
{
    /**
     * CV PDF dosyası yükleme
     */
    public static function uploadPdf(array $file): string
    {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new Exception('Geçerli bir PDF dosyası seçilmedi.');
        }

        if (($file['size'] ?? 0) > 10 * 1024 * 1024) {
            throw new Exception('PDF dosyası 10MB sınırını aşamaz.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        if ($mime !== 'application/pdf') {
            throw new Exception('Yalnızca gerçek PDF belgeleri yüklenebilir.');
        }

        $relDir = '/storage/uploads/documents';
        $absDir = BVLOG_ROOT . $relDir;
        if (!is_dir($absDir)) {
            mkdir($absDir, 0755, true);
        }

        $filename = 'cv-' . bin2hex(random_bytes(8)) . '.pdf';
        $absPath = "{$absDir}/{$filename}";

        if (!move_uploaded_file($file['tmp_name'], $absPath)) {
            throw new Exception('PDF dosyası kaydedilemedi.');
        }

        return "{$relDir}/{$filename}";
    }

    /**
     * Tema veya Eklenti Zip paketi çıkartıcı (Zip-Slip korumalı)
     */
    public static function extractPackageZip(array $file, string $targetBaseDir, string $manifestFile): string
    {
        if (!class_exists('ZipArchive')) {
            throw new Exception('Sunucuda ZipArchive eklentisi aktif değil.');
        }

        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new Exception('Lütfen geçerli bir .zip dosyası seçin.');
        }

        if (($file['size'] ?? 0) > 15 * 1024 * 1024) {
            throw new Exception('Zip paketi 15MB sınırını aşamaz.');
        }

        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        if ($ext !== 'zip') {
            throw new Exception('Yalnızca .zip uzantılı paketler kabul edilir.');
        }

        $zip = new ZipArchive();
        if ($zip->open($file['tmp_name']) !== true) {
            throw new Exception('Zip arşivi açılamadı veya dosya bozuk.');
        }

        // Zip-Slip ve içerik güvenlik denetimi
        $foundManifest = null;
        $packageRootFolder = null;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);
            if ($entryName === false) {
                continue;
            }

            // Dizin atlama (../) saldırı kontrolü
            $normalized = str_replace('\\', '/', $entryName);
            if (str_contains($normalized, '../') || str_starts_with($normalized, '/')) {
                $zip->close();
                throw new Exception('Güvenlik İhlali: Zip arşivinde geçersiz dizin yolu tespit edildi.');
            }

            if (basename($normalized) === $manifestFile) {
                $foundManifest = $normalized;
                $parts = explode('/', $normalized);
                if (count($parts) === 2) {
                    $packageRootFolder = $parts[0];
                } elseif (count($parts) === 1) {
                    $packageRootFolder = '';
                }
            }
        }

        if ($foundManifest === null) {
            $zip->close();
            throw new Exception("Geçersiz paket: Arşiv içinde '{$manifestFile}' dosyası bulunamadı.");
        }

        $manifestContent = $zip->getFromName($foundManifest);
        $manifestData = json_decode((string) $manifestContent, true);
        if (!is_array($manifestData) || empty($manifestData['id']) || empty($manifestData['name'])) {
            $zip->close();
            throw new Exception("Geçersiz '{$manifestFile}': 'id' ve 'name' alanları zorunludur.");
        }

        $packageId = preg_replace('/[^a-z0-9_-]/i', '', (string) $manifestData['id']);
        if (empty($packageId) || $packageId === 'default') {
            $zip->close();
            throw new Exception('Geçersiz veya korumalı paket kimliği (id).');
        }

        $destDir = rtrim($targetBaseDir, '/') . '/' . $packageId;
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        // Güvenli çıkartma işlemi
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = str_replace('\\', '/', (string) $zip->getNameIndex($i));
            if (str_ends_with($entryName, '/')) {
                continue;
            }

            $relEntry = $entryName;
            if ($packageRootFolder !== '' && str_starts_with($entryName, $packageRootFolder . '/')) {
                $relEntry = substr($entryName, strlen($packageRootFolder) + 1);
            }

            if ($relEntry === '' || str_contains($relEntry, '..')) {
                continue;
            }

            $targetFile = $destDir . '/' . $relEntry;
            $targetFileDir = dirname($targetFile);
            if (!is_dir($targetFileDir)) {
                mkdir($targetFileDir, 0755, true);
            }

            $stream = $zip->getStream($zip->getNameIndex($i));
            if ($stream) {
                file_put_contents($targetFile, stream_get_contents($stream));
                fclose($stream);
            }
        }

        $zip->close();
        return $packageId;
    }
}
