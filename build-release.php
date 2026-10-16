<?php

/**
 * Bvlog — Son Kullanıcı Dağıtım Paketi (Release ZIP) Oluşturucu
 * Kullanım: php build-release.php  veya  npm run release
 *
 * Geliştirme dosyalarını (node_modules, resources, vite/tailwind config,
 * yerel veritabanı ayarları, yüklenen test görselleri ve kurulum kilidi)
 * dışarıda bırakarak doğrudan paylaşımlı hostinge yüklenmeye hazır
 * temiz bir ZIP paketi üretir.
 */

declare(strict_types=1);

$rootDir = __DIR__;
$appConfig = require $rootDir . '/config/app.php';
$version = $appConfig['version'] ?? '1.0.0';

$distDir = $rootDir . '/dist';
if (!is_dir($distDir)) {
    mkdir($distDir, 0755, true);
}

$zipFileName = "bvlog-v{$version}.zip";
$zipFilePath = $distDir . '/' . $zipFileName;

if (file_exists($zipFilePath)) {
    unlink($zipFilePath);
}

$zip = new ZipArchive();
if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "Hata: ZIP dosyası oluşturulamadı: {$zipFilePath}\n");
    exit(1);
}

// Son kullanıcı paketine DAHİL EDİLMEYECEK kök dizin/dosyalar
$excludedRootItems = [
    '.git',
    '.github',
    '.idea',
    '.vscode',
    'node_modules',
    'resources',
    'dist',
    'vendor',
    '.env',
    '.gitignore',
    'package.json',
    'package-lock.json',
    'vite.config.js',
    'tailwind.config.js',
    'postcss.config.js',
    'build-release.php',
];

// Son kullanıcı paketine DAHİL EDİLMEYECEK göreli yollar (yerel kurulum verileri)
$excludedRelativePaths = [
    'config/database.php',
    'storage/installed.lock',
    'storage/quarantine.json',
];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($rootDir, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

$addedFiles = 0;

foreach ($iterator as $item) {
    $fullPath = $item->getPathname();
    $relativePath = ltrim(str_replace('\\', '/', substr($fullPath, strlen($rootDir))), '/');

    // 1. Kök seviye hariç tutulanları atla
    $firstSegment = explode('/', $relativePath)[0];
    if (in_array($firstSegment, $excludedRootItems, true)) {
        continue;
    }

    // 2. Özel göreli yolları atla
    if (in_array($relativePath, $excludedRelativePaths, true)) {
        continue;
    }

    // 3. storage/uploads, storage/logs, storage/cache içindeki yerel dosyaları atla (.gitkeep ve .htaccess hariç)
    if (preg_match('#^storage/(uploads|logs|cache)/(.+)$#', $relativePath, $matches)) {
        if (!in_array($matches[2], ['.gitkeep', '.htaccess'], true)) {
            continue;
        }
    }

    if ($item->isDir()) {
        $zip->addEmptyDir($relativePath);
    } else {
        $zip->addFile($fullPath, $relativePath);
        $addedFiles++;
    }
}

$zip->close();

$sizeKb = round(filesize($zipFilePath) / 1024, 1);
echo "============================================================\n";
echo "Bvlog Dağıtım Paketi Hazır!\n";
echo "Dosya   : dist/{$zipFileName}\n";
echo "Boyut   : {$sizeKb} KB ({$addedFiles} dosya)\n";
echo "============================================================\n";
