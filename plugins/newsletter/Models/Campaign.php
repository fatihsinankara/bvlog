<?php

namespace Plugins\Newsletter\Models;

use Core\Database;
use Plugins\Newsletter\NewsletterSchema;

class Campaign
{
    public static function all(int $limit = 50): array
    {
        NewsletterSchema::ensureTables();
        return Database::fetchAll(
            'SELECT * FROM newsletter_campaigns ORDER BY id DESC LIMIT ' . max(1, (int) $limit)
        );
    }

    public static function find(int $id): ?array
    {
        NewsletterSchema::ensureTables();
        return Database::fetchOne(
            'SELECT * FROM newsletter_campaigns WHERE id = :id LIMIT 1',
            ['id' => $id]
        );
    }

    public static function create(array $data): int
    {
        NewsletterSchema::ensureTables();
        Database::query(
            'INSERT INTO newsletter_campaigns
                (subject, content_html, recipient_scope, total_recipients, sent_count, failed_count, last_error, sent_at)
             VALUES
                (:subject, :content_html, :scope, :total, :sent, :failed, :err, :sent_at)',
            [
                'subject'      => $data['subject'] ?? '',
                'content_html' => $data['content_html'] ?? '',
                'scope'        => $data['recipient_scope'] ?? 'all_active',
                'total'        => (int) ($data['total_recipients'] ?? 0),
                'sent'         => (int) ($data['sent_count'] ?? 0),
                'failed'       => (int) ($data['failed_count'] ?? 0),
                'err'          => $data['last_error'] ?? null,
                'sent_at'      => $data['sent_at'] ?? date('Y-m-d H:i:s'),
            ]
        );
        return (int) Database::lastInsertId();
    }

    public static function delete(int $id): void
    {
        NewsletterSchema::ensureTables();
        Database::query('DELETE FROM newsletter_campaigns WHERE id = :id', ['id' => $id]);
    }
}
