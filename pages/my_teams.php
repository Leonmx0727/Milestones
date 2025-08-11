<?php
/**
 * UCID: LM64 | Date: 11/08/2025
 * Details: "My Teams" — list of favorited teams with filters, stats, and remove/remove-all.
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_login();

$user = current_user();

/** ---------- Handle Remove All (POST) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'remove_all') {
    try {
        // Build same WHERE used for listing, but scoped to this user
        $name    = trim($_POST['name'] ?? '');
        $country = trim($_POST['country'] ?? '');
        $is_api  = $_POST['is_api'] ?? 'either';

        $where = ['utf.user_id = ?'];
        $params = [$user['id']];
        if ($name !== '') { $where[] = 't.name LIKE ?';    $params[] = '%'.$name.'%'; }
        if ($country !== '') { $where[] = 't.country LIKE ?'; $params[] = '%'.$country.'%'; }
        if ($is_api === 'yes') { $where[] = 't.is_api = 1'; }
        elseif ($is_api === 'no') { $where[] = 't.is_api = 0'; }

        $whereSql = implode(' AND ', $where);
        $sql = "DELETE utf FROM user_team_favorites utf
                JOIN teams t ON t.id = utf.team_id
                WHERE $whereSql";
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        flash('success', 'Removed all matching favorites.');
    } catch (Throwable $e) {
        error_log('my_teams remove_all: '.$e->getMessage());
        flash('error', 'Could not remove favorites right now.');
    }
    // redirect to GET to avoid resubmits
    redirect('/pages/my_teams.php?'.http_build_query($_GET));
}

/** ---------- Read filters (GET) ---------- */
$name    = trim($_GET['name'] ?? '');
$country = trim($_GET['country'] ?? '');
$is_api  = $_GET['is_api'] ?? 'either';          // yes|no|either
$sort    = $_GET['sort'] ?? 'created_desc';      // name_asc|country_asc|created_desc
$limit   = (int)($_GET['limit'] ?? 10);
$page    = (int)($_GET['page'] ?? 1);
if ($limit < 1 || $limit > 100) $limit = 10;
if ($page < 1) $page = 1;
$offset  = ($page - 1) * $limit;

/** ---------- WHERE for this user + filters ---------- */
$where = ['utf.user_id = ?'];
$params = [$user['id']];
if ($name !== '')    { $where[] = 't.name LIKE ?';    $params[] = '%'.$name.'%'; }
if ($country !== '') { $where[] = 't.country LIKE ?'; $params[] = '%'.$country.'%'; }
if ($is_api === 'yes') { $where[] = 't.is_api = 1'; }
elseif ($is_api === 'no') { $where[] = 't.is_api = 0'; }
$whereSql = 'WHERE ' . implode(' AND ', $where);

/** ---------- Sorting ---------- */
switch ($sort) {
  case 'name_asc':    $orderBy = 'ORDER BY t.name ASC, t.id DESC'; break;
  case 'country_asc': $orderBy = 'ORDER BY t.country ASC, t.name ASC'; break;
  default:            $orderBy = 'ORDER BY utf.created DESC, t.id DESC'; // newest added first
}

/** ---------- Stats: total associations for this user ---------- */
try {
  $stmt = db()->prepare('SELECT COUNT(*) FROM user_team_favorites WHERE user_id = ?');
  $stmt->execute([$user['id']]);
  $total_for_user = (int)$stmt->fetchColumn();
} catch (Throwable $e) { error_log('my_teams total_for_user: '.$e->getMessage()); $total_for_user = 0; }

/** ---------- Count filtered total ---------- */
try {
  $stmt = db()->prepare("SELECT COUNT(*) 
                         FROM user_team_favorites utf
                         JOIN teams t ON t.id = utf.team_id
                         $whereSql");
  $stmt->execute($params);
  $total_filtered = (int)$stmt->fetchColumn();
} catch (Throwable $e) { error_log('my_teams total_filtered: '.$e->getMessage()); $total_filtered = 0; }

/** ---------- Fetch page ---------- */
try {
  $sql = "SELECT t.*, utf.created AS added_on
          FROM user_team_favorites utf
          JOIN teams t ON t.id = utf.team_id
          $whereSql
          $orderBy
          LIMIT $limit OFFSET $offset";
  $stmt = db()->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll();
} catch (Throwable $e) { error_log('my_teams fetch: '.$e->getMessage()); $rows = []; }
$shown = count($rows);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Teams</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/styles.css">
  <script src="<?= BASE_URL ?>/js/scripts.js" defer></script>
</head>
<body>
<?php render_navbar('my_teams'); ?>

<div class="container">
  <?php render_flash(); ?>
  <h1>My Teams</h1>

  <div class="help" style="margin-bottom:8px;">
    Total favorites: <strong><?= $total_for_user ?></strong> • Showing: <strong><?= $shown ?></strong> of <strong><?= $total_filtered ?></strong>
  </div>

  <form method="get" onsubmit="return validateMyAssocFilters(this)" style="margin-bottom:12px;display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));align-items:end">
    <label>Name (partial)
      <input type="text" name="name" value="<?= e($name) ?>" placeholder="e.g., Manchester">
    </label>
    <label>Country (partial)
      <input type="text" name="country" value="<?= e($country) ?>" placeholder="e.g., England">
    </label>
    <label>Source
      <select name="is_api">
        <option value="either" <?= $is_api==='either'?'selected':'' ?>>Either</option>
        <option value="yes"    <?= $is_api==='yes'?'selected':'' ?>>API</option>
        <option value="no"     <?= $is_api==='no'?'selected':'' ?>>Manual</option>
      </select>
    </label>
    <label>Sort
      <select name="sort">
        <option value="created_desc" <?= $sort==='created_desc'?'selected':'' ?>>Newest Added</option>
        <option value="name_asc"     <?= $sort==='name_asc'?'selected':'' ?>>Name A→Z</option>
        <option value="country_asc"  <?= $sort==='country_asc'?'selected':'' ?>>Country A→Z</option>
      </select>
    </label>
    <label>Limit (1–100)
      <input type="number" name="limit" min="1" max="100" value="<?= e((string)$limit) ?>">
    </label>
    <label>Page
      <input type="number" name="page" min="1" value="<?= e((string)$page) ?>">
    </label>
    <div>
      <button type="submit">Apply Filters</button>
      <a class="button secondary" href="<?= BASE_URL ?>/pages/my_teams.php" style="margin-left:8px;display:inline-block;padding:12px 16px;background:#6c757d;color:#fff;border-radius:8px;text-decoration:none;">Reset</a>
    </div>
  </form>

  <?php if (!$rows): ?>
    <div class="alert info">No results available.</div>
  <?php else: ?>
    <form method="post" onsubmit="return confirm('Remove all matching favorites? This cannot be undone.');" style="margin-bottom:12px">
      <!-- keep the current filters in hidden inputs for POST remove_all -->
      <input type="hidden" name="action" value="remove_all">
      <input type="hidden" name="name" value="<?= e($name) ?>">
      <input type="hidden" name="country" value="<?= e($country) ?>">
      <input type="hidden" name="is_api" value="<?= e($is_api) ?>">
      <button type="submit" class="button secondary">Remove All (matching)</button>
    </form>

    <div style="overflow:auto">
      <table style="width:100%;border-collapse:collapse">
        <thead>
          <tr style="text-align:left;border-bottom:1px solid #eee">
            <th style="padding:8px">Logo</th>
            <th style="padding:8px">Team</th>
            <th style="padding:8px">Code</th>
            <th style="padding:8px">Country</th>
            <th style="padding:8px">Founded</th>
            <th style="padding:8px">Added On</th>
            <th style="padding:8px">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr style="border-bottom:1px solid #f0f0f0">
            <td style="padding:8px"><?php if ($r['logo_url']): ?><img src="<?= e($r['logo_url']) ?>" style="height:28px"><?php else: ?>—<?php endif; ?></td>
            <td style="padding:8px"><?= e($r['name']) ?></td>
            <td style="padding:8px"><?= e($r['code'] ?? '—') ?></td>
            <td style="padding:8px"><?= e($r['country'] ?? '—') ?></td>
            <td style="padding:8px"><?= e($r['founded'] ?? '—') ?></td>
            <td style="padding:8px"><?= e($r['added_on']) ?></td>
            <td style="padding:8px;white-space:nowrap">
              <a href="<?= BASE_URL ?>/pages/teams_view.php?id=<?= (int)$r['id'] ?>">View</a> ·
              <a href="<?= BASE_URL ?>/pages/favorite_team.php?action=remove&id=<?= (int)$r['id'] ?>" onclick="return confirm('Remove from favorites?');">Remove</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php
      $base = BASE_URL . '/pages/my_teams.php?' . http_build_query(array_merge($_GET, ['page' => null]));
      $prev = $page > 1 ? ($base . '&page=' . ($page-1)) : null;
      $next = ($offset + $shown) < $total_filtered ? ($base . '&page=' . ($page+1)) : null;
    ?>
    <div style="margin-top:12px;display:flex;gap:8px">
      <?php if ($prev): ?><a class="button secondary" href="<?= e($prev) ?>" style="padding:10px 12px;background:#6c757d;color:#fff;border-radius:8px;text-decoration:none;">← Prev</a><?php endif; ?>
      <?php if ($next): ?><a class="button" href="<?= e($next) ?>" style="padding:10px 12px;background:#0d6efd;color:#fff;border-radius:8px;text-decoration:none;">Next →</a><?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<script>
function validateMyAssocFilters(form) {
  const limit = form.limit.value.trim();
  const page  = form.page.value.trim();
  if (!limit || +limit < 1 || +limit > 100) { alert("Limit must be between 1 and 100."); return false; }
  if (page && +page < 1) { alert("Page must be 1 or greater."); return false; }
  return true;
}
</script>

</body>
</html>
