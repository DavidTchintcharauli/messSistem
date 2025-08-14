<?php
namespace App\Infra;

use App\Domain\MessageRepository;
use PDO, InvalidArgumentException;

final class PDOMessageRepository implements MessageRepository {
    public function __construct(private PDO $pdo) {}

    public function create(int $from, int $to, string $body): int {
        $body = trim($body);
        if ($from <= 0 || $to <= 0 || $from === $to) throw new InvalidArgumentException('Invalid participants');
        $len = function_exists('mb_strlen') ? mb_strlen($body,'UTF-8') : strlen($body);
        if ($body === '' || $len > 2000) throw new InvalidArgumentException('Message must be 1..2000 chars');

        $st = $this->pdo->prepare('SELECT 1 FROM users WHERE id=:id');
        $st->execute([':id'=>$to]);
        if (!$st->fetchColumn()) throw new InvalidArgumentException('Recipient not found');

        $st = $this->pdo->prepare('INSERT INTO messages (from_user_id, to_user_id, body) VALUES (:f,:t,:b)');
        $st->execute([':f'=>$from, ':t'=>$to, ':b'=>$body]);
        return (int)$this->pdo->lastInsertId();
    }

    public function fetchConversation(int $me, int $peer, int $sinceId=0, int $limit=50): array {
        $limit = max(1, min($limit, 200));
        if ($sinceId > 0) {
            $sql = 'SELECT id, from_user_id, to_user_id, body, created_at
                    FROM messages
                    WHERE ((from_user_id=:me1 AND to_user_id=:p1)
                        OR  (from_user_id=:p2  AND to_user_id=:me2))
                      AND id > :since
                    ORDER BY id ASC
                    LIMIT '.$limit;
            $st = $this->pdo->prepare($sql);
            $st->execute([':me1'=>$me, ':p1'=>$peer, ':p2'=>$peer, ':me2'=>$me, ':since'=>$sinceId]);
        } else {
            $sql = 'SELECT id, from_user_id, to_user_id, body, created_at
                    FROM messages
                    WHERE (from_user_id=:me1 AND to_user_id=:p1)
                       OR (from_user_id=:p2 AND to_user_id=:me2)
                    ORDER BY id ASC
                    LIMIT '.$limit;
            $st = $this->pdo->prepare($sql);
            $st->execute([':me1'=>$me, ':p1'=>$peer, ':p2'=>$peer, ':me2'=>$me]);
        }
        return $st->fetchAll() ?: [];
    }

    public function fetchInbox(int $me, int $sinceId=0, int $limit=50): array {
        $limit = max(1, min($limit, 200));
        if ($sinceId > 0) {
            $st = $this->pdo->prepare('SELECT id, from_user_id, to_user_id, body, created_at
                                       FROM messages WHERE to_user_id=:me AND id > :since
                                       ORDER BY id ASC LIMIT '.$limit);
            $st->execute([':me'=>$me, ':since'=>$sinceId]);
        } else {
            $st = $this->pdo->prepare('SELECT id, from_user_id, to_user_id, body, created_at
                                       FROM messages WHERE to_user_id=:me
                                       ORDER BY id ASC LIMIT '.$limit);
            $st->execute([':me'=>$me]);
        }
        return $st->fetchAll() ?: [];
    }

    public function fetchReceivedFrom(int $me, int $from, int $sinceId=0, int $limit=50): array {
        $limit = max(1, min($limit, 200));
        if ($sinceId > 0) {
            $st = $this->pdo->prepare('SELECT id, from_user_id, to_user_id, body, created_at
                                       FROM messages WHERE to_user_id=:me AND from_user_id=:f AND id > :since
                                       ORDER BY id ASC LIMIT '.$limit);
            $st->execute([':me'=>$me, ':f'=>$from, ':since'=>$sinceId]);
        } else {
            $st = $this->pdo->prepare('SELECT id, from_user_id, to_user_id, body, created_at
                                       FROM messages WHERE to_user_id=:me AND from_user_id=:f
                                       ORDER BY id ASC LIMIT '.$limit);
            $st->execute([':me'=>$me, ':f'=>$from]);
        }
        return $st->fetchAll() ?: [];
    }
}
