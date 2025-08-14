<?php
declare(strict_types=1);

require __DIR__ . '/../app/Auth/Users.php';

use App\Auth\Users;

function create_user(PDO $pdo, string $username, string $password): int {
    return Users::create($pdo, $username, $password);
}

function verify_login(PDO $pdo, string $username, string $password): bool {
    return Users::verify($pdo, $username, $password);
}

function get_user_id_by_username(PDO $pdo, string $username): ?int {
    return Users::idByUsername($pdo, $username);
}

function user_exists(PDO $pdo, string $username): bool {
    return Users::exists($pdo, $username);
}
