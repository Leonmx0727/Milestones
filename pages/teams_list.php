<?php
/**
 * UCID: lm64 | Date: 10/08/2025
 * Details: teams list page with filters
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_login();

// get filters from url
$name    = trim($_GET['name'] ?? '');
$country = trim($_GET['country'] ?? '');
$code    = trim($_GET['code'] ?? '');
$league  = trim($_GET['last_league_api_id'] ?? ''); // hint filter
$season  = trim($_GET['last_season_hint'] ?? '');   // hint filter
$is_api  = $_GET['is_api'] ?? 'either';             // yes|no|either
$sort    = $_GET['sort'] ?? 'created_desc';         // name_asc|country_asc|created_desc
$limit   = (int)($_GET['limit'] ?? 10);
$page    = (int)($_GET['page'] ?? 1);
if ($limit < 1 || $limit > 100) $limit = 10;
if ($page < 1) $page = 1;
$offset  = ($page - 1) * $limit;

$where = [];
$params = [];
if ($name !== '') { $where[] = 'name LIKE ?'; $params[] = '%'.$name.'%'; }
if ($country !== '') { $where[] = 'country LIKE ?'; $params[] = '%'.$country.'%'; }
if ($code !== '') { $where[] = 'code LIKE ?'; $params[] = '%'.$code.'%'; }
if ($league !== '') { $where[] = 'last_league_api_id = ?'; $params[] = (int)$league; }
if ($season !== '') { $where[] = 'last_season_hint = ?'; $params[] = (int)$season; }
if ($is_api === 'yes') { $where[] = 'is_api = 1'; }
elseif ($is_api === 'no') { $where[] = 'is_api = 0'; }

$whereSql = $where ? 'WHERE '.implode(' AND ',$where) : '';

switch ($sort) {
  case 'name_asc':    $orderBy = 'ORDER BY name ASC, id DESC'; break;
  case 'country_asc': $orderBy = 'ORDER BY country ASC, name ASC'; break;
  default:            $orderBy = 'ORDER BY created DESC, id DESC';
}

try {
  $stmt = db()->prepare("SELECT COUNT(*) FROM teams $whereSql");
  $stmt->execute($params);
  $total = (int)$stmt->fetchColumn();
} catch (Throwable $e) { error_log('teams_list count: '.$e->getMessage()); $total = 0; }

try {
  $sql = "SELECT id, api_team_id, name, code, country, founded, city, venue_name, logo_url,
                 last_league_api_id, last_season_hint, is_api, created
          FROM teams
          $whereSql
          $orderBy
          LIMIT $limit OFFSET $offset";
  $stmt = db()->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll();
} catch (Throwable $e) { error_log('teams_list fetch: '.$e->getMessage()); $rows = []; }
$shown = count($rows);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Teams – List</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/styles.css">
  <script src="<?= BASE_URL ?>/js/scripts.js" defer></script>
</head>
<body>
<?php render_navbar('teams'); ?>

<div class="container">
  <?php render_flash(); ?>
  <h1>Teams</h1>
  
  <?php render_admin_actions('teams'); ?>

  <form method="get" onsubmit="return validateTeamsListFilters(this)" style="margin-bottom:16px;display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));align-items:end">
    <label>Name (partial)
      <input type="text" name="name" value="<?= e($name) ?>" placeholder="e.g., Manchester">
    </label>
    <label>Country (partial)
      <input type="text" name="country" value="<?= e($country) ?>" placeholder="e.g., England">
    </label>
    <label>Code
      <input type="text" name="code" value="<?= e($code) ?>" placeholder="e.g., MCI">
    </label>
    <label>League ID (API hint)
      <input type="number" name="last_league_api_id" value="<?= e($league) ?>" placeholder="e.g., 39">
    </label>
    <label>Season (hint)
      <input type="number" name="last_season_hint" value="<?= e($season) ?>" placeholder="e.g., 2020" min="1900" max="2100">
    </label>
    <label>Source
      <select name="is_api">
        <option value="either" <?= $is_api==='either'?'selected':'' ?>>Either</option>
        <option value="yes"    <?= $is_api==='yes'?'selected':'' ?>>API</option>
        <option value="no"     <?= $is_api==='no'?'selected':'' ?>>Manual</option>
      </select>
    </label>
    <label>Sort by
      <select name="sort">
        <option value="created_desc" <?= $sort==='created_desc'?'selected':'' ?>>Newest Created</option>
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
      <a class="button secondary" href="<?= BASE_URL ?>/pages/teams_list.php" style="margin-left:8px;display:inline-block;padding:12px 16px;background:#6c757d;color:#fff;border-radius:8px;text-decoration:none;">Reset</a>
    </div>
  </form>

  <div class="help" style="margin-bottom:12px;">
    Showing <strong><?= $shown ?></strong> of <strong><?= $total ?></strong> matching teams.
  </div>

  <?php if (!$rows): ?>
    <div class="alert info">No results available.</div>
  <?php else: ?>
    <div style="overflow:auto">
      <table style="width:100%;border-collapse:collapse">
        <thead>
          <tr style="text-align:left;border-bottom:1px solid #eee">
            <th style="padding:8px">Logo</th>
            <th style="padding:8px">Name</th>
            <th style="padding:8px">Code</th>
            <th style="padding:8px">Country</th>
            <th style="padding:8px">Founded</th>
            <th style="padding:8px">Venue/City</th>
            <th style="padding:8px">API Hint</th>
            <th style="padding:8px">Source</th>
            <th style="padding:8px">Created</th>
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
            <td style="padding:8px"><?= e(($r['venue_name'] ?: '—') . ' / ' . ($r['city'] ?: '—')) ?></td>
            <td style="padding:8px"><?= e(($r['last_league_api_id'] ?: '—') . ' / ' . ($r['last_season_hint'] ?: '—')) ?></td>
            <td style="padding:8px"><?= !empty($r['is_api']) ? 'API' : 'Manual' ?></td>
            <td style="padding:8px"><?= e($r['created']) ?></td>
            <td style="padding:8px;white-space:nowrap">
              <a href="<?= BASE_URL ?>/pages/teams_view.php?id=<?= (int)$r['id'] ?>">View</a> ·
              <a href="<?= BASE_URL ?>/pages/teams_edit.php?id=<?= (int)$r['id'] ?>">Edit</a> ·
              <a href="<?= BASE_URL ?>/pages/teams_delete.php?id=<?= (int)$r['id'] ?>" onclick="return confirm('Delete this team? This cannot be undone.');">Delete</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php
      $base = BASE_URL . '/pages/teams_list.php?' . http_build_query(array_merge($_GET, ['page' => null]));
      $prev = $page > 1 ? ($base . '&page=' . ($page-1)) : null;
      $next = ($offset + $shown) < $total ? ($base . '&page=' . ($page+1)) : null;
    ?>
    <div style="margin-top:12px;display:flex;gap:8px">
      <?php if ($prev): ?><a class="button secondary" href="<?= e($prev) ?>" style="padding:10px 12px;background:#6c757d;color:#fff;border-radius:8px;text-decoration:none;">← Prev</a><?php endif; ?>
      <?php if ($next): ?><a class="button" href="<?= e($next) ?>" style="padding:10px 12px;background:#0d6efd;color:#fff;border-radius:8px;text-decoration:none;">Next →</a><?php endif; ?>
    </div>
  <?php endif; ?>
</div>
</body>
</html>
