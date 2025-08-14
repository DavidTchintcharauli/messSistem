<?php
namespace App\Infra;

use App\Domain\UserRepository;
use PDO;

final class PDOUserRepository implements UserRepository {
    public function __construct(private PDO $pdo) {}

    public function findIdByUsername(string $username): ?int {
        $st = $this->pdo->prepare('SELECT id FROM users WHERE username=:u');
        $st->execute([':u'=>$username]);
        $id = $st->fetchColumn();
        return $id ? (int)$id : null;
    }

    public function usernameExists(string $username): bool {
        return $this->findIdByUsername($username) !== null;
    }

    public function verifyLogin(string $username, string $password): bool {
        $st = $this->pdo->prepare('SELECT password_hash FROM users WHERE username=:u');
        $st->execute([':u'=>$username]);
        $hash = $st->fetchColumn();
        return $hash ? password_verify($password, $hash) : false;
    }

    public function create(string $username, string $password): int {
        $st = $this->pdo->prepare('INSERT INTO users (username, password_hash) VALUES (:u,:h)');
        $st->execute([':u'=>$username, ':h'=>password_hash($password, PASSWORD_DEFAULT)]);
        return (int)$this->pdo->lastInsertId();
    }
}
