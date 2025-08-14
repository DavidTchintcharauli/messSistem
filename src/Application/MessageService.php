<?php
namespace App\Application;

use App\Domain\MessageRepository;

final class MessageService {
    public function __construct(private MessageRepository $repo) {}

    public function send(int $from, int $to, string $body): int {
        return $this->repo->create($from, $to, $body);
    }
    public function conversation(int $me,int $peer,int $sinceId=0,int $limit=50): array {
        $rows = $this->repo->fetchConversation($me,$peer,$sinceId,$limit);
        return array_map(fn($r)=>[
            'id'=>(int)$r['id'],
            'from'=>(int)$r['from_user_id'],
            'to'=>(int)$r['to_user_id'],
            'side'=> ((int)$r['from_user_id']===$me?'me':'you'),
            'body'=>$r['body'],
            'created_at'=>$r['created_at'],
        ], $rows);
    }
}
