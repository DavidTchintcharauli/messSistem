<?php
declare(strict_types=1);

require __DIR__ . '/../db/Connection.php';
require __DIR__ . '/../db/schema.php';
require __DIR__ . '/../db/users.php';
require __DIR__ . '/partials/header.php';

$pdo = Connection::get();
ensure_users_table($pdo);

$err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $p = (string)($_POST['password'] ?? '');
    try {
        $id = create_user($pdo, $u, $p);
        $_SESSION['user'] = ['id'=>$id, 'username'=>$u];
        header('Location: /signalRConnection/public//dashboard.php'); exit;
    } catch (Throwable $e) {
        $err = 'Registration failed: ' . (str_contains($e->getMessage(), 'Duplicate') ? 'username already taken' : 'check inputs');
    }
}
?>
<div class="card" style="max-width:560px;margin:32px auto;">
  <h2 style="margin-top:0">Create account</h2>
  <p class="badge">Password min 8 chars</p>
  <?php if ($err): ?><div class="alert"><?= h($err) ?></div><?php endif; ?>
  <form method="post" autocomplete="off">
    <label class="label">Username</label>
    <input class="input" name="username" required maxlength="64" />
    <label class="label">Password</label>
    <input class="input" name="password" type="password" required minlength="8" />
    <div class="form-actions">
      <a class="btn" href="/signalRConnection/public">Cancel</a>
      <button class="btn success" type="submit">Register</button>
    </div>
  </form>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
