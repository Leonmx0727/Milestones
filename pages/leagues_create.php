<?php
/**
 * UCID: lm64 | Date: 10/08/2025
 * Details: create new league
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_login();
if (!has_role('admin')) {
    flash('error','You do not have permission to access this page.');
    redirect('/pages/home.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name   = trim($_POST['name'] ?? '');
    $type   = trim($_POST['type'] ?? '');
    $country= trim($_POST['country'] ?? '');
    $logo   = trim($_POST['logo_url'] ?? '');
    $current= !empty($_POST['season_current']) ? 1 : 0;

    // validation
    if ($name === '') $errors[] = 'League name is required.';
    if ($type !== '' && !in_array($type, ['League','Cup'], true)) $errors[] = 'Type must be League or Cup.';
    if ($logo !== '' && !preg_match('#^https?://#i', $logo)) $errors[] = 'Logo URL must start with http:// or https://';

    if (!$errors) {
        try {
            $stmt = db()->prepare('INSERT INTO leagues (name, type, country, logo_url, season_current, is_api) VALUES (?, ?, ?, ?, ?, 0)');
            $stmt->execute([$name, $type ?: null, $country ?: null, $logo ?: null, $current]);
            flash('success','League created successfully.');
            redirect('/pages/leagues_list.php');
        } catch (Throwable $e) {
            error_log('leagues_create: ' . $e->getMessage());
            $errors[] = 'We could not create the league right now. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Create League</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/styles.css">
  <script src="<?= BASE_URL ?>/js/scripts.js" defer></script>
</head>
<body>
<div class="nav">
  <a href="<?= BASE_URL ?>/pages/leagues_list.php">Leagues</a>
  <div class="nav-right">
    <a href="<?= BASE_URL ?>/pages/logout.php">Logout</a>
  </div>
</div>

<div class="container">
  <?php render_flash(); ?>
  <h1>Create League</h1>

  <?php if ($errors): ?>
    <div class="alert error"><?php foreach ($errors as $e) echo '<div>'.e($e).'</div>'; ?></div>
  <?php endif; ?>

  <form method="post" onsubmit="return validateLeagueForm(this)">
    <label>League Name *
      <input type="text" name="name" required minlength="2" maxlength="120" value="<?= old('name') ?>">
    </label>
    <label>Type
      <select name="type">
        <option value="">—</option>
        <option value="League" <?= (($_POST['type'] ?? '')==='League')?'selected':'' ?>>League</option>
        <option value="Cup"    <?= (($_POST['type'] ?? '')==='Cup')?'selected':'' ?>>Cup</option>
      </select>
    </label>
    <label>Country
      <input type="text" name="country" maxlength="80" value="<?= old('country') ?>">
    </label>
    <label>Logo URL
      <input type="url" name="logo_url" maxlength="255" placeholder="https://..." value="<?= old('logo_url') ?>">
    </label>
    <label>
      <input type="checkbox" name="season_current" <?= !empty($_POST['season_current']) ? 'checked' : '' ?>> Current Season
    </label>

    <div>
      <button type="submit">Create</button>
      <a class="button secondary" href="<?= BASE_URL ?>/pages/leagues_list.php" style="margin-left:8px;display:inline-block;padding:12px 16px;background:#6c757d;color:#fff;border-radius:8px;text-decoration:none;">Cancel</a>
    </div>
  </form>
</div>
</body>
</html>
