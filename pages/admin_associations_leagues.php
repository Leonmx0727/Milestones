<?php
/**
 * UCID: LM64 | Date: 11/08/2025
 * Admin view of ALL users' league assoc# ----- Fetch page -----
try {
  $sql = "SELECT ulf.id as rel_id, ulf.created as added_on,
                 u.id as user_id, u.username,
                 l.id as league_id, l.name, l.country, l.logo_url
          FROM user_league_follows ulf
          JOIN users u ON u.id = ulf.user_id
          JOIN leagues l ON l.id = ulf.league_id
          $whereSql
          $orderBy
          LIMIT $limit OFFSET $offset";
  $stmt = db()->prepare($sql); $stmt->execute($params);
  $rows = $stmt->fetchAll();
} catch (Throwable $e) { error_log('admin_associations_leagues fetch: '.$e->getMessage()); $rows = []; }s) with filters, stats, bulk remove.
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_login();
if (!has_role('admin')) { flash('error','Admins only.'); redirect('/pages/home.php'); }

# ---- Bulk remove (POST) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'remove_all') {
  try {
    $username = trim($_POST['username'] ?? '');
    $lname    = trim($_POST['league_name'] ?? '');
    $country  = trim($_POST['country'] ?? '');
    $is_api   = $_POST['is_api'] ?? 'either';
    $current  = $_POST['season_current'] ?? 'either';

    $where = []; $params = [];
    if ($username !== '') { $where[] = 'u.username LIKE ?'; $params[] = '%'.$username.'%'; }
    if ($lname    !== '') { $where[] = 'l.name LIKE ?';     $params[] = '%'.$lname.'%'; }
    if ($country  !== '') { $where[] = 'l.country LIKE ?';  $params[] = '%'.$country.'%'; }
    if ($current === 'yes') { $where[] = 'l.season_current = 1'; }
    elseif ($current === 'no') { $where[] = 'l.season_current = 0'; }
    if ($is_api === 'yes') { $where[] = 'l.is_api = 1'; }
    elseif ($is_api === 'no') { $where[] = 'l.is_api = 0'; }

    $whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';
    $sql = "DELETE ulf FROM user_league_follows ulf
            JOIN users u ON u.id = ulf.user_id
            JOIN leagues l ON l.id = ulf.league_id
            $whereSql";
    $stmt = db()->prepare($sql); $stmt->execute($params);
    flash('success','Removed all matching league associations.');
  } catch (Throwable $e) { error_log('admin_associations_leagues bulk: '.$e->getMessage()); flash('error','Could not remove associations right now.'); }
  redirect('/pages/admin_associations_leagues.php?'.http_build_query($_GET));
}

# ---- Filters (GET) ----
$username = trim($_GET['username'] ?? '');
$lname    = trim($_GET['league_name'] ?? '');
$country  = trim($_GET['country'] ?? '');
$season_current = $_GET['season_current'] ?? 'either';
$is_api   = $_GET['is_api'] ?? 'either';
$sort     = $_GET['sort'] ?? 'recent';        // recent | league_az | country_az | users_desc
$limit    = (int)($_GET['limit'] ?? 10);
$page     = (int)($_GET['page'] ?? 1);
if ($limit < 1 || $limit > 100) $limit = 10;
if ($page < 1) $page = 1;
$offset   = ($page - 1) * $limit;

$where = []; $params = [];
if ($username !== '') { $where[] = 'u.username LIKE ?'; $params[] = '%'.$username.'%'; }
if ($lname    !== '') { $where[] = 'l.name LIKE ?';     $params[] = '%'.$lname.'%'; }
if ($country  !== '') { $where[] = 'l.country LIKE ?';  $params[] = '%'.$country.'%'; }
if ($season_current === 'yes') { $where[] = 'l.season_current = 1'; }
elseif ($season_current === 'no') { $where[] = 'l.season_current = 0'; }
if ($is_api === 'yes') { $where[] = 'l.is_api = 1'; }
elseif ($is_api === 'no') { $where[] = 'l.is_api = 0'; }
$whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

switch ($sort) {
  case 'league_az':   $orderBy = 'ORDER BY l.name ASC, ulf.id DESC'; break;
  case 'country_az':  $orderBy = 'ORDER BY l.country ASC, l.name ASC'; break;
  case 'users_desc':  $orderBy = 'ORDER BY users_ct DESC, l.name ASC'; break;
  default:            $orderBy = 'ORDER BY ulf.created DESC, ulf.id DESC';
}

# ---- Stats ----
try { $total_all = (int)db()->query('SELECT COUNT(*) FROM user_league_follows')->fetchColumn(); }
catch (Throwable $e) { error_log('admin_associations_leagues total: '.$e->getMessage()); $total_all = 0; }

try {
  $csql = "SELECT COUNT(*)
           FROM user_league_follows ulf
           JOIN users u ON u.id = ulf.user_id
           JOIN leagues l ON l.id = ulf.league_id
           $whereSql";
  $stmt = db()->prepare($csql); $stmt->execute($params);
  $total_filtered = (int)$stmt->fetchColumn();
} catch (Throwable $e) { error_log('admin_associations_leagues filtered: '.$e->getMessage()); $total_filtered = 0; }

# Users per league (for Users Count column)
$usersCount = [];
try {
  $agg = db()->query('SELECT league_id, COUNT(*) c FROM user_league_follows GROUP BY league_id');
  foreach ($agg as $r) $usersCount[(int)$r['league_id']] = (int)$r['c'];
} catch (Throwable $e) { error_log('admin_associations_leagues agg: '.$e->getMessage()); }

# ---- Fetch page ----
try {
  $sql = "SELECT ulf.id as rel_id, ulf.created as added_on,
                 u.id as user_id, u.username,
                 l.id as league_id, l.name, l.country, l.logo_url
          FROM user_league_follows ulf
          JOIN users u ON u.id = ulf.user_id
          JOIN leagues l ON l.id = ulf.league_id
          $whereSql
          $orderBy
          LIMIT $limit OFFSET $offset";
  $stmt = db()->prepare($sql); $stmt->execute($params);
  $rows = $stmt->fetchAll();
} catch (Throwable $e) { error_log('admin_associations_leagues fetch: '.$e->getMessage()); $rows = []; }
$shown = count($rows);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin • League Associations</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/styles.css">
  <script src="<?= BASE_URL ?>/js/scripts.js" defer></script>
</head>
<body>
<?php render_navbar('admin'); ?>

<div class="container">
  <?php render_flash(); ?>
  <h1>All Users – League Associations</h1>

  <div class="help" style="margin-bottom:8px;">
    Total associations (all users): <strong><?= $total_all ?></strong> • Showing: <strong><?= $shown ?></strong> of <strong><?= $total_filtered ?></strong>
  </div>

  <form method="get" onsubmit="return validateAdminAssocFilters(this)" style="margin-bottom:12px;display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));align-items:end">
    <label>Username (partial)
      <input type="text" name="username" value="<?= e($username) ?>" placeholder="e.g., ali">
    </label>
    <label>League Name (partial)
      <input type="text" name="league_name" value="<?= e($lname) ?>" placeholder="e.g., Premier">
    </label>
    <label>Country (partial)
      <input type="text" name="country" value="<?= e($country) ?>" placeholder="e.g., England">
    </label>
    <label>Current Season
      <select name="season_current">
        <option value="either" <?= $season_current==='either'?'selected':'' ?>>Either</option>
        <option value="yes"    <?= $season_current==='yes'?'selected':'' ?>>Yes</option>
        <option value="no"     <?= $season_current==='no'?'selected':'' ?>>No</option>
      </select>
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
        <option value="recent"     <?= $sort==='recent'?'selected':'' ?>>Most Recently Added</option>
        <option value="league_az"  <?= $sort==='league_az'?'selected':'' ?>>League A→Z</option>
        <option value="country_az" <?= $sort==='country_az'?'selected':'' ?>>Country A→Z</option>
        <option value="users_desc" <?= $sort==='users_desc'?'selected':'' ?>>Users Count (desc)</option>
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
      <a class="button secondary" href="<?= BASE_URL ?>/pages/admin_associations_leagues.php" style="margin-left:8px;display:inline-block;padding:12px 16px;background:#6c757d;color:#fff;border-radius:8px;text-decoration:none;">Reset</a>
    </div>
  </form>

  <form method="post" onsubmit="return confirm('Remove ALL league associations that match the current filters?');" style="margin-bottom:12px">
    <input type="hidden" name="action" value="remove_all">
    <input type="hidden" name="username" value="<?= e($username) ?>">
    <input type="hidden" name="league_name" value="<?= e($lname) ?>">
    <input type="hidden" name="country" value="<?= e($country) ?>">
    <input type="hidden" name="season_current" value="<?= e($season_current) ?>">
    <input type="hidden" name="is_api" value="<?= e($is_api) ?>">
    <button type="submit" class="button secondary">Remove All for Matching Users/Leagues</button>
  </form>

  <?php if (!$rows): ?>
    <div class="alert info">No results available.</div>
  <?php else: ?>
    <div style="overflow:auto">
      <table style="width:100%;border-collapse:collapse">
        <thead>
          <tr style="text-align:left;border-bottom:1px solid #eee">
            <th style="padding:8px">League</th>
            <th style="padding:8px">Country</th>
            <th style="padding:8px">Users Count</th>
            <th style="padding:8px">Username</th>
            <th style="padding:8px">Added On</th>
            <th style="padding:8px">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): $ct = $usersCount[(int)$r['league_id']] ?? 0; ?>
          <tr style="border-bottom:1px solid #f0f0f0">
            <td style="padding:8px">
              <?php if ($r['logo_url']): ?><img src="<?= e($r['logo_url']) ?>" style="height:24px;vertical-align:middle;margin-right:6px"><?php endif; ?>
              <a href="<?= BASE_URL ?>/pages/leagues_view.php?id=<?= (int)$r['league_id'] ?>"><?= e($r['name']) ?></a>
            </td>
            <td style="padding:8px"><?= e($r['country'] ?? '—') ?></td>
            <td style="padding:8px"><?= (int)$ct ?></td>
            <td style="padding:8px"><a href="<?= BASE_URL ?>/pages/user_public.php?u=<?= urlencode($r['username']) ?>"><?= e($r['username']) ?></a></td>
            <td style="padding:8px"><?= e($r['added_on']) ?></td>
            <td style="padding:8px;white-space:nowrap">
              <a href="<?= BASE_URL ?>/pages/leagues_view.php?id=<?= (int)$r['league_id'] ?>">View League</a> ·
              <a href="<?= BASE_URL ?>/pages/admin_remove_league_assoc.php?uid=<?= (int)$r['user_id'] ?>&lid=<?= (int)$r['league_id'] ?>" onclick="return confirm('Remove this user↔league association?');">Remove</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php
      $base = BASE_URL . '/pages/admin_associations_leagues.php?' . http_build_query(array_merge($_GET, ['page' => null]));
      $prev = $page > 1 ? ($base . '&page=' . ($page-1)) : null;
      $next = ($offset + $shown) < $total_filtered ? ($base . '&page=' . ($page+1)) : null;
    ?>
    <div style="margin-top:12px;display:flex;gap:8px">
      <?php if ($prev): ?><a class="button secondary" href="<?= e($prev) ?>" style="padding:10px 12px;background:#6c757d;color:#fff;border-radius:8px;text-decoration:none;">← Prev</a><?php endif; ?>
      <?php if ($next): ?><a class="button" href="<?= e($next) ?>" style="padding:10px 12px;background:#0d6efd;color:#fff;border-radius:8px;text-decoration:none;">Next →</a><?php endif; ?>
    </div>
  <?php endif; ?>
</div>
</body>
</html>
