<?php

namespace Core;

use App\Models\Setting;

class ThemeManager
{
    public static function getThemesDir(): string
    {
        $dir = BVLOG_ROOT . '/themes';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }

    public static function getActiveTheme(): string
    {
        if (!is_installed()) {
            return 'default';
        }
        $theme = Setting::get('active_theme', 'default');
        $theme = preg_replace('/[^a-z0-9_-]/i', '', (string) $theme);
        if (empty($theme) || !is_dir(self::getThemesDir() . '/' . $theme)) {
            return 'default';
        }
        return $theme;
    }

    public static function discover(): array
    {
        $dir = self::getThemesDir();
        $activeTheme = self::getActiveTheme();
        $themes = [];

        $folders = glob($dir . '/*', GLOB_ONLYDIR) ?: [];
        foreach ($folders as $folder) {
            $id = basename($folder);
            $manifestFile = $folder . '/theme.json';
            if (!file_exists($manifestFile)) {
                continue;
            }

            $data = json_decode((string) file_get_contents($manifestFile), true);
            if (!is_array($data)) {
                continue;
            }

            $themes[$id] = [
                'id'          => $id,
                'name'        => $data['name'] ?? $id,
                'version'     => $data['version'] ?? '1.0.0',
                'author'      => $data['author'] ?? 'Bvlog',
                'description' => $data['description'] ?? '',
                'is_active'   => ($id === $activeTheme),
                'is_default'  => ($id === 'default'),
            ];
        }

        // Varsayılan tema her zaman mevcut görünmeli
        if (!isset($themes['default'])) {
            $themes['default'] = [
                'id'          => 'default',
                'name'        => 'Bvlog Editorial (Varsayılan)',
                'version'     => '1.0.0',
                'author'      => 'Bvlog Çekirdek',
                'description' => 'Gradientsiz, saf tipografik ve yüksek okunabilirlikli çekirdek tema.',
                'is_active'   => ($activeTheme === 'default'),
                'is_default'  => true,
            ];
        }

        return $themes;
    }

    /**
     * Şablon dosyasını hiyerarşik olarak arar:
     * 1. Aktif tema (themes/{active}/views/{view}.php)
     * 2. Varsayılan tema (themes/default/views/{view}.php)
     * 3. Çekirdek uygulama görünümü (app/Views/{view}.php)
     */
    public static function resolveViewPath(string $view): array
    {
        $active = self::getActiveTheme();
        $paths = [];

        if ($active !== 'default') {
            $themeFile = self::getThemesDir() . "/{$active}/views/{$view}.php";
            if (file_exists($themeFile)) {
                $paths[] = $themeFile;
            }
        }

        $defaultThemeFile = self::getThemesDir() . "/default/views/{$view}.php";
        if (file_exists($defaultThemeFile)) {
            $paths[] = $defaultThemeFile;
        }

        $coreViewFile = BVLOG_ROOT . "/app/Views/{$view}.php";
        if (file_exists($coreViewFile)) {
            $paths[] = $coreViewFile;
        }

        return $paths;
    }

    public static function logThemeError(string $theme, string $message): void
    {
        $logDir = BVLOG_ROOT . '/storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        $line = sprintf("[%s] THEME ERROR [%s]: %s\n", date('Y-m-d H:i:s'), $theme, $message);
        file_put_contents($logDir . '/themes.log', $line, FILE_APPEND);
    }
}
