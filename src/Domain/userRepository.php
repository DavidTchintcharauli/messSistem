<?php
namespace App\Domain;

interface UserRepository {
    public function findIdByUsername(string $username): ?int;
    public function usernameExists(string $username): bool;
    public function verifyLogin(string $username, string $password): bool;
    public function create(string $username, string $password): int;
}
