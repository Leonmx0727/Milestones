<?php
/**
 * UCID: lm64 | Date: 10/08/2025
 * Details: edit league
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_login();
if (!has_role('admin')) {
    flash('error','You do not have permission to access this page.');
    redirect('/pages/leagues_list.php');
}

$id = (int)($_GET['id'] ?? 0);
if ($id < 1) { flash('error','Invalid league id.'); redirect('/pages/leagues_list.php'); }

try {
    $stmt = db()->prepare('SELECT * FROM leagues WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $league = $stmt->fetch();
    if (!$league) { flash('error','League not found.'); redirect('/pages/leagues_list.php'); }
} catch (Throwable $e) {
    error_log('leagues_edit load: ' . $e->getMessage());
    flash('error','We could not load this league right now.');
    redirect('/pages/leagues_list.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name   = trim($_POST['name'] ?? '');
    $type   = trim($_POST['type'] ?? '');
    $country= trim($_POST['country'] ?? '');
    $logo   = trim($_POST['logo_url'] ?? '');
    $current= !empty($_POST['season_current']) ? 1 : 0;

    if ($name === '') $errors[] = 'League name is required.';
    if ($type !== '' && !in_array($type, ['League','Cup'], true)) $errors[] = 'Type must be League or Cup.';
    if ($logo !== '' && !preg_match('#^https?://#i', $logo)) $errors[] = 'Logo URL must start with http:// or https://';

    if (!$errors) {
        try {
            $stmt = db()->prepare('UPDATE leagues SET name = ?, type = ?, country = ?, logo_url = ?, season_current = ? WHERE id = ?');
            $stmt->execute([$name, $type ?: null, $country ?: null, $logo ?: null, $current, $id]);
            flash('success','League updated successfully.');
            redirect('/pages/leagues_edit.php?id=' . $id);
        } catch (Throwable $e) {
            error_log('leagues_edit save: ' . $e->getMessage());
            $errors[] = 'We could not update the league right now. Please try again.';
        }
    }
}

// For sticky form, prefer POST values when present
$val = fn($k) => isset($_POST[$k]) ? e($_POST[$k]) : e((string)($league[$k] ?? ''));
$checked = function ($postKey, $dbKey) use ($league) {
    if (isset($_POST[$postKey])) return !empty($_POST[$postKey]) ? 'checked' : '';
    return !empty($league[$dbKey]) ? 'checked' : '';
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit League</title>
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
  <h1>Edit League</h1>

  <?php if ($errors): ?>
    <div class="alert error"><?php foreach ($errors as $e) echo '<div>'.e($e).'</div>'; ?></div>
  <?php endif; ?>

  <form method="post" onsubmit="return validateLeagueForm(this)">
    <label>League Name *
      <input type="text" name="name" required minlength="2" maxlength="120" value="<?= $val('name') ?>">
    </label>
    <label>Type
      <select name="type">
        <?php
          $curType = isset($_POST['type']) ? $_POST['type'] : ($league['type'] ?? '');
        ?>
        <option value="">—</option>
        <option value="League" <?= ($curType==='League')?'selected':'' ?>>League</option>
        <option value="Cup"    <?= ($curType==='Cup')?'selected':'' ?>>Cup</option>
      </select>
    </label>
    <label>Country
      <input type="text" name="country" maxlength="80" value="<?= $val('country') ?>">
    </label>
    <label>Logo URL
      <input type="url" name="logo_url" maxlength="255" placeholder="https://..." value="<?= $val('logo_url') ?>">
    </label>
    <label>
      <input type="checkbox" name="season_current" <?= $checked('season_current','season_current') ?>> Current Season
    </label>

    <div style="margin-top:8px">
      <button type="submit">Save Changes</button>
      <a class="button secondary" href="<?= BASE_URL ?>/pages/leagues_view.php?id=<?= (int)$league['id'] ?>" style="margin-left:8px;display:inline-block;padding:12px 16px;background:#6c757d;color:#fff;border-radius:8px;text-decoration:none;">Cancel</a>
    </div>
  </form>
</div>
</body>
</html>
