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

$peerId = filter_input(INPUT_GET, 'with',    FILTER_VALIDATE_INT) ?: 0;
$since  = filter_input(INPUT_GET, 'sinceId', FILTER_VALIDATE_INT) ?: 0;
$limitQ = filter_input(INPUT_GET, 'limit',   FILTER_VALIDATE_INT);
$limit  = ($limitQ !== null && $limitQ > 0) ? min($limitQ, 200) : 50;

if ($peerId <= 0) {
    api_fail(400, 'bad_peer', 'with must be a positive user id');
}

$rows = fetch_conversation($pdo, $meId, (int)$peerId, (int)$since, (int)$limit);

$out = array_map(function(array $r) use ($meId) {
    $from = (int)$r['from_user_id'];
    return [
        'id'         => (int)$r['id'],
        'from'       => $from,
        'to'         => (int)$r['to_user_id'],
        'body'       => (string)$r['body'],
        'created_at' => (string)$r['created_at'],
        'side'       => ($from === $meId ? 'me' : 'you'),
    ];
}, $rows);

api_ok(['messages' => $out]);
