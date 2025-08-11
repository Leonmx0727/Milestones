<?php
/**
 * UCID: lm64d | Date: 09/08/2025
 * Details: user profile page
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_login();

$user = current_user();
$errors_account = [];
$errors_password = [];

/** Handle account update (username/email) */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_account') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');

    // PHP validation
    if (!preg_match('/^[A-Za-z0-9_]{3,20}$/', $username)) {
        $errors_account[] = 'Please enter a valid username (3–20 chars, letters/numbers/underscore).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors_account[] = 'Please enter a valid email address.';
    }

    if (!$errors_account) {
        try {
            // Check uniqueness excluding current user
            $stmt = db()->prepare('SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ? LIMIT 1');
            $stmt->execute([$username, $email, $user['id']]);
            if ($stmt->fetch()) {
                $errors_account[] = 'That username or email is already taken.';
            } else {
                $upd = db()->prepare('UPDATE users SET username = ?, email = ? WHERE id = ?');
                $upd->execute([$username, $email, $user['id']]);

                // sync session
                $_SESSION['user']['username'] = $username;
                $_SESSION['user']['email']    = $email;

                flash('success', 'Profile updated successfully.');
                redirect('/pages/profile.php');
            }
        } catch (Throwable $e) {
            error_log('Profile update error: ' . $e->getMessage());
            $errors_account[] = 'We could not update your profile right now. Please try again.';
        }
    }
}

/** Handle password change */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_new_password'] ?? '';

    if ($new !== $confirm) {
        $errors_password[] = 'New passwords do not match.';
    }
    if (strlen($new) < 8) {
        $errors_password[] = 'New password must be at least 8 characters.';
    }

    if (!$errors_password) {
        try {
            $stmt = db()->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([$user['id']]);
            $hash = $stmt->fetchColumn();

            if (!$hash || !password_verify($current, $hash)) {
                $errors_password[] = 'Current password is incorrect.';
            } else {
                $newHash = password_hash($new, PASSWORD_BCRYPT);
                $upd = db()->prepare('UPDATE users SET password = ? WHERE id = ?');
                $upd->execute([$newHash, $user['id']]);

                flash('success', 'Password changed successfully.');
                redirect('/pages/profile.php');
            }
        } catch (Throwable $e) {
            error_log('Password change error: ' . $e->getMessage());
            $errors_password[] = 'We could not change your password right now. Please try again.';
        }
    }
}

$user = current_user(); // refresh in case we updated
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Your Profile</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/styles.css">
  <script src="<?= BASE_URL ?>/js/scripts.js" defer></script>
</head>
<body>
<?php render_navbar('profile'); ?>

<div class="container">
  <?php render_flash(); ?>
  <h1>Profile</h1>

  <h2>Account Details</h2>
  <?php if ($errors_account): ?>
    <div class="alert error"><?php foreach ($errors_account as $e) echo '<div>'.e($e).'</div>'; ?></div>
  <?php endif; ?>

  <form method="post" onsubmit="return validateProfileAccount(this)">
    <input type="hidden" name="action" value="update_account">
    <label>Username
      <input type="text" name="username" required minlength="3" maxlength="20" pattern="^[A-Za-z0-9_]{3,20}$"
             value="<?= e($user['username']) ?>">
    </label>
    <label>Email
      <input type="email" name="email" required value="<?= e($user['email']) ?>">
    </label>
    <button type="submit">Save Changes</button>
  </form>

  <h2>Change Password</h2>
  <?php if ($errors_password): ?>
    <div class="alert error"><?php foreach ($errors_password as $e) echo '<div>'.e($e).'</div>'; ?></div>
  <?php endif; ?>

  <form method="post" onsubmit="return validateProfilePassword(this)">
    <input type="hidden" name="action" value="change_password">
    <label>Current Password
      <input type="password" name="current_password" required>
    </label>
    <label>New Password
      <input type="password" name="new_password" required minlength="8">
    </label>
    <label>Confirm New Password
      <input type="password" name="confirm_new_password" required minlength="8">
    </label>
    <button type="submit">Change Password</button>
  </form>
</div>
</body>
</html>
