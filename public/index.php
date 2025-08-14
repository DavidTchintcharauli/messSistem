<?php
declare(strict_types=1);
require __DIR__ . '/../db/Connection.php';
require __DIR__ . '/../db/schema.php';
require __DIR__ . '/partials/header.php';

$pdo = Connection::get();
ensure_users_table($pdo);

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<?php if ($flash): ?>
  <div class="alert"><?= h($flash) ?></div>
<?php endif; ?>

<section class="hero">
  <h1>Welcome</h1>
  <p>ეს არის მინიმალური PHP აპი. თუ არ ხარ ავტორიზებული, დააჭირე <b>Register</b> ან <b>Login</b>.
     დალოგინების შემდეგ გადახვალ Dashboard-ზე და მარჯვენა ზედა კუთხეში გამოჩნდება შენი სახელი.</p>
</section>

<div class="grid">
  <div class="card">
    <h3>What you can do</h3>
    <p>დარეგისტრირდი, მერე დალოგინდი და ნახავ დეშბორდს.</p>
  </div>
  <div class="card">
    <h3>Tech</h3>
    <p>Vanilla PHP + PDO + Password hashing. CSS ხელით.</p>
  </div>
</div>

<form method="post" action="/seed.php" class="form-actions" style="margin-top:16px">
  <button class="btn success" type="submit">Seed demo user</button>
</form>

<?php require __DIR__ . '/partials/footer.php'; ?>
