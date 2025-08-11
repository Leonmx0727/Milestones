<?php
/**
 * UCID: LM64 | Date: 11/08/2025
 * Details: Admin view of ALL users' team associations with filters, stats, and bulk remove.
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_login();
if (!has_role('admin')) { flash('error','Admins only.'); redirect('/pages/home.php'); }

# ----- Handle Bulk Remove (POST) -----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'remove_all') {
  try {
    $username = trim($_POST['username'] ?? '');
    $tname    = trim($_POST['team_name'] ?? '');
    $country  = trim($_POST['country'] ?? '');
    $is_api   = $_POST['is_api'] ?? 'either';

    $where = [];
    $params = [];
    if ($username !== '') { $where[] = 'u.username LIKE ?'; $params[] = '%'.$username.'%'; }
    if ($tname   !== '') { $where[] = 't.name LIKE ?'; $params[] = '%'.$tname.'%'; }
    if ($country !== '') { $where[] = 't.country LIKE ?'; $params[] = '%'.$country.'%'; }
    if ($is_api === 'yes') { $where[] = 't.is_api = 1'; }
    elseif ($is_api === 'no') { $where[] = 't.is_api = 0'; }

    $whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';
    $sql = "DELETE utf FROM user_team_favorites utf
            JOIN users u ON u.id = utf.user_id
            JOIN teams t ON t.id = utf.team_id
            $whereSql";
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    flash('success','Removed all matching team associations.');
  } catch (Throwable $e) {
    error_log('admin_associations_teams bulk remove: '.$e->getMessage());
    flash('error','Could not remove associations right now.');
  }
  redirect('/pages/admin_associations_teams.php?'.http_build_query($_GET));
}

# ----- Filters (GET) -----
$username = trim($_GET['username'] ?? '');   // required by rubric (partial)
$tname    = trim($_GET['team_name'] ?? '');
$country  = trim($_GET['country'] ?? '');
$is_api   = $_GET['is_api'] ?? 'either';     // yes|no|either
$sort     = $_GET['sort'] ?? 'recent';       // recent | team_az | country_az | users_desc
$limit    = (int)($_GET['limit'] ?? 10);
$page     = (int)($_GET['page'] ?? 1);
if ($limit < 1 || $limit > 100) $limit = 10;
if ($page < 1) $page = 1;
$offset   = ($page - 1) * $limit;

$where = [];
$params = [];
if ($username !== '') { $where[] = 'u.username LIKE ?'; $params[] = '%'.$username.'%'; }
if ($tname   !== '') { $where[] = 't.name LIKE ?'; $params[] = '%'.$tname.'%'; }
if ($country !== '') { $where[] = 't.country LIKE ?'; $params[] = '%'.$country.'%'; }
if ($is_api === 'yes') { $where[] = 't.is_api = 1'; }
elseif ($is_api === 'no') { $where[] = 't.is_api = 0'; }
$whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

switch ($sort) {
  case 'team_az':    $orderBy = 'ORDER BY t.name ASC, utf.id DESC'; break;
  case 'country_az': $orderBy = 'ORDER BY t.country ASC, t.name ASC'; break;
  case 'users_desc': $orderBy = 'ORDER BY users_ct DESC, t.name ASC'; break;
  default:           $orderBy = 'ORDER BY utf.created DESC, utf.id DESC';
}

# ----- Stats -----
try {
  $tot = db()->query('SELECT COUNT(*) FROM user_team_favorites')->fetchColumn();
  $total_all = (int)$tot;
} catch (Throwable $e) { error_log('admin_associations_teams total_all: '.$e->getMessage()); $total_all = 0; }

# Count filtered
try {
  $csql = "SELECT COUNT(*) FROM user_team_favorites utf
           JOIN users u ON u.id = utf.user_id
           JOIN teams t ON t.id = utf.team_id
           $whereSql";
  $stmt = db()->prepare($csql);
  $stmt->execute($params);
  $total_filtered = (int)$stmt->fetchColumn();
} catch (Throwable $e) { error_log('admin_associations_teams total_filtered: '.$e->getMessage()); $total_filtered = 0; }

# ----- Users per team (for Users Count column) -----
$usersCount = [];
try {
  $agg = db()->query('SELECT team_id, COUNT(*) as c FROM user_team_favorites GROUP BY team_id');
  foreach ($agg as $row) $usersCount[(int)$row['team_id']] = (int)$row['c'];
} catch (Throwable $e) { error_log('admin_associations_teams agg: '.$e->getMessage()); }

# ----- Fetch page -----
try {
  $sql = "SELECT utf.id as rel_id, utf.created as added_on,
                 u.id as user_id, u.username,
                 t.id as team_id, t.name, t.country, t.logo_url
          FROM user_team_favorites utf
          JOIN users u ON u.id = utf.user_id
          JOIN teams t ON t.id = utf.team_id
          $whereSql
          $orderBy
          LIMIT $limit OFFSET $offset";
  $stmt = db()->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll();
} catch (Throwable $e) {
  error_log('admin_associations_teams fetch: '.$e->getMessage());
  $rows = [];
}
$shown = count($rows);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin • Team Associations</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/styles.css">
  <script src="<?= BASE_URL ?>/js/scripts.js" defer></script>
</head>
<body>
<?php render_navbar('admin'); ?>

<div class="container">
  <?php render_flash(); ?>
  <h1>All Users – Team Associations</h1>

  <div class="help" style="margin-bottom:8px;">
    Total associations (all users): <strong><?= $total_all ?></strong> • Showing: <strong><?= $shown ?></strong> of <strong><?= $total_filtered ?></strong>
  </div>

  <form method="get" onsubmit="return validateAdminAssocFilters(this)" style="margin-bottom:12px;display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));align-items:end">
    <label>Username (partial)
      <input type="text" name="username" value="<?= e($username) ?>" placeholder="e.g., ali">
    </label>
    <label>Team Name (partial)
      <input type="text" name="team_name" value="<?= e($tname) ?>" placeholder="e.g., United">
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
        <option value="recent"     <?= $sort==='recent'?'selected':'' ?>>Most Recently Added</option>
        <option value="team_az"    <?= $sort==='team_az'?'selected':'' ?>>Team A→Z</option>
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
      <a class="button secondary" href="<?= BASE_URL ?>/pages/admin_associations_teams.php" style="margin-left:8px;display:inline-block;padding:12px 16px;background:#6c757d;color:#fff;border-radius:8px;text-decoration:none;">Reset</a>
    </div>
  </form>

  <form method="post" onsubmit="return confirm('Remove ALL associations that match the current filters?');" style="margin-bottom:12px">
    <input type="hidden" name="action" value="remove_all">
    <input type="hidden" name="username" value="<?= e($username) ?>">
    <input type="hidden" name="team_name" value="<?= e($tname) ?>">
    <input type="hidden" name="country" value="<?= e($country) ?>">
    <input type="hidden" name="is_api" value="<?= e($is_api) ?>">
    <button type="submit" class="button secondary">Remove All for Matching Users/Teams</button>
  </form>

  <?php if (!$rows): ?>
    <div class="alert info">No results available.</div>
  <?php else: ?>
    <div style="overflow:auto">
      <table style="width:100%;border-collapse:collapse">
        <thead>
          <tr style="text-align:left;border-bottom:1px solid #eee">
            <th style="padding:8px">Team</th>
            <th style="padding:8px">Country</th>
            <th style="padding:8px">Users Count</th>
            <th style="padding:8px">Username</th>
            <th style="padding:8px">Added On</th>
            <th style="padding:8px">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r):
          $ct = $usersCount[(int)$r['team_id']] ?? 0;
        ?>
          <tr style="border-bottom:1px solid #f0f0f0">
            <td style="padding:8px">
              <?php if ($r['logo_url']): ?><img src="<?= e($r['logo_url']) ?>" style="height:24px;vertical-align:middle;margin-right:6px"><?php endif; ?>
              <a href="<?= BASE_URL ?>/pages/teams_view.php?id=<?= (int)$r['team_id'] ?>"><?= e($r['name']) ?></a>
            </td>
            <td style="padding:8px"><?= e($r['country'] ?? '—') ?></td>
            <td style="padding:8px"><?= (int)$ct ?></td>
            <td style="padding:8px">
              <a href="<?= BASE_URL ?>/pages/user_public.php?u=<?= urlencode($r['username']) ?>"><?= e($r['username']) ?></a>
            </td>
            <td style="padding:8px"><?= e($r['added_on']) ?></td>
            <td style="padding:8px;white-space:nowrap">
              <a href="<?= BASE_URL ?>/pages/teams_view.php?id=<?= (int)$r['team_id'] ?>">View Team</a> ·
              <a href="<?= BASE_URL ?>/pages/admin_remove_team_assoc.php?uid=<?= (int)$r['user_id'] ?>&tid=<?= (int)$r['team_id'] ?>" onclick="return confirm('Remove this user↔team association?');">Remove</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php
      $base = BASE_URL . '/pages/admin_associations_teams.php?' . http_build_query(array_merge($_GET, ['page' => null]));
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
