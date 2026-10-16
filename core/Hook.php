<?php

namespace Core;

class Hook
{
    private static array $actions = [];
    private static array $filters = [];
    private static ?string $currentLoadingPlugin = null;

    public static function setCurrentPlugin(?string $pluginId): void
    {
        self::$currentLoadingPlugin = $pluginId;
    }

    public static function addAction(string $tag, callable $callback, int $priority = 10): void
    {
        self::$actions[$tag][$priority][] = [
            'callback' => $callback,
            'plugin'   => self::$currentLoadingPlugin,
        ];
    }

    public static function doAction(string $tag, mixed ...$args): void
    {
        if (empty(self::$actions[$tag])) {
            return;
        }

        ksort(self::$actions[$tag]);
        foreach (self::$actions[$tag] as $items) {
            foreach ($items as $item) {
                try {
                    call_user_func_array($item['callback'], $args);
                } catch (\Throwable $e) {
                    if (!empty($item['plugin'])) {
                        PluginManager::quarantine($item['plugin'], $e->getMessage(), $e->getTraceAsString());
                    } else {
                        self::logError("Action [{$tag}] error: " . $e->getMessage());
                    }
                }
            }
        }
    }

    public static function addFilter(string $tag, callable $callback, int $priority = 10): void
    {
        self::$filters[$tag][$priority][] = [
            'callback' => $callback,
            'plugin'   => self::$currentLoadingPlugin,
        ];
    }

    public static function applyFilters(string $tag, mixed $value, mixed ...$args): mixed
    {
        if (empty(self::$filters[$tag])) {
            return $value;
        }

        ksort(self::$filters[$tag]);
        foreach (self::$filters[$tag] as $items) {
            foreach ($items as $item) {
                try {
                    $value = call_user_func_array($item['callback'], array_merge([$value], $args));
                } catch (\Throwable $e) {
                    if (!empty($item['plugin'])) {
                        PluginManager::quarantine($item['plugin'], $e->getMessage(), $e->getTraceAsString());
                    } else {
                        self::logError("Filter [{$tag}] error: " . $e->getMessage());
                    }
                }
            }
        }

        return $value;
    }

    private static function logError(string $msg): void
    {
        $logDir = BVLOG_ROOT . '/storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        file_put_contents($logDir . '/hooks.log', '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL, FILE_APPEND);
    }
}
