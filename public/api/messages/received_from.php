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

$fromId = filter_input(INPUT_GET, 'from',    FILTER_VALIDATE_INT) ?: 0;
$since  = filter_input(INPUT_GET, 'sinceId', FILTER_VALIDATE_INT) ?: 0;
$limit  = filter_input(INPUT_GET, 'limit',   FILTER_VALIDATE_INT);
$limit  = ($limit !== null && $limit > 0) ? min($limit, 200) : 50;

if ($fromId <= 0) {
    api_fail(400, 'bad_from', 'from must be a positive user id');
}

$rows = fetch_received_from($pdo, $meId, (int)$fromId, (int)$since, (int)$limit);

api_ok(['messages' => $rows]);
