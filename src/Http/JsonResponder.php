<?php
namespace App\Http;

final class JsonResponder {
    public static function ok(array $data = []): void {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data + ['ok'=>true], JSON_UNESCAPED_UNICODE);
    }
    public static function error(int $code, string $msg, array $extra = []): void {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok'=>false,'error'=>$msg] + $extra, JSON_UNESCAPED_UNICODE);
    }
}
