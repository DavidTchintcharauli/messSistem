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

$since  = filter_input(INPUT_GET, 'sinceId', FILTER_VALIDATE_INT) ?: 0;
$limitQ = filter_input(INPUT_GET, 'limit',   FILTER_VALIDATE_INT);
$limit  = ($limitQ !== null && $limitQ > 0) ? min($limitQ, 200) : 50;

$rows = fetch_inbox($pdo, $meId, (int)$since, (int)$limit);

$out = array_map(static function(array $r) {
    return [
        'id'         => (int)$r['id'],
        'from'       => (int)$r['from_user_id'],
        'to'         => (int)$r['to_user_id'],
        'body'       => (string)$r['body'],
        'created_at' => (string)$r['created_at'],
    ];
}, $rows);

api_ok(['messages' => $out]);
