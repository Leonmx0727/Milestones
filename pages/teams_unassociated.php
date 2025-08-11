<?php
// UCID: LM64 | Date: 11/08/2025
// Admin list of teams with zero user associations
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_login();
if (!has_role('admin')) { flash('error','Admins only.'); redirect('/pages/home.php'); }

$name = trim($_GET['name'] ?? '');
$country = trim($_GET['country'] ?? '');
$is_api = $_GET['is_api'] ?? 'either';
$sort = $_GET['sort'] ?? 'created_desc';
$limit = (int)($_GET['limit'] ?? 10);
$page  = (int)($_GET['page'] ?? 1);
if ($limit<1||$limit>100) $limit=10;
if ($page<1) $page=1;
$offset = ($page-1)*$limit;

$where = ['utf.id IS NULL'];
$params = [];
if ($name !== '')    { $where[] = 't.name LIKE ?';    $params[] = '%'.$name.'%'; }
if ($country !== '') { $where[] = 't.country LIKE ?'; $params[] = '%'.$country.'%'; }
if ($is_api==='yes') { $where[] = 't.is_api = 1'; }
elseif ($is_api==='no') { $where[] = 't.is_api = 0'; }
$whereSql = 'WHERE '.implode(' AND ', $where);

switch ($sort) {
  case 'name_asc':    $orderBy='ORDER BY t.name ASC, t.id DESC'; break;
  case 'country_asc': $orderBy='ORDER BY t.country ASC, t.name ASC'; break;
  default:            $orderBy='ORDER BY t.created DESC, t.id DESC';
}

try {
  $cs = "SELECT COUNT(*) FROM teams t LEFT JOIN user_team_favorites utf ON utf.team_id=t.id $whereSql";
  $stmt = db()->prepare($cs); $stmt->execute($params); $total = (int)$stmt->fetchColumn();
} catch (Throwable $e) { error_log('teams_unassociated count: '.$e->getMessage()); $total = 0; }

try {
  $sql = "SELECT t.* FROM teams t
          LEFT JOIN user_team_favorites utf ON utf.team_id=t.id
          $whereSql $orderBy LIMIT $limit OFFSET $offset";
  $stmt = db()->prepare($sql); $stmt->execute($params); $rows=$stmt->fetchAll();
} catch (Throwable $e) { error_log('teams_unassociated fetch: '.$e->getMessage()); $rows=[]; }
$shown = count($rows);
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Admin • Unassociated Teams</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/styles.css"><script src="<?= BASE_URL ?>/js/scripts.js" defer></script></head>
<body>
<?php render_navbar('admin'); ?>
<div class="container">
  <?php render_flash(); ?>
  <h1>Unassociated Teams</h1>
  <div class="help">Total unassociated: <strong><?= $total ?></strong> • Showing: <strong><?= $shown ?></strong></div>

  <form method="get" onsubmit="return validateAdminAssocFilters(this)" style="margin-bottom:12px;display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(220px,1fr))">
    <label>Name (partial)<input type="text" name="name" value="<?= e($name) ?>"></label>
    <label>Country (partial)<input type="text" name="country" value="<?= e($country) ?>"></label>
    <label>Source
      <select name="is_api">
        <option value="either" <?= $is_api==='either'?'selected':'' ?>>Either</option>
        <option value="yes"    <?= $is_api==='yes'?'selected':'' ?>>API</option>
        <option value="no"     <?= $is_api==='no'?'selected':'' ?>>Manual</option>
      </select>
    </label>
    <label>Sort
      <select name="sort">
        <option value="created_desc" <?= $sort==='created_desc'?'selected':'' ?>>Newest Created</option>
        <option value="name_asc"     <?= $sort==='name_asc'?'selected':'' ?>>Name A→Z</option>
        <option value="country_asc"  <?= $sort==='country_asc'?'selected':'' ?>>Country A→Z</option>
      </select>
    </label>
    <label>Limit (1–100)<input type="number" name="limit" min="1" max="100" value="<?= e((string)$limit) ?>"></label>
    <label>Page<input type="number" name="page" min="1" value="<?= e((string)$page) ?>"></label>
    <div><button type="submit">Apply Filters</button><a class="button secondary" href="<?= BASE_URL ?>/pages/teams_unassociated.php" style="margin-left:8px;display:inline-block;padding:12px 16px;background:#6c757d;color:#fff;border-radius:8px;text-decoration:none;">Reset</a></div>
  </form>

  <?php if (!$rows): ?><div class="alert info">No results available.</div>
  <?php else: ?>
    <div style="overflow:auto">
      <table style="width:100%;border-collapse:collapse">
        <thead><tr><th style="padding:8px">Logo</th><th style="padding:8px">Team</th><th style="padding:8px">Country</th><th style="padding:8px">Actions</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr style="border-bottom:1px solid #f0f0f0">
            <td style="padding:8px"><?php if ($r['logo_url']): ?><img src="<?= e($r['logo_url']) ?>" style="height:24px"><?php else: ?>—<?php endif; ?></td>
            <td style="padding:8px"><?= e($r['name']) ?></td>
            <td style="padding:8px"><?= e($r['country'] ?? '—') ?></td>
            <td style="padding:8px;white-space:nowrap"><a href="<?= BASE_URL ?>/pages/teams_view.php?id=<?= (int)$r['id'] ?>">View</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php
      $base = BASE_URL . '/pages/teams_unassociated.php?' . http_build_query(array_merge($_GET, ['page'=>null]));
      $prev = $page>1 ? ($base.'&page='.($page-1)) : null;
      $next = ($offset + $shown) < $total ? ($base.'&page='.($page+1)) : null;
    ?>
    <div style="margin-top:12px;display:flex;gap:8px">
      <?php if ($prev): ?><a class="button secondary" href="<?= e($prev) ?>">← Prev</a><?php endif; ?>
      <?php if ($next): ?><a class="button" href="<?= e($next) ?>">Next →</a><?php endif; ?>
    </div>
  <?php endif; ?>
</div>
</body>
</html>
