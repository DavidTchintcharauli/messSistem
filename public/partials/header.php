<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');


$__dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
$BASE_PATH = rtrim($__dir, '/');
if ($BASE_PATH === '/' || $BASE_PATH === '.' ) { $BASE_PATH = ''; }


$currentUser = $_SESSION['user'] ?? null;

function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="ka">
  <head>
    <meta charset="utf-8">
    <title>MiniApp</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="<?= h($BASE_PATH) ?>/assets/css/styles.css" rel="stylesheet">
  </head>
  <body>
    <nav class="nav">
      <div class="brand">MiniApp</div>
      <div class="nav-right">
        <?php if ($currentUser): ?>
          <span class="badge">Hello, <?= h($currentUser['username']) ?></span>
          <a class="btn" href="<?= h($BASE_PATH) ?>/dashboard.php">Dashboard</a>
          <a class="btn" href="<?= h($BASE_PATH) ?>/messages.php">Messages</a>
          <a class="btn danger" href="<?= h($BASE_PATH) ?>/logout.php">Logout</a>
        <?php else: ?>
          <a class="btn" href="<?= h($BASE_PATH) ?>/login.php">Login</a>
          <a class="btn primary" href="<?= h($BASE_PATH) ?>/register.php">Register</a>
        <?php endif; ?>
      </div>
    </nav>
    <div class="container">
