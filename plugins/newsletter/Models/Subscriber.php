<?php

namespace Plugins\Newsletter\Models;

use Core\Database;
use Plugins\Newsletter\NewsletterSchema;

class Subscriber
{
    public static function find(int $id): ?array
    {
        NewsletterSchema::ensureTables();
        return Database::fetchOne('SELECT * FROM newsletter_subscribers WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    public static function findByEmail(string $email): ?array
    {
        NewsletterSchema::ensureTables();
        return Database::fetchOne(
            'SELECT * FROM newsletter_subscribers WHERE email = :email LIMIT 1',
            ['email' => strtolower(trim($email))]
        );
    }

    public static function findByUnsubscribeToken(string $token): ?array
    {
        NewsletterSchema::ensureTables();
        if ($token === '') {
            return null;
        }
        return Database::fetchOne(
            'SELECT * FROM newsletter_subscribers WHERE unsubscribe_token = :token LIMIT 1',
            ['token' => $token]
        );
    }

    public static function findByVerifyToken(string $token): ?array
    {
        NewsletterSchema::ensureTables();
        if ($token === '') {
            return null;
        }
        return Database::fetchOne(
            'SELECT * FROM newsletter_subscribers WHERE verify_token = :token LIMIT 1',
            ['token' => $token]
        );
    }

    public static function getStats(): array
    {
        NewsletterSchema::ensureTables();
        $rows = Database::fetchAll(
            'SELECT status, COUNT(*) AS cnt FROM newsletter_subscribers GROUP BY status'
        );
        $stats = [
            'total'        => 0,
            'active'       => 0,
            'pending'      => 0,
            'unsubscribed' => 0,
        ];
        foreach ($rows as $r) {
            $st = (string) ($r['status'] ?? '');
            $c = (int) ($r['cnt'] ?? 0);
            if (isset($stats[$st])) {
                $stats[$st] = $c;
            }
            $stats['total'] += $c;
        }
        return $stats;
    }

    public static function listSubscribers(string $statusFilter = '', string $search = ''): array
    {
        NewsletterSchema::ensureTables();
        $where = [];
        $params = [];

        if (in_array($statusFilter, ['active', 'pending', 'unsubscribed'], true)) {
            $where[] = 'status = :status';
            $params['status'] = $statusFilter;
        }

        $search = trim($search);
        if ($search !== '') {
            $where[] = '(email LIKE :q OR name LIKE :q2)';
            $params['q'] = '%' . $search . '%';
            $params['q2'] = '%' . $search . '%';
        }

        $sql = 'SELECT * FROM newsletter_subscribers';
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY created_at DESC, id DESC';

        return Database::fetchAll($sql, $params);
    }

    public static function getActiveSubscribers(?array $onlyIds = null): array
    {
        NewsletterSchema::ensureTables();
        if (is_array($onlyIds)) {
            $ids = array_values(array_unique(array_filter(array_map('intval', $onlyIds), fn($v) => $v > 0)));
            if (empty($ids)) {
                return [];
            }
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = Database::getInstance()->prepare(
                "SELECT * FROM newsletter_subscribers WHERE status = 'active' AND id IN ({$placeholders}) ORDER BY id ASC"
            );
            $stmt->execute($ids);
            return $stmt->fetchAll();
        }

        return Database::fetchAll(
            "SELECT * FROM newsletter_subscribers WHERE status = 'active' ORDER BY id ASC"
        );
    }

    /**
     * Yeni abone kaydeder veya daha önce çıkmış/bekleyen aboneyi günceller.
     *
     * @return array ['subscriber' => array, 'is_new' => bool, 'was_active' => bool]
     */
    public static function subscribeOrUpdate(
        string $email,
        ?string $name,
        string $status,
        string $ip,
        string $consentTextHash
    ): array {
        NewsletterSchema::ensureTables();
        $email = strtolower(trim($email));
        $name = $name !== null && trim($name) !== '' ? mb_substr(trim($name), 0, 120) : null;
        $now = date('Y-m-d H:i:s');

        $existing = self::findByEmail($email);
        if ($existing) {
            $wasActive = ($existing['status'] === 'active');
            $verifyToken = $existing['verify_token'] ?: bin2hex(random_bytes(24));
            $unsubToken = $existing['unsubscribe_token'] ?: bin2hex(random_bytes(24));

            if ($wasActive) {
                if ($name !== null && empty($existing['name'])) {
                    Database::query(
                        'UPDATE newsletter_subscribers SET name = :name WHERE id = :id',
                        ['name' => $name, 'id' => $existing['id']]
                    );
                    $existing['name'] = $name;
                }
                return [
                    'subscriber' => $existing,
                    'is_new'     => false,
                    'was_active' => true,
                ];
            }

            // Daha önce ayrılmış (unsubscribed) veya onay bekleyen (pending) aboneyi yeniden güncelle
            $newVerifyToken = bin2hex(random_bytes(24));
            Database::query(
                'UPDATE newsletter_subscribers
                 SET name = COALESCE(:name, name),
                     status = :status,
                     verify_token = :vtoken,
                     unsubscribe_token = :utoken,
                     consent_approved = 1,
                     consent_at = :cat,
                     consent_ip = :cip,
                     consent_text_hash = :chash,
                     verified_at = :vat,
                     verified_ip = :vip,
                     unsubscribed_at = NULL,
                     unsubscribed_ip = NULL
                 WHERE id = :id',
                [
                    'name'   => $name,
                    'status' => $status,
                    'vtoken' => $newVerifyToken,
                    'utoken' => $unsubToken,
                    'cat'    => $now,
                    'cip'    => $ip,
                    'chash'  => $consentTextHash,
                    'vat'    => $status === 'active' ? $now : null,
                    'vip'    => $status === 'active' ? $ip : null,
                    'id'     => $existing['id'],
                ]
            );

            return [
                'subscriber' => self::find((int) $existing['id']) ?? $existing,
                'is_new'     => false,
                'was_active' => false,
            ];
        }

        $verifyToken = bin2hex(random_bytes(24));
        $unsubToken = bin2hex(random_bytes(24));

        Database::query(
            'INSERT INTO newsletter_subscribers
                (email, name, status, verify_token, unsubscribe_token, consent_approved, consent_at, consent_ip, consent_text_hash, verified_at, verified_ip)
             VALUES
                (:email, :name, :status, :vtoken, :utoken, 1, :cat, :cip, :chash, :vat, :vip)',
            [
                'email'  => $email,
                'name'   => $name,
                'status' => $status,
                'vtoken' => $verifyToken,
                'utoken' => $unsubToken,
                'cat'    => $now,
                'cip'    => $ip,
                'chash'  => $consentTextHash,
                'vat'    => $status === 'active' ? $now : null,
                'vip'    => $status === 'active' ? $ip : null,
            ]
        );

        $id = (int) Database::lastInsertId();
        return [
            'subscriber' => self::find($id) ?? [],
            'is_new'     => true,
            'was_active' => false,
        ];
    }

    public static function markVerified(int $id, string $ip): void
    {
        NewsletterSchema::ensureTables();
        Database::query(
            "UPDATE newsletter_subscribers
             SET status = 'active', verified_at = :vat, verified_ip = :vip, unsubscribed_at = NULL
             WHERE id = :id",
            [
                'vat' => date('Y-m-d H:i:s'),
                'vip' => $ip,
                'id'  => $id,
            ]
        );
    }

    public static function markUnsubscribed(int $id, string $ip): void
    {
        NewsletterSchema::ensureTables();
        Database::query(
            "UPDATE newsletter_subscribers
             SET status = 'unsubscribed', unsubscribed_at = :uat, unsubscribed_ip = :uip
             WHERE id = :id",
            [
                'uat' => date('Y-m-d H:i:s'),
                'uip' => $ip,
                'id'  => $id,
            ]
        );
    }

    public static function updateStatus(int $id, string $status): void
    {
        NewsletterSchema::ensureTables();
        if (!in_array($status, ['active', 'pending', 'unsubscribed'], true)) {
            return;
        }
        $now = date('Y-m-d H:i:s');
        if ($status === 'unsubscribed') {
            Database::query(
                'UPDATE newsletter_subscribers SET status = :st, unsubscribed_at = :uat WHERE id = :id',
                ['st' => $status, 'uat' => $now, 'id' => $id]
            );
        } elseif ($status === 'active') {
            Database::query(
                'UPDATE newsletter_subscribers SET status = :st, verified_at = COALESCE(verified_at, :vat), unsubscribed_at = NULL WHERE id = :id',
                ['st' => $status, 'vat' => $now, 'id' => $id]
            );
        } else {
            Database::query(
                'UPDATE newsletter_subscribers SET status = :st WHERE id = :id',
                ['st' => $status, 'id' => $id]
            );
        }
    }

    public static function delete(int $id): void
    {
        NewsletterSchema::ensureTables();
        Database::query('DELETE FROM newsletter_subscribers WHERE id = :id', ['id' => $id]);
    }
}
