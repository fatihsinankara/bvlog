<?php

namespace Plugins\Collections\Models;

use Core\Database;

class CollectionPost
{
    protected static string $table = 'collection_posts';

    public static function getPostRelation(int $postId): ?array
    {
        return Database::fetchOne(
            "SELECT * FROM `" . static::$table . "` WHERE post_id = :pid LIMIT 1",
            ['pid' => $postId]
        );
    }

    public static function syncPost(int $postId, ?int $collectionId, int $sortOrder = 1): void
    {
        if (!$collectionId || $collectionId <= 0) {
            self::removePost($postId);
            return;
        }

        $existing = self::getPostRelation($postId);
        if ($existing) {
            Database::update(
                static::$table,
                ['collection_id' => $collectionId, 'sort_order' => $sortOrder],
                'id = :id',
                ['id' => $existing['id']]
            );
        } else {
            Database::insert(static::$table, [
                'collection_id' => $collectionId,
                'post_id'       => $postId,
                'sort_order'    => $sortOrder,
            ]);
        }
    }

    public static function removePost(int $postId): void
    {
        Database::query("DELETE FROM `" . static::$table . "` WHERE post_id = :pid", ['pid' => $postId]);
    }

    public static function getNextOrder(int $collectionId): int
    {
        $max = Database::fetchColumn(
            "SELECT MAX(sort_order) FROM `" . static::$table . "` WHERE collection_id = :cid",
            ['cid' => $collectionId]
        );

        return $max !== null ? ((int) $max + 1) : 1;
    }

    /**
     * Koleksiyon düzenleme ekranından gelen toplu yazı & sıra bilgisini senkronize eder.
     * @param int $collectionId
     * @param array<int, int> $postOrders [post_id => sort_order]
     */
    public static function syncCollectionPosts(int $collectionId, array $postOrders): void
    {
        // Önce mevcut ilişkileri al
        $currentPosts = Database::fetchAll(
            "SELECT post_id FROM `" . static::$table . "` WHERE collection_id = :cid",
            ['cid' => $collectionId]
        );
        $currentIds = array_column($currentPosts, 'post_id');
        $newIds = array_keys($postOrders);

        // Çıkarılan yazıları sil
        $toDelete = array_diff($currentIds, $newIds);
        if (!empty($toDelete)) {
            $inClause = implode(',', array_map('intval', $toDelete));
            Database::query("DELETE FROM `" . static::$table . "` WHERE collection_id = :cid AND post_id IN ({$inClause})", [
                'cid' => $collectionId
            ]);
        }

        // Yeni veya güncellenen yazıları kaydet
        foreach ($postOrders as $postId => $order) {
            $postId = (int) $postId;
            $order = max(1, (int) $order);

            // Başka bir koleksiyondaysa bile bu koleksiyona taşı (Post bir koleksiyonda olur)
            Database::query(
                "INSERT INTO `" . static::$table . "` (collection_id, post_id, sort_order) 
                 VALUES (:cid, :pid, :ord)
                 ON DUPLICATE KEY UPDATE collection_id = :cid_up, sort_order = :ord_up",
                [
                    'cid'    => $collectionId,
                    'pid'    => $postId,
                    'ord'    => $order,
                    'cid_up' => $collectionId,
                    'ord_up' => $order,
                ]
            );
        }
    }
}
