<?php
declare(strict_types=1);

require __DIR__ . '/../db/Connection.php';
require __DIR__ . '/../db/schema.php';
require __DIR__ . '/partials/header.php';

if (!isset($_SESSION['user'])) {
    header('Location: /login.php'); exit;
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id <= 0) {
    header('Location: /messages.php'); exit;
}

$pdo = Connection::get();
ensure_users_table($pdo);

$stmt = $pdo->prepare('SELECT id, username, created_at FROM users WHERE id = :id');
$stmt->execute([':id' => $id]);
$user = $stmt->fetch();

if (!$user) {
    require __DIR__ . '/partials/footer.php';
    http_response_code(404);
    echo '<div class="alert">User not found.</div>';
    exit;
}
?>
<div class="hero" style="padding-top:48px">
  <h1>User: <?= h($user['username']) ?></h1>
  <p class="badge">Joined: <?= h($user['created_at']) ?></p>
</div>

<div class="card" style="max-width:640px;">
  <p>აქ შეგიძლიათ დაამატოთ საჯარო ინფორმაცია ამ იუზერზე (ბიო/სტატისტიკა/ბოლო აქტივობა).</p>
  <div class="form-actions" style="justify-content:flex-start;">
    <a class="btn" href="/messages.php">Back to list</a>
    <?= (int)$user['id'] ?>">Message</a>
  </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
