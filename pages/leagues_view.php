<?php
/**
 * UCID: lm64 | Date: 10/08/2025
 * Details: view league details
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_login();

$id = (int)($_GET['id'] ?? 0);
if ($id < 1) { flash('error','Invalid league id.'); redirect('/pages/leagues_list.php'); }

try {
    $stmt = db()->prepare('SELECT * FROM leagues WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        flash('error','League not found.');
        redirect('/pages/leagues_list.php');
    }
} catch (Throwable $e) {
    error_log('leagues_view: ' . $e->getMessage());
    flash('error','We could not load this league right now.');
    redirect('/pages/leagues_list.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>League Details</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/styles.css">
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
  <h1><?= e($row['name']) ?></h1>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
    <div><strong>Type:</strong> <?= e($row['type'] ?? '—') ?></div>
    <div><strong>Country:</strong> <?= e($row['country'] ?? '—') ?></div>
    <div><strong>Current Season:</strong> <?= !empty($row['season_current']) ? 'Yes' : 'No' ?></div>
    <div><strong>Source:</strong> <?= !empty($row['is_api']) ? 'API' : 'Manual' ?></div>
    <div><strong>API League ID:</strong> <?= e($row['api_league_id'] ?? '—') ?></div>
    <div><strong>API Last Fetched:</strong> <?= e($row['api_last_fetched'] ?? '—') ?></div>
    <div><strong>Created:</strong> <?= e($row['created']) ?></div>
    <div><strong>Modified:</strong> <?= e($row['modified']) ?></div>
  </div>

  <div style="margin-top:16px">
    <?php if (!empty($row['logo_url'])): ?>
      <img src="<?= e($row['logo_url']) ?>" alt="Logo" style="height:60px">
    <?php endif; ?>
  </div>

  <div style="margin-top:16px">
    <a href="<?= BASE_URL ?>/pages/leagues_edit.php?id=<?= (int)$row['id'] ?>">Edit</a> ·
    <a href="<?= BASE_URL ?>/pages/leagues_delete.php?id=<?= (int)$row['id'] ?>" onclick="return confirm('Delete this league? This cannot be undone.');">Delete</a> ·
    <a href="<?= BASE_URL ?>/pages/leagues_list.php">Back to list</a>
  </div>
</div>
</body>
</html>
