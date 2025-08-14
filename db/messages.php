<?php
declare(strict_types=1);

function create_message(PDO $pdo, int $fromUserId, int $toUserId, string $body): int
{
    $body = trim($body);
    if ($fromUserId <= 0 || $toUserId <= 0 || $fromUserId === $toUserId) {
        throw new InvalidArgumentException('Invalid participants');
    }
    if ($body === '' || mb_strlen($body) > 2000) {
        throw new InvalidArgumentException('Message must be 1..2000 chars');
    }

    $st = $pdo->prepare('SELECT 1 FROM users WHERE id = :id');
    $st->execute([':id' => $toUserId]);
    if (!$st->fetchColumn()) {
        throw new InvalidArgumentException('Recipient not found');
    }

    $stmt = $pdo->prepare('
        INSERT INTO messages (from_user_id, to_user_id, body)
        VALUES (:from_id, :to_id, :body)
    ');
    $stmt->execute([
        ':from_id' => $fromUserId,
        ':to_id'   => $toUserId,
        ':body'    => $body,
    ]);

    return (int)$pdo->lastInsertId();
}

function fetch_conversation(PDO $pdo, int $meId, int $peerId, int $sinceId = 0, int $limit = 50): array
{
    $limit = max(1, min($limit, 200));

    if ($sinceId > 0) {
        $sql = '
          SELECT id, from_user_id, to_user_id, body, created_at
            FROM messages
           WHERE ((from_user_id = :me1  AND to_user_id = :peer1)
               OR (from_user_id = :peer2 AND to_user_id = :me2))
             AND id > :since
           ORDER BY id ASC
           LIMIT ' . $limit;
        $st = $pdo->prepare($sql);
        $st->execute([
            ':me1'   => $meId,
            ':peer1' => $peerId,
            ':peer2' => $peerId,
            ':me2'   => $meId,
            ':since' => $sinceId,
        ]);
    } else {
        $sql = '
          SELECT id, from_user_id, to_user_id, body, created_at
            FROM messages
           WHERE (from_user_id = :me1  AND to_user_id = :peer1)
              OR (from_user_id = :peer2 AND to_user_id = :me2)
           ORDER BY id ASC
           LIMIT ' . $limit;
        $st = $pdo->prepare($sql);
        $st->execute([
            ':me1'   => $meId,
            ':peer1' => $peerId,
            ':peer2' => $peerId,
            ':me2'   => $meId,
        ]);
    }

    return $st->fetchAll() ?: [];
}

function fetch_inbox(PDO $pdo, int $meId, int $sinceId = 0, int $limit = 50): array
{
    $limit = max(1, min($limit, 200));
    if ($sinceId > 0) {
        $sql = 'SELECT id, from_user_id, to_user_id, body, created_at
                  FROM messages
                 WHERE to_user_id = :me AND id > :since
                 ORDER BY id ASC
                 LIMIT ' . $limit;
        $st = $pdo->prepare($sql);
        $st->execute([':me'=>$meId, ':since'=>$sinceId]);
    } else {
        $sql = 'SELECT id, from_user_id, to_user_id, body, created_at
                  FROM messages
                 WHERE to_user_id = :me
                 ORDER BY id ASC
                 LIMIT ' . $limit;
        $st = $pdo->prepare($sql);
        $st->execute([':me'=>$meId]);
    }
    return $st->fetchAll() ?: [];
}

function fetch_received_from(PDO $pdo, int $meId, int $fromId, int $sinceId = 0, int $limit = 50): array
{
    $limit = max(1, min($limit, 200));
    if ($sinceId > 0) {
        $sql = 'SELECT id, from_user_id, to_user_id, body, created_at
                  FROM messages
                 WHERE to_user_id = :me AND from_user_id = :from AND id > :since
                 ORDER BY id ASC
                 LIMIT ' . $limit;
        $st = $pdo->prepare($sql);
        $st->execute([':me'=>$meId, ':from'=>$fromId, ':since'=>$sinceId]);
    } else {
        $sql = 'SELECT id, from_user_id, to_user_id, body, created_at
                  FROM messages
                 WHERE to_user_id = :me AND from_user_id = :from
                 ORDER BY id ASC
                 LIMIT ' . $limit;
        $st = $pdo->prepare($sql);
        $st->execute([':me'=>$meId, ':from'=>$fromId]);
    }
    return $st->fetchAll() ?: [];
}