<?php
declare(strict_types=1);

require __DIR__ . '/../../../app/bootstrap.php';
require __DIR__ . '/../../../app/api.php';
require __DIR__ . '/../../../app/sse.php';
require __DIR__ . '/../../../db/messages.php';

$user = api_require_user();                
$meId = (int)$user['id'];
session_write_close();                     

sse_headers();

$pdo = app_pdo();
app_ensure_schema($pdo);

$peerId = filter_input(INPUT_GET, 'with', FILTER_VALIDATE_INT) ?: 0;
if ($peerId <= 0) { http_response_code(400); sse_error('bad peer'); exit; }

$since = 0;
if (!empty($_SERVER['HTTP_LAST_EVENT_ID'])) $since = (int)$_SERVER['HTTP_LAST_EVENT_ID'];
if (isset($_GET['sinceId'])) $since = max($since, (int)$_GET['sinceId']);

ignore_user_abort(true);
set_time_limit(0);

$timeoutSec = 300;          
$stepUs     = 250000;      
if (defined('TESTING')) {
    $timeoutSec = (int)($_GET['timeout'] ?? $timeoutSec);
    $stepUs     = (int)($_GET['stepUs']  ?? $stepUs);
}

$initial = fetch_conversation($pdo, $meId, $peerId, $since, 50);
foreach ($initial as $r) {
    $id   = (int)$r['id'];
    $side = ((int)$r['from_user_id'] === $meId) ? 'me' : 'you';
    sse_emit('message', [
        'id' => $id,
        'side' => $side,
        'body' => (string)$r['body'],
        'created_at' => (string)$r['created_at']
    ], $id);
    $since = $id;
}

$start = microtime(true);
while (!connection_aborted() && (microtime(true) - $start) < $timeoutSec) {
    $rows = fetch_conversation($pdo, $meId, $peerId, $since, 100);
    if ($rows) {
        foreach ($rows as $r) {
            $id   = (int)$r['id'];
            $side = ((int)$r['from_user_id'] === $meId) ? 'me' : 'you';
            sse_emit('message', [
                'id' => $id,
                'side' => $side,
                'body' => (string)$r['body'],
                'created_at' => (string)$r['created_at']
            ], $id);
            $since = $id;
        }
    }
    usleep($stepUs);
}

sse_ping();
