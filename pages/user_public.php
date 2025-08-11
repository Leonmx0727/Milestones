<?php
// UCID: LM64 | Date: 11/08/2025
// Minimal read-only public profile (for admin association pages' username links)

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_login(); // keep it simple: only logged-in users can view

$u = trim($_GET['u'] ?? '');
if ($u === '') { flash('error','Missing username.'); redirect('/pages/home.php'); }

try {
    $stmt = db()->prepare('SELECT id, username, created FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$u]);
    $row = $stmt->fetch();
    if (!$row) { flash('error','User not found.'); redirect('/pages/home.php'); }
} catch (Throwable $e) {
    error_log('user_public: ' . $e->getMessage());
    flash('error','Could not load user profile.');
    redirect('/pages/home.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>User • <?= e($row['username']) ?></title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/styles.css">
</head>
<body>
<?php render_navbar('profile'); ?>
<div class="container">
  <?php render_flash(); ?>
  <h1>@<?= e($row['username']) ?></h1>
  <div><strong>Member since:</strong> <?= e($row['created']) ?></div>
</div>
</body>
</html>
