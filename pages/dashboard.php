<?php
/**
 * UCID: lm64d | Date: 09/08/2025
 * Details: Protected placeholder dashboard page.
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/styles.css">
</head>
<body>
<div class="nav">
  <a href="<?= BASE_URL ?>/pages/home.php">Home</a>
  <a href="<?= BASE_URL ?>/pages/dashboard.php">Dashboard</a>
  <div class="nav-right">
    <a href="<?= BASE_URL ?>/pages/profile.php">Profile</a>
    <a href="<?= BASE_URL ?>/pages/logout.php">Logout</a>
  </div>
</div>

<div class="container">
  <?php render_flash(); ?>
  <h1>Dashboard</h1>
  <p class="help">This is a protected page. We’ll expand this in later milestones.</p>
</div>
</body>
</html>
