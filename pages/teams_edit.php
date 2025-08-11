<?php
/**
 * UCID: lm64 | Date: 10/08/2025
 * Details: edit team page
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_login();
if (!has_role('admin')) { flash('error','You do not have permission to access this page.'); redirect('/pages/teams_list.php'); }

$id = (int)($_GET['id'] ?? 0);
if ($id < 1) { flash('error','Invalid team id.'); redirect('/pages/teams_list.php'); }

try {
  $stmt = db()->prepare('SELECT * FROM teams WHERE id = ? LIMIT 1');
  $stmt->execute([$id]);
  $team = $stmt->fetch();
  if (!$team) { flash('error','Team not found.'); redirect('/pages/teams_list.php'); }
} catch (Throwable $e) {
  error_log('teams_edit load: '.$e->getMessage());
  flash('error','We could not load this team right now.'); redirect('/pages/teams_list.php');
}

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
      $stmt = db()->prepare('UPDATE teams SET name=?, code=?, country=?, founded=?, city=?, venue_name=?, logo_url=? WHERE id=?');
      $stmt->execute([$name, $code ?: null, $country ?: null, $founded ?: null, $city ?: null, $venue ?: null, $logo ?: null, $id]);
      flash('success','Team updated successfully.');
      redirect('/pages/teams_edit.php?id='.$id);
    } catch (Throwable $e) { error_log('teams_edit save: '.$e->getMessage()); $errors[] = 'We could not update the team right now. Please try again.'; }
  }
}

$val = fn($k) => isset($_POST[$k]) ? e($_POST[$k]) : e((string)($team[$k] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Team</title>
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
  <h1>Edit Team</h1>

  <?php if ($errors): ?><div class="alert error"><?php foreach ($errors as $e) echo '<div>'.e($e).'</div>'; ?></div><?php endif; ?>

  <form method="post" onsubmit="return validateTeamForm(this)">
    <label>Team Name *
      <input type="text" name="name" required minlength="2" maxlength="120" value="<?= $val('name') ?>">
    </label>
    <label>Code
      <input type="text" name="code" maxlength="10" value="<?= $val('code') ?>" placeholder="e.g., MCI">
    </label>
    <label>Country
      <input type="text" name="country" maxlength="80" value="<?= $val('country') ?>">
    </label>
    <label>Founded (YYYY)
      <input type="number" name="founded" min="1850" max="2100" value="<?= $val('founded') ?>">
    </label>
    <label>City
      <input type="text" name="city" maxlength="120" value="<?= $val('city') ?>">
    </label>
    <label>Venue Name
      <input type="text" name="venue_name" maxlength="150" value="<?= $val('venue_name') ?>">
    </label>
    <label>Logo URL
      <input type="url" name="logo_url" maxlength="255" placeholder="https://..." value="<?= $val('logo_url') ?>">
    </label>

    <div style="margin-top:8px">
      <button type="submit">Save Changes</button>
      <a class="button secondary" href="<?= BASE_URL ?>/pages/teams_view.php?id=<?= (int)$team['id'] ?>" style="margin-left:8px;display:inline-block;padding:12px 16px;background:#6c757d;color:#fff;border-radius:8px;text-decoration:none;">Cancel</a>
    </div>
  </form>
</div>
</body>
</html>
