<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require __DIR__ . '/../db/Connection.php';
require __DIR__ . '/../db/schema.php';

function app_pdo(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $pdo = Connection::get();
    return $pdo;
}

function app_ensure_schema(PDO $pdo): void {
    ensure_users_table($pdo);
    ensure_messages_table($pdo);
}
