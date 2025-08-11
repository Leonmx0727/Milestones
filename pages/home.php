<?php
/**
 * UCID: lm64 | Date: 08/08/2025
 * Details: Protected landing page after login. Simple nav and welcome.
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Home</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/styles.css">
</head>
<body>
<?php render_navbar('home'); ?>

<div class="container">
  <?php render_flash(); ?>
  <h1>Welcome, <?= e($user['username']) ?>!</h1>
  <p class="help">You’re logged in. Use the navigation to view your profile or logout.</p>
</div>
</body>
</html>
