<?php
declare(strict_types=1);

require __DIR__ . '/../../../app/bootstrap.php';
require __DIR__ . '/../../../app/api.php';
require __DIR__ . '/../../../db/messages.php';

api_json_headers();

$user = api_require_user(); 
$meId = (int)$user['id'];

$pdo = app_pdo();
app_ensure_schema($pdo);              

$in = api_read_json();               
api_require_csrf($in['csrf'] ?? '');  

$withId = (int)($in['with'] ?? 0);
$body   = (string)($in['body'] ?? '');

try {
    $id = create_message($pdo, $meId, $withId, $body);
    api_ok(['id' => $id]);           
} catch (Throwable $e) {
    api_fail(400, 'invalid', $e->getMessage());
}
