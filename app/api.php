<?php
declare(strict_types=1);

function api_json_headers(): void {
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
}

function api_json(int $code, array $payload): void {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function api_ok(array $payload = []): void {
    api_json(200, ['ok' => true] + $payload);
}

function api_fail(int $code, string $error, string $detail = ''): void {
    $out = ['error' => $error];
    if ($detail !== '') $out['detail'] = $detail;
    api_json($code, $out);
}

function api_read_json(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) api_fail(400, 'bad_json');
    return $data;
}

function api_require_user(): array {
    if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
        api_fail(401, 'unauthorized');
    }
    return $_SESSION['user'];
}

function api_require_csrf(string $token): void {
    $sessionToken = (string)($_SESSION['csrf'] ?? '');
    if ($sessionToken === '' || !hash_equals($sessionToken, $token)) {
        api_fail(403, 'csrf');
    }
}
