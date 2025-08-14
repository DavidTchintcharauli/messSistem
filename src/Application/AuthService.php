<?php
namespace App\Application;

use App\Domain\UserRepository;

final class AuthService {
    public function __construct(private UserRepository $users) {}
    
    public function attempt(array $post): array {
        $u = trim((string)($post['username'] ?? ''));
        $p = (string)($post['password'] ?? '');
        if ($u === '' || $p === '') return ['ok'=>false,'error'=>'Fill all fields'];
        if (!$this->users->verifyLogin($u, $p)) return ['ok'=>false,'error'=>'Invalid credentials'];

        $id = $this->users->findIdByUsername($u) ?? 0;
        return ['ok'=>true, 'user'=>['id'=>$id,'username'=>$u], 'redirect'=>'/signalRConnection/public/dashboard.php'];
    }
}
