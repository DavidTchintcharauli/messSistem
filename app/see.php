<?php
declare(strict_types=1);

function sse_headers(): void {
    header('Content-Type: text/event-stream; charset=utf-8');
    header('Cache-Control: no-cache, no-transform');
    header('X-Accel-Buffering: no');  
    @ini_set('zlib.output_compression', '0');
}

function sse_emit(string $event, array $data, ?int $id = null): void {
    if ($id !== null) { echo "id: {$id}\n"; }
    echo "event: {$event}\n";
    echo 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
    @ob_flush(); flush();
}

function sse_error(string $message): void {
    echo "event: error\n";
    echo "data: {$message}\n\n";
    @ob_flush(); flush();
}

function sse_ping(): void {
    echo "event: ping\ndata: {}\n\n";
    @ob_flush(); flush();
}
