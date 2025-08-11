<?php
/**
 * UCID: lm64 | Date: 08/08/2025
 * Details: user registration page
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // PHP validation
    if (!preg_match('/^[A-Za-z0-9_]{3,20}$/', $username)) {
        $errors[] = 'Please enter a valid username (3–20 chars, letters/numbers/underscore).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        try {
            // Uniqueness checks
            $stmt = db()->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $errors[] = 'That username or email is already taken. Please choose another.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $ins = db()->prepare('INSERT INTO users (username, email, password) VALUES (?, ?, ?)');
                $ins->execute([$username, $email, $hash]);

                // assign "user" role
                $uid = (int)db()->lastInsertId();
                $rid = (int)db()->query("SELECT id FROM roles WHERE name='user' LIMIT 1")->fetchColumn();
                if ($rid) {
                    $ur = db()->prepare('INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, ?)');
                    $ur->execute([$uid, $rid]);
                }

                flash('success', 'Account created successfully. Please log in.');
                redirect('/pages/login.php');
            }
        } catch (Throwable $e) {
            error_log('Register error: ' . $e->getMessage());
            $errors[] = 'We could not create your account right now. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/styles.css">
  <script src="<?= BASE_URL ?>/js/scripts.js" defer></script>
</head>
<body>
<?php /* Simple Nav */ require_once __DIR__ . '/../includes/auth.php'; ?>
<div class="nav">
  <a href="<?= BASE_URL ?>/pages/home.php">Home</a>
  <div class="nav-right">
    <a href="<?= BASE_URL ?>/pages/login.php">Login</a>
  </div>
</div>

<div class="container">
  <h1>Create Account</h1>
  <?php render_flash(); ?>
  <?php if ($errors): ?>
    <div class="alert error">
      <?php foreach ($errors as $e) { echo '<div>'.e($e).'</div>'; } ?>
    </div>
  <?php endif; ?>

  <form method="post" onsubmit="return validateRegistration(this)">
    <label>Username
      <input type="text" name="username" required
             minlength="3" maxlength="20" pattern="^[A-Za-z0-9_]{3,20}$"
             value="<?= old('username') ?>">
      <div class="help">3–20 chars, letters/numbers/_</div>
    </label>

    <label>Email
      <input type="email" name="email" required value="<?= old('email') ?>">
    </label>

    <label>Password
      <input type="password" name="password" required minlength="8">
    </label>

    <label>Confirm Password
      <input type="password" name="confirm_password" required minlength="8">
    </label>

    <button type="submit">Register</button>
  </form>
</div>
</body>
</html>
