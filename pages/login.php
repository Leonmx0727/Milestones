<?php
/**
 * UCID: lm64 | Date: 09/08/2025
 * Details: login page
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uoe = trim($_POST['username_or_email'] ?? '');
    $pw  = $_POST['password'] ?? '';

    if ($uoe === '') $errors[] = 'Please enter your username or email.';
    if ($pw === '')  $errors[] = 'Please enter your password.';

    if (!$errors) {
        try {
            $stmt = db()->prepare('SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1');
            $stmt->execute([$uoe, $uoe]);
            $user = $stmt->fetch();

            if (!$user) {
                $errors[] = 'No account found with that username/email.';
            } else if (!password_verify($pw, $user['password'])) {
                $errors[] = 'Incorrect password. Please try again.';
            } else if ((int)$user['is_active'] !== 1) {
                $errors[] = 'Your account is inactive. Please contact support.';
            } else {
                login_user($user);
                flash('success', 'Welcome back, ' . e($user['username']) . '!');
                redirect('/pages/home.php'); // protected landing
            }
        } catch (Throwable $e) {
            error_log('Login error: ' . $e->getMessage());
            $errors[] = 'We could not log you in right now. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/styles.css">
  <script src="<?= BASE_URL ?>/js/scripts.js" defer></script>
</head>
<body>
<div class="nav">
  <a href="<?= BASE_URL ?>/pages/login.php">Login</a>
  <a href="<?= BASE_URL ?>/pages/register.php">Register</a>
</div>

<div class="container">
  <h1>Login</h1>
  <?php render_flash(); ?>
  <?php if ($errors): ?>
    <div class="alert error">
      <?php foreach ($errors as $e) { echo '<div>'.e($e).'</div>'; } ?>
    </div>
  <?php endif; ?>

  <form method="post" onsubmit="return validateLogin(this)">
    <label>Username or Email
      <input type="text" name="username_or_email" required value="<?= old('username_or_email') ?>">
    </label>

    <label>Password
      <input type="password" name="password" required>
    </label>

    <button type="submit">Login</button>
  </form>
</div>
</body>
</html>
