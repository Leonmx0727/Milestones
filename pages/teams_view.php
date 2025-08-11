<?php
/**
 * UCID: lm64 | Date: 10/08/2025
 * Details: view team details
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_login();

$id = (int)($_GET['id'] ?? 0);
if ($id < 1) { flash('error','Invalid team id.'); redirect('/pages/teams_list.php'); }

try {
  $stmt = db()->prepare('SELECT * FROM teams WHERE id = ? LIMIT 1');
  $stmt->execute([$id]);
  $row = $stmt->fetch();
  if (!$row) { flash('error','Team not found.'); redirect('/pages/teams_list.php'); }
} catch (Throwable $e) {
  error_log('teams_view: '.$e->getMessage());
  flash('error','We could not load this team right now.');
  redirect('/pages/teams_list.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Team Details</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/styles.css">
</head>
<body>
<div class="nav">
  <a href="<?= BASE_URL ?>/pages/teams_list.php">Teams</a>
  <div class="nav-right"><a href="<?= BASE_URL ?>/pages/logout.php">Logout</a></div>
</div>

<div class="container">
  <?php render_flash(); ?>
  <h1><?= e($row['name']) ?></h1>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
    <div><strong>Code:</strong> <?= e($row['code'] ?? '—') ?></div>
    <div><strong>Country:</strong> <?= e($row['country'] ?? '—') ?></div>
    <div><strong>Founded:</strong> <?= e($row['founded'] ?? '—') ?></div>
    <div><strong>City:</strong> <?= e($row['city'] ?? '—') ?></div>
    <div><strong>Venue:</strong> <?= e($row['venue_name'] ?? '—') ?></div>
    <div><strong>Source:</strong> <?= !empty($row['is_api']) ? 'API' : 'Manual' ?></div>
    <div><strong>API Team ID:</strong> <?= e($row['api_team_id'] ?? '—') ?></div>
    <div><strong>API Last Fetched:</strong> <?= e($row['api_last_fetched'] ?? '—') ?></div>
    <div><strong>League/Season Hint:</strong> <?= e(($row['last_league_api_id'] ?: '—') . ' / ' . ($row['last_season_hint'] ?: '—')) ?></div>
    <div><strong>Created:</strong> <?= e($row['created']) ?></div>
    <div><strong>Modified:</strong> <?= e($row['modified']) ?></div>
  </div>

  <div style="margin-top:16px">
    <?php if (!empty($row['logo_url'])): ?>
      <img src="<?= e($row['logo_url']) ?>" alt="Logo" style="height:60px">
    <?php endif; ?>
  </div>

  <div style="margin-top:16px">
    <a href="<?= BASE_URL ?>/pages/teams_edit.php?id=<?= (int)$row['id'] ?>">Edit</a> ·
    <a href="<?= BASE_URL ?>/pages/teams_delete.php?id=<?= (int)$row['id'] ?>" onclick="return confirm('Delete this team? This cannot be undone.');">Delete</a> ·
    <a href="<?= BASE_URL ?>/pages/teams_list.php">Back to list</a>
  </div>
</div>
</body>
</html>
