<?php
/**
 * UCID: lm64 | Date: 10/08/2025
 * Details: create new team
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_login();
if (!has_role('admin')) { flash('error','You do not have permission to access this page.'); redirect('/pages/teams_list.php'); }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name    = trim($_POST['name'] ?? '');
  $code    = trim($_POST['code'] ?? '');
  $country = trim($_POST['country'] ?? '');
  $founded = trim($_POST['founded'] ?? '');
  $city    = trim($_POST['city'] ?? '');
  $venue   = trim($_POST['venue_name'] ?? '');
  $logo    = trim($_POST['logo_url'] ?? '');

  if ($name === '') $errors[] = 'Team name is required.';
  if ($code !== '' && !preg_match('/^[A-Za-z0-9]{2,10}$/', $code)) $errors[] = 'Code should be 2–10 letters/numbers.';
  if ($logo !== '' && !preg_match('#^https?://#i', $logo)) $errors[] = 'Logo URL must start with http:// or https://';
  if ($founded !== '' && (!preg_match('/^\d{4}$/', $founded) || (int)$founded < 1850 || (int)$founded > 2100)) $errors[] = 'Founded must be a valid 4-digit year.';

  if (!$errors) {
    try {
      $stmt = db()->prepare('INSERT INTO teams (name, code, country, founded, city, venue_name, logo_url, is_api) VALUES (?,?,?,?,?,?,?,0)');
      $stmt->execute([$name, $code ?: null, $country ?: null, $founded ?: null, $city ?: null, $venue ?: null, $logo ?: null]);
      flash('success','Team created successfully.');
      redirect('/pages/teams_list.php');
    } catch (Throwable $e) { error_log('teams_create: '.$e->getMessage()); $errors[] = 'We could not create the team right now. Please try again.'; }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Create Team</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/styles.css">
  <script src="<?= BASE_URL ?>/js/scripts.js" defer></script>
</head>
<body>
<div class="nav">
  <a href="<?= BASE_URL ?>/pages/teams_list.php">Teams</a>
  <div class="nav-right"><a href="<?= BASE_URL ?>/pages/logout.php">Logout</a></div>
</div>

<div class="container">
  <?php render_flash(); ?>
  <h1>Create Team</h1>

  <?php if ($errors): ?><div class="alert error"><?php foreach ($errors as $e) echo '<div>'.e($e).'</div>'; ?></div><?php endif; ?>

  <form method="post" onsubmit="return validateTeamForm(this)">
    <label>Team Name *
      <input type="text" name="name" required minlength="2" maxlength="120" value="<?= old('name') ?>">
    </label>
    <label>Code
      <input type="text" name="code" maxlength="10" value="<?= old('code') ?>" placeholder="e.g., MCI">
    </label>
    <label>Country
      <input type="text" name="country" maxlength="80" value="<?= old('country') ?>">
    </label>
    <label>Founded (YYYY)
      <input type="number" name="founded" min="1850" max="2100" value="<?= old('founded') ?>">
    </label>
    <label>City
      <input type="text" name="city" maxlength="120" value="<?= old('city') ?>">
    </label>
    <label>Venue Name
      <input type="text" name="venue_name" maxlength="150" value="<?= old('venue_name') ?>">
    </label>
    <label>Logo URL
      <input type="url" name="logo_url" maxlength="255" placeholder="https://..." value="<?= old('logo_url') ?>">
    </label>

    <div>
      <button type="submit">Create</button>
      <a class="button secondary" href="<?= BASE_URL ?>/pages/teams_list.php" style="margin-left:8px;display:inline-block;padding:12px 16px;background:#6c757d;color:#fff;border-radius:8px;text-decoration:none;">Cancel</a>
    </div>
  </form>
</div>
</body>
</html>
