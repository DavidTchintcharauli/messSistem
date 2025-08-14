<?php
declare(strict_types=1);
require __DIR__ . '/partials/header.php';

if (!isset($_SESSION['user'])) {
    header('Location: /login.php'); exit;
}
?>
<div class="hero" style="padding-top:48px">
  <h1>Dashboard</h1>
  <p>ავტორიზებული ხარ როგორც <b><?= h($_SESSION['user']['username']) ?></b>.</p>
</div>

<div class="grid">
  <div class="card">
    <h3>Quick actions</h3>
    <p>აქ შეგიძლია დაამატო შენი კარდები/ინფო.</p>
  </div>
  <div class="card">
    <h3>Profile</h3>
    <p>მარჯვენა ზედა კუთხეში — შენი სახელი და Logout.</p>
  </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
