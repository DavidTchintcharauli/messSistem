<?php
namespace App\Domain;

interface MessageRepository {
    public function create(int $from, int $to, string $body): int;
    public function fetchConversation(int $me, int $peer, int $sinceId=0, int $limit=50): array;
    public function fetchInbox(int $me, int $sinceId=0, int $limit=50): array;
    public function fetchReceivedFrom(int $me, int $from, int $sinceId=0, int $limit=50): array;
}
