<?php

namespace Core;

class View
{
    public static function render(string $view, array $data = [], ?string $layout = 'layouts/main'): void
    {
        $content = self::renderFileWithFallback($view, $data);

        if ($layout !== null) {
            $layoutData = array_merge($data, ['content' => $content]);
            echo self::renderFileWithFallback($layout, $layoutData);
            return;
        }

        echo $content;
    }

    public static function renderAdmin(string $view, array $data = []): void
    {
        self::render($view, $data, 'layouts/admin');
    }

    public static function renderInstall(string $view, array $data = []): void
    {
        self::render($view, $data, 'layouts/install');
    }

    public static function renderPartial(string $view, array $data = []): string
    {
        return self::renderFileWithFallback($view, $data);
    }

    private static function renderFileWithFallback(string $view, array $data): string
    {
        // Admin ve install görünümleri doğrudan app/Views içinden çalışır
        if (str_starts_with($view, 'admin/') || str_starts_with($view, 'install/') || str_starts_with($view, 'layouts/admin') || str_starts_with($view, 'layouts/install') || str_starts_with($view, 'components/')) {
            $file = BVLOG_ROOT . "/app/Views/{$view}.php";
            if (file_exists($file)) {
                return self::captureFile($file, $data);
            }
        }

        $candidates = ThemeManager::resolveViewPath($view);
        foreach ($candidates as $index => $filePath) {
            try {
                return self::captureFile($filePath, $data);
            } catch (\Throwable $e) {
                $activeTheme = ThemeManager::getActiveTheme();
                ThemeManager::logThemeError($activeTheme, "Error rendering [{$view}] in {$filePath}: " . $e->getMessage());
                // İlk aday (özel tema) hata verdiyse sonraki adaya (varsayılan temaya) geç!
                continue;
            }
        }

        return '<div class="p-4 border border-red-300 bg-red-50 text-red-800 rounded">Görünüm dosyası bulunamadı: ' . e($view) . '</div>';
    }

    private static function captureFile(string $filePath, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        try {
            require $filePath;
            return (string) ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }
}
