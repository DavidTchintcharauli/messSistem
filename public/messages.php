<?php
declare(strict_types=1);

require __DIR__ . '/../db/Connection.php';
require __DIR__ . '/../db/schema.php';
require __DIR__ . '/partials/header.php';

if (!isset($_SESSION['user'])) {
    header('Location: /login.php'); exit;
}

$pdo = Connection::get();
ensure_users_table($pdo);

$meId = (int)$_SESSION['user']['id'];
$q = trim($_GET['q'] ?? '');

if ($q !== '') {
    $stmt = $pdo->prepare(
        'SELECT id, username, created_at
           FROM users
          WHERE id <> :me AND username LIKE :q
          ORDER BY username ASC
          LIMIT 100'
    );
    $stmt->execute([':me' => $meId, ':q' => "%{$q}%"]);
} else {
    $stmt = $pdo->prepare(
        'SELECT id, username, created_at
           FROM users
          WHERE id <> :me
          ORDER BY created_at DESC
          LIMIT 100'
    );
    $stmt->execute([':me' => $meId]);
}

$users = $stmt->fetchAll() ?: [];
?>
<div class="hero" style="padding-top:48px">
  <h1>Messages overview</h1>
  <p>აიხედე სხვა იუზერების პროფილები ან დაიწყე საუბარი (შემდეგ დავამატებთ რეალურ ჩატს).</p>
</div>

<div class="card" style="margin-bottom:16px;">
  <form method="get" style="display:flex; gap:8px; align-items:center;">
    <input class="input" name="q" placeholder="Search username…" value="<?= h($q) ?>" />
    <button class="btn" type="submit">Search</button>
    <?php if ($q !== ''): ?>
      <a class="btn" href="/messages.php">Clear</a>
    <?php endif; ?>
  </form>
</div>

<?php if (!$users): ?>
  <div class="alert">ვერ მოიძებნა მომხმარებელი.</div>
<?php else: ?>
  <div class="grid">
    <?php foreach ($users as $u): ?>
      <div class="card">
        <div style="display:flex; align-items:center; justify-content:space-between;">
          <div>
            <div style="font-weight:700;"><?= h($u['username']) ?></div>
            <div class="badge">joined: <?= h($u['created_at']) ?></div>
          </div>
          <div style="display:flex; gap:8px;">
            <a class="btn" href="<?= h($BASE_PATH) ?>/user.php?id=<?= (int)$u['id'] ?>">View</a>
            <a class="btn primary" href="<?= h($BASE_PATH) ?>/chat.php?with=<?= (int)$u['id'] ?>">Chat</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
