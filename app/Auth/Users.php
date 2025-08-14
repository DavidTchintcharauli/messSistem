<?php
declare(strict_types=1);

namespace App\Auth;

use InvalidArgumentException;
use PDO;
use PDOException;

final class Users
{
    private static function len(string $s): int {
        return \function_exists('mb_strlen') ? \mb_strlen($s, 'UTF-8') : \strlen($s);
    }

    private static function algo(): string|int {
        return \defined('PASSWORD_ARGON2ID') ? \PASSWORD_ARGON2ID : \PASSWORD_DEFAULT;
    }

   
    private static function dummyHash(): string {
        static $dummy = null;
        if ($dummy !== null) return $dummy;
        $dummy = \password_hash('~dummy~password~', self::algo());
        return $dummy;
    }

    public static function create(PDO $pdo, string $username, string $password): int
    {
        $username = \trim($username);

        $ulen = self::len($username);
        if ($ulen < 1 || $ulen > 64) {
            throw new InvalidArgumentException('Invalid username length');
        }

        if (self::len($password) < 8) {
            throw new InvalidArgumentException('Password too short (min 8)');
        }

        $hash = \password_hash($password, self::algo());

        try {
            $driver = (string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

            if ($driver === 'pgsql') {
                $stmt = $pdo->prepare(
                    'INSERT INTO users (username, password_hash) VALUES (:u, :p) RETURNING id'
                );
                $stmt->execute([':u' => $username, ':p' => $hash]);
                return (int)$stmt->fetchColumn();
            }

            $stmt = $pdo->prepare('INSERT INTO users (username, password_hash) VALUES (:u, :p)');
            $stmt->execute([':u' => $username, ':p' => $hash]);
            return (int)$pdo->lastInsertId();

        } catch (PDOException $e) {
           $sqlState   = (string)($e->errorInfo[0] ?? '');
            $driverCode = (int)($e->errorInfo[1] ?? 0);
            $msg        = $e->getMessage();

            $isDuplicate =
                $sqlState === '23505' ||               // PG unique_violation
                $sqlState === '23000' ||               // generic integrity constraint
                $driverCode === 1062 ||                // MySQL duplicate key
                \str_contains($msg, 'duplicate') || \str_contains($msg, 'Duplicate');

            if ($isDuplicate) {
                throw new InvalidArgumentException('Username already taken');
            }
            throw $e;
        }
    }

   public static function verify(PDO $pdo, string $username, string $password): bool
    {
        $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE username = :u');
        $stmt->execute([':u' => $username]);
        $row  = $stmt->fetch();

       $hash = $row['password_hash'] ?? self::dummyHash();

        if (!\password_verify($password, (string)$hash)) {
            return false;
        }

        if (isset($row['password_hash']) && \password_needs_rehash((string)$row['password_hash'], self::algo())) {
            $newHash = \password_hash($password, self::algo());
            $upd = $pdo->prepare('UPDATE users SET password_hash = :h WHERE id = :id');
            $upd->execute([':h' => $newHash, ':id' => (int)$row['id']]);
        }

        return true;
    }

    public static function idByUsername(PDO $pdo, string $username): ?int {
        $st = $pdo->prepare('SELECT id FROM users WHERE username = :u');
        $st->execute([':u' => $username]);
        $id = $st->fetchColumn();
        return $id ? (int)$id : null;
    }

    public static function exists(PDO $pdo, string $username): bool {
        $st = $pdo->prepare('SELECT 1 FROM users WHERE username = :u');
        $st->execute([':u' => $username]);
        return (bool)$st->fetchColumn();
    }
}
