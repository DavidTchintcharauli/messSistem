<?php
namespace App\Security;

final class Csrf {
    public static function issue(): string {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $t = bin2hex(random_bytes(16));
        $_SESSION['csrf'] = $t;
        return $t;
    }
    public static function check(string $token): bool {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
    }
}
