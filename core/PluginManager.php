<?php

namespace Core;

use App\Models\Setting;

class PluginManager
{
    public static function getPluginsDir(): string
    {
        $dir = BVLOG_ROOT . '/plugins';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }

    public static function isSafeMode(): bool
    {
        if (isset($_GET['safe_mode']) && $_GET['safe_mode'] === '1') {
            return true;
        }
        return file_exists(BVLOG_ROOT . '/storage/safe_mode.flag');
    }

    public static function boot(): void
    {
        if (!is_installed() || self::isSafeMode()) {
            return;
        }

        $activePlugins = Setting::getJson('active_plugins', []);
        if (empty($activePlugins)) {
            return;
        }

        foreach ($activePlugins as $pluginId) {
            $pluginId = preg_replace('/[^a-z0-9_-]/i', '', (string) $pluginId);
            $manifestPath = self::getPluginsDir() . "/{$pluginId}/plugin.json";

            if (!file_exists($manifestPath)) {
                continue;
            }

            $manifest = json_decode((string) file_get_contents($manifestPath), true);
            $mainFile = $manifest['main'] ?? 'plugin.php';
            $entryPath = self::getPluginsDir() . "/{$pluginId}/" . basename($mainFile);

            if (!file_exists($entryPath)) {
                self::quarantine($pluginId, "Ana eklenti dosyası ({$mainFile}) bulunamadı.", '');
                continue;
            }

            try {
                Hook::setCurrentPlugin($pluginId);
                require_once $entryPath;
                Hook::setCurrentPlugin(null);
            } catch (\Throwable $e) {
                Hook::setCurrentPlugin(null);
                self::quarantine($pluginId, $e->getMessage(), $e->getTraceAsString());
            }
        }
    }

    public static function discover(): array
    {
        $dir = self::getPluginsDir();
        $active = is_installed() ? Setting::getJson('active_plugins', []) : [];
        $quarantined = is_installed() ? Setting::getJson('quarantined_plugins', []) : [];
        $plugins = [];

        $folders = glob($dir . '/*', GLOB_ONLYDIR) ?: [];
        foreach ($folders as $folder) {
            $id = basename($folder);
            $manifestFile = $folder . '/plugin.json';
            if (!file_exists($manifestFile)) {
                continue;
            }

            $data = json_decode((string) file_get_contents($manifestFile), true);
            if (!is_array($data)) {
                continue;
            }

            $plugins[$id] = [
                'id'          => $id,
                'name'        => $data['name'] ?? $id,
                'version'     => $data['version'] ?? '1.0.0',
                'author'      => $data['author'] ?? 'Bilinmiyor',
                'description' => $data['description'] ?? '',
                'main'        => $data['main'] ?? 'plugin.php',
                'is_active'   => in_array($id, $active, true),
                'quarantine'  => $quarantined[$id] ?? null,
            ];
        }

        return $plugins;
    }

    public static function activate(string $pluginId): bool
    {
        $pluginId = preg_replace('/[^a-z0-9_-]/i', '', $pluginId);
        $plugins = self::discover();
        if (!isset($plugins[$pluginId])) {
            return false;
        }

        // Karantinadan temizle
        $quarantined = Setting::getJson('quarantined_plugins', []);
        unset($quarantined[$pluginId]);
        Setting::setJson('quarantined_plugins', $quarantined);

        // Ön çalışma kontrolü (Sandbox test)
        $entryPath = self::getPluginsDir() . "/{$pluginId}/" . basename($plugins[$pluginId]['main']);
        if (!file_exists($entryPath)) {
            self::quarantine($pluginId, 'Eklenti giriş dosyası bulunamadı.', '');
            return false;
        }

        try {
            Hook::setCurrentPlugin($pluginId);
            require_once $entryPath;
            Hook::setCurrentPlugin(null);
        } catch (\Throwable $e) {
            Hook::setCurrentPlugin(null);
            self::quarantine($pluginId, $e->getMessage(), $e->getTraceAsString());
            return false;
        }

        $active = Setting::getJson('active_plugins', []);
        if (!in_array($pluginId, $active, true)) {
            $active[] = $pluginId;
            Setting::setJson('active_plugins', array_values($active));
        }

        return true;
    }

    public static function deactivate(string $pluginId): void
    {
        $active = Setting::getJson('active_plugins', []);
        $active = array_values(array_filter($active, fn($item) => $item !== $pluginId));
        Setting::setJson('active_plugins', $active);
    }

    public static function quarantine(string $pluginId, string $errorMsg, string $trace): void
    {
        self::deactivate($pluginId);

        $quarantined = Setting::getJson('quarantined_plugins', []);
        $quarantined[$pluginId] = [
            'error' => $errorMsg,
            'time'  => date('Y-m-d H:i:s'),
            'trace' => mb_substr($trace, 0, 800),
        ];
        Setting::setJson('quarantined_plugins', $quarantined);

        $logDir = BVLOG_ROOT . '/storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        $logLine = sprintf("[%s] QUARANTINED PLUGIN [%s]: %s\n", date('Y-m-d H:i:s'), $pluginId, $errorMsg);
        file_put_contents($logDir . '/plugins.log', $logLine, FILE_APPEND);

        Session::flash('error', "Güvenlik Koruması: '{$pluginId}' eklentisi çalışma zamanı hatası ürettiği için otomatik olarak karantinaya alındı ve devre dışı bırakıldı.");
    }
}
