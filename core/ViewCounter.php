<?php

namespace Core;

class ViewCounter
{
    private static bool $flushHooked = false;

    public static function record(int $postId): bool
    {
        if ($postId < 1) {
            return false;
        }

        $seen = self::seenIds();
        $mark = (string) $postId;
        if (in_array($mark, $seen, true)) {
            return false;
        }

        $seen[] = $mark;
        $seen = array_slice($seen, -40);
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (int) ($_SERVER['SERVER_PORT'] ?? 80) === 443;

        setcookie('bv_seen', implode('.', $seen), [
            'expires'  => time() + 86400 * 30,
            'path'     => '/',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        self::append($postId);
        self::hookFlush();
        return true;
    }

    public static function flushIfDue(): int
    {
        $tick = BVLOG_ROOT . '/storage/cache/views_flush_tick.txt';
        $dir = dirname($tick);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $last = is_file($tick) ? (int) @file_get_contents($tick) : 0;
        if ((time() - $last) < 60) {
            return 0;
        }
        @file_put_contents($tick, (string) time(), LOCK_EX);
        return self::flush();
    }

    public static function flush(): int
    {
        $file = self::queueFile();
        if (!is_file($file)) {
            return 0;
        }

        $handle = @fopen($file, 'c+');
        if (!$handle) {
            return 0;
        }
        if (!flock($handle, LOCK_EX)) {
            fclose($handle);
            return 0;
        }

        $raw = stream_get_contents($handle);
        ftruncate($handle, 0);
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);

        $counts = [];
        foreach (preg_split('/\s+/', (string) $raw) ?: [] as $part) {
            $id = (int) $part;
            if ($id > 0) {
                $counts[$id] = ($counts[$id] ?? 0) + 1;
            }
        }

        if ($counts === [] || !is_installed()) {
            return 0;
        }

        $updated = 0;
        foreach ($counts as $id => $n) {
            Database::query(
                'UPDATE posts SET views_count = views_count + :n WHERE id = :id',
                ['n' => $n, 'id' => $id]
            );
            $updated += $n;
        }
        return $updated;
    }

    private static function seenIds(): array
    {
        $raw = (string) ($_COOKIE['bv_seen'] ?? '');
        if ($raw === '' || !preg_match('/^[0-9.]+$/', $raw)) {
            return [];
        }
        return array_values(array_filter(explode('.', $raw), static fn ($id) => $id !== ''));
    }

    private static function append(int $postId): void
    {
        $file = self::queueFile();
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents($file, $postId . "\n", FILE_APPEND | LOCK_EX);
    }

    private static function queueFile(): string
    {
        return BVLOG_ROOT . '/storage/cache/pending_views.log';
    }

    private static function hookFlush(): void
    {
        if (self::$flushHooked) {
            return;
        }
        self::$flushHooked = true;
        register_shutdown_function(static function (): void {
            self::flushIfDue();
        });
    }
}
