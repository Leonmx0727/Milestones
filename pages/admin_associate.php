<?php
/**
 * UCID: LM64 | Date: 11/08/2025
 * Admin Bulk Associate Tool
 * - Mode: Teams | Leagues
 * - Left: entities search (max 25)  • Right: users search (max 25)
 * - Apply Associations toggles relationships:
 *      if exists -> remove; if not -> add
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_login();
if (!has_role('admin')) {
  flash('error','Admins only.'); redirect('/pages/home.php');
}

/* ------------ Read filters (sticky via GET) ------------ */
$mode         = ($_GET['mode'] ?? 'teams') === 'leagues' ? 'leagues' : 'teams'; // default teams
$ename        = trim($_GET['ename'] ?? '');     // entity name partial
$ecountry     = trim($_GET['ecountry'] ?? '');  // entity country partial
$e_is_api     = $_GET['e_is_api'] ?? 'either'; // API|Manual filter (either)
$uname        = trim($_GET['uname'] ?? '');     // username partial
$limitLeft    = 25; // per rubric
$limitRight   = 25;

/* ------------ Execute searches (GET) ------------ */
$entities = [];
$users = [];

// Build entity query based on mode
if ($mode === 'teams') {
  $etable = 'teams';
  $eidcol = 'id';
  $enamecol = 'name';
  $countrycol = 'country';
  $logo = 'logo_url';
  $joinTable = 'user_team_favorites';
  $joinIdCol = 'team_id';
} else {
  $etable = 'leagues';
  $eidcol = 'id';
  $enamecol = 'name';
  $countrycol = 'country';
  $logo = 'logo_url';
  $joinTable = 'user_league_follows';
  $joinIdCol = 'league_id';
}

try {
  // Entities query (max 25)
  $ewhere = [];
  $eparams = [];
  if ($ename !== '')    { $ewhere[] = "$enamecol LIKE ?"; $eparams[] = '%'.$ename.'%'; }
  if ($ecountry !== '') { $ewhere[] = "$countrycol LIKE ?"; $eparams[] = '%'.$ecountry.'%'; }
  if ($e_is_api === 'yes') { $ewhere[] = "is_api = 1"; }
  elseif ($e_is_api === 'no') { $ewhere[] = "is_api = 0"; }
  $ewhereSql = $ewhere ? ('WHERE '.implode(' AND ', $ewhere)) : '';
  $esql = "SELECT $eidcol AS id, $enamecol AS name, $countrycol AS country, $logo AS logo_url
           FROM $etable
           $ewhereSql
           ORDER BY created DESC, id DESC
           LIMIT $limitLeft";
  $stmt = db()->prepare($esql);
  $stmt->execute($eparams);
  $entities = $stmt->fetchAll();
} catch (Throwable $e) {
  error_log('admin_associate entities: '.$e->getMessage());
  $entities = [];
}

// Users search (max 25)
try {
  $uwhere = [];
  $uparams = [];
  if ($uname !== '') { $uwhere[] = "username LIKE ?"; $uparams[] = '%'.$uname.'%'; }
  $uwhereSql = $uwhere ? ('WHERE '.implode(' AND ', $uwhere)) : '';
  $usql = "SELECT id, username, created FROM users
           $uwhereSql
           ORDER BY created DESC, id DESC
           LIMIT $limitRight";
  $stmt = db()->prepare($usql);
  $stmt->execute($uparams);
  $users = $stmt->fetchAll();
} catch (Throwable $e) {
  error_log('admin_associate users: '.$e->getMessage());
  $users = [];
}

// Handle Apply (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'apply') {
  $modePost = ($_POST['mode'] ?? 'teams') === 'leagues' ? 'leagues' : 'teams';
  $entityIds = array_values(array_filter(array_map('intval', $_POST['entity_ids'] ?? [])));
  $userIds   = array_values(array_filter(array_map('intval', $_POST['user_ids'] ?? [])));

  if (!$entityIds || !$userIds) {
    flash('error','Select at least one user and one entity.'); 
    redirect('/pages/admin_associate.php?'.http_build_query($_GET));
  }

  // Map mode to relationship table
  $relTable = $modePost === 'teams' ? 'user_team_favorites' : 'user_league_follows';
  $relCol   = $modePost === 'teams' ? 'team_id'            : 'league_id';

  $added = 0; $removed = 0;
  try {
    db()->beginTransaction();
    $delStmt = db()->prepare("DELETE FROM $relTable WHERE user_id=? AND $relCol=?");
    $insStmt = db()->prepare("INSERT IGNORE INTO $relTable (user_id, $relCol) VALUES (?,?)");

    foreach ($userIds as $uid) {
      foreach ($entityIds as $eid) {
        // Toggle: try delete first; if nothing deleted, insert.
        $delStmt->execute([$uid, $eid]);
        if ($delStmt->rowCount() > 0) {
          $removed++;
        } else {
          $insStmt->execute([$uid, $eid]);
          if ($insStmt->rowCount() > 0) $added++;
        }
      }
    }
    db()->commit();
    flash('success', "Applied associations. Added: $added, Removed: $removed.");
  } catch (Throwable $e) {
    db()->rollBack();
    error_log('admin_associate apply: '.$e->getMessage());
    flash('error','Could not apply associations right now.');
  }
  // keep current GET filters
  redirect('/pages/admin_associate.php?'.http_build_query($_GET));
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin • Bulk Associate</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/styles.css">
  <script>
    // UCID: LM64 | Date: 11/08/2025
    function validateBulkApply(form){
      const e = form.querySelectorAll('input[name="entity_ids[]"]:checked').length;
      const u = form.querySelectorAll('input[name="user_ids[]"]:checked').length;
      if (!e || !u) { alert('Select at least one entity and one user.'); return false; }
      return true;
    }
  </script>
</head>
<body>
<?php render_navbar('admin'); ?>

<div class="container">
  <?php render_flash(); ?>
  <h1>Bulk Associate (Toggle)</h1>

  <form method="get" style="margin-bottom:12px;display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));align-items:end">
    <label>Mode
      <select name="mode">
        <option value="teams"   <?= $mode==='teams'?'selected':'' ?>>Teams</option>
        <option value="leagues" <?= $mode==='leagues'?'selected':'' ?>>Leagues</option>
      </select>
    </label>
    <label>Entity Name (partial)
      <input type="text" name="ename" value="<?= e($ename) ?>" placeholder="<?= $mode==='teams'?'e.g., United':'e.g., Premier' ?>">
    </label>
    <label>Entity Country (partial)
      <input type="text" name="ecountry" value="<?= e($ecountry) ?>" placeholder="e.g., England">
    </label>
    <label>Entity Source
      <select name="e_is_api">
        <option value="either" <?= $e_is_api==='either'?'selected':'' ?>>Either</option>
        <option value="yes"    <?= $e_is_api==='yes'?'selected':'' ?>>API</option>
        <option value="no"     <?= $e_is_api==='no'?'selected':'' ?>>Manual</option>
      </select>
    </label>
    <label>User (username partial)
      <input type="text" name="uname" value="<?= e($uname) ?>" placeholder="e.g., ali">
    </label>
    <div>
      <button type="submit">Search</button>
      <a class="button secondary" href="<?= BASE_URL ?>/pages/admin_associate.php?mode=<?= e($mode) ?>" style="margin-left:8px;display:inline-block;padding:12px 16px;background:#6c757d;color:#fff;border-radius:8px;text-decoration:none;">Reset</a>
    </div>
  </form>

  <form method="post" onsubmit="return validateBulkApply(this)">
    <input type="hidden" name="action" value="apply">
    <input type="hidden" name="mode" value="<?= e($mode) ?>">

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
      <!-- Entities column -->
      <div class="card" style="padding:12px;border:1px solid #eee;border-radius:12px">
        <h2 style="margin-top:0"><?= $mode==='teams' ? 'Teams' : 'Leagues' ?> (up to 25)</h2>
        <?php if (!$entities): ?>
          <div class="alert info">No entities match your search.</div>
        <?php else: ?>
          <?php foreach ($entities as $eRow): ?>
            <label style="display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px dashed #f1f1f1">
              <input type="checkbox" name="entity_ids[]" value="<?= (int)$eRow['id'] ?>">
              <?php if (!empty($eRow['logo_url'])): ?>
                <img src="<?= e($eRow['logo_url']) ?>" alt="" style="height:20px">
              <?php endif; ?>
              <span><strong><?= e($eRow['name']) ?></strong><?php if (!empty($eRow['country'])): ?> — <?= e($eRow['country']) ?><?php endif; ?></span>
            </label>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- Users column -->
      <div class="card" style="padding:12px;border:1px solid #eee;border-radius:12px">
        <h2 style="margin-top:0">Users (up to 25)</h2>
        <?php if (!$users): ?>
          <div class="alert info">No users match your search.</div>
        <?php else: ?>
          <?php foreach ($users as $uRow): ?>
            <label style="display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px dashed #f1f1f1">
              <input type="checkbox" name="user_ids[]" value="<?= (int)$uRow['id'] ?>">
              <span>@<?= e($uRow['username']) ?> <small style="opacity:0.6">• since <?= e($uRow['created']) ?></small></span>
            </label>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <div style="margin-top:12px">
      <button type="submit" class="button">Apply Associations</button>
    </div>
  </form>
</div>
</body>
</html>
