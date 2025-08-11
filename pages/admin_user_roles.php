<?php
/**
 * UCID: LM64 | Date: 2025-08-12
 * Details: Admin view of ALL user-role associations with filters, stats, and bulk management.
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_login();
if (!has_role('admin')) { flash('error','Admins only.'); redirect('/pages/home.php'); }

/** ---------- Handle Role Assignment/Removal (POST) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'assign_role') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        $role_id = (int)($_POST['role_id'] ?? 0);
        
        if ($user_id && $role_id) {
            try {
                $stmt = db()->prepare('INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, ?)');
                $stmt->execute([$user_id, $role_id]);
                flash('success', 'Role assigned successfully.');
            } catch (Throwable $e) {
                error_log('admin_user_roles assign: '.$e->getMessage());
                flash('error', 'Could not assign role.');
            }
        }
    }
    
    if ($action === 'remove_role') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        $role_id = (int)($_POST['role_id'] ?? 0);
        
        if ($user_id && $role_id) {
            try {
                $stmt = db()->prepare('DELETE FROM user_roles WHERE user_id = ? AND role_id = ?');
                $stmt->execute([$user_id, $role_id]);
                flash('success', 'Role removed successfully.');
            } catch (Throwable $e) {
                error_log('admin_user_roles remove: '.$e->getMessage());
                flash('error', 'Could not remove role.');
            }
        }
    }
    
    if ($action === 'remove_all') {
        try {
            $username = trim($_POST['username'] ?? '');
            $role_name = trim($_POST['role_name'] ?? '');
            $is_active = $_POST['is_active'] ?? 'either';

            $where = [];
            $params = [];
            if ($username !== '') { $where[] = 'u.username LIKE ?'; $params[] = '%'.$username.'%'; }
            if ($role_name !== '') { $where[] = 'r.name LIKE ?'; $params[] = '%'.$role_name.'%'; }
            if ($is_active === 'yes') { $where[] = 'ur.is_active = 1'; }
            elseif ($is_active === 'no') { $where[] = 'ur.is_active = 0'; }

            $whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';
            $sql = "DELETE ur FROM user_roles ur
                    JOIN users u ON u.id = ur.user_id
                    JOIN roles r ON r.id = ur.role_id
                    $whereSql";
            $stmt = db()->prepare($sql);
            $stmt->execute($params);
            flash('success', 'Removed all matching role assignments.');
        } catch (Throwable $e) {
            error_log('admin_user_roles remove_all: '.$e->getMessage());
            flash('error', 'Could not remove role assignments.');
        }
    }
    
    redirect('/pages/admin_user_roles.php?'.http_build_query($_GET));
}

/** ---------- Filters (GET) ---------- */
$username = trim($_GET['username'] ?? '');
$role_name = trim($_GET['role_name'] ?? '');
$is_active = $_GET['is_active'] ?? 'either';
$sort = $_GET['sort'] ?? 'recent';
$limit = (int)($_GET['limit'] ?? 10);
$page = (int)($_GET['page'] ?? 1);
if ($limit < 1 || $limit > 100) $limit = 10;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$where = [];
$params = [];
if ($username !== '') { $where[] = 'u.username LIKE ?'; $params[] = '%'.$username.'%'; }
if ($role_name !== '') { $where[] = 'r.name LIKE ?'; $params[] = '%'.$role_name.'%'; }
if ($is_active === 'yes') { $where[] = 'ur.is_active = 1'; }
elseif ($is_active === 'no') { $where[] = 'ur.is_active = 0'; }
$whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

switch ($sort) {
    case 'username_asc': $orderBy = 'ORDER BY u.username ASC, r.name ASC'; break;
    case 'role_asc': $orderBy = 'ORDER BY r.name ASC, u.username ASC'; break;
    default: $orderBy = 'ORDER BY ur.created DESC, ur.id DESC';
}

/** ---------- Stats ---------- */
try {
    $tot = db()->query('SELECT COUNT(*) FROM user_roles WHERE is_active = 1')->fetchColumn();
    $total_all = (int)$tot;
} catch (Throwable $e) { error_log('admin_user_roles total_all: '.$e->getMessage()); $total_all = 0; }

try {
    $csql = "SELECT COUNT(*) FROM user_roles ur
             JOIN users u ON u.id = ur.user_id
             JOIN roles r ON r.id = ur.role_id
             $whereSql";
    $stmt = db()->prepare($csql);
    $stmt->execute($params);
    $total_filtered = (int)$stmt->fetchColumn();
} catch (Throwable $e) { error_log('admin_user_roles total_filtered: '.$e->getMessage()); $total_filtered = 0; }

/** ---------- Fetch page ---------- */
try {
    $sql = "SELECT ur.id as assignment_id, ur.created as assigned_on, ur.is_active,
                   u.id as user_id, u.username, u.email,
                   r.id as role_id, r.name as role_name, r.description
            FROM user_roles ur
            JOIN users u ON u.id = ur.user_id
            JOIN roles r ON r.id = ur.role_id
            $whereSql
            $orderBy
            LIMIT $limit OFFSET $offset";
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log('admin_user_roles fetch: '.$e->getMessage());
    $rows = [];
}
$shown = count($rows);

/** ---------- Get all users and roles for dropdown ---------- */
try {
    $users_stmt = db()->query('SELECT id, username FROM users ORDER BY username');
    $all_users = $users_stmt->fetchAll();
    
    $roles_stmt = db()->query('SELECT id, name, description FROM roles WHERE is_active = 1 ORDER BY name');
    $all_roles = $roles_stmt->fetchAll();
} catch (Throwable $e) {
    error_log('admin_user_roles dropdowns: '.$e->getMessage());
    $all_users = [];
    $all_roles = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin • User Role Associations</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/styles.css">
    <script src="<?= BASE_URL ?>/js/scripts.js" defer></script>
</head>
<body>
<?php render_navbar('admin'); ?>

<div class="container">
    <?php render_flash(); ?>
    <h1>All Users – Role Associations</h1>

    <div class="help" style="margin-bottom:8px;">
        Total active assignments: <strong><?= $total_all ?></strong> • Showing: <strong><?= $shown ?></strong> of <strong><?= $total_filtered ?></strong>
    </div>

    <!-- Quick Role Assignment -->
    <div style="margin-bottom:20px;padding:16px;background:#f8f9fa;border-radius:8px;">
        <h3 style="margin:0 0 12px 0;">Quick Role Assignment</h3>
        <form method="post" style="display:grid;gap:12px;grid-template-columns:1fr 1fr auto;align-items:end;">
            <label>User
                <select name="user_id" required>
                    <option value="">Select User...</option>
                    <?php foreach ($all_users as $user): ?>
                        <option value="<?= (int)$user['id'] ?>"><?= e($user['username']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Role
                <select name="role_id" required>
                    <option value="">Select Role...</option>
                    <?php foreach ($all_roles as $role): ?>
                        <option value="<?= (int)$role['id'] ?>"><?= e($role['name']) ?> - <?= e($role['description']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div>
                <input type="hidden" name="action" value="assign_role">
                <button type="submit" class="button">Assign Role</button>
            </div>
        </form>
    </div>

    <!-- Filters -->
    <form method="get" onsubmit="return validateAdminAssocFilters(this)" style="margin-bottom:12px;display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));align-items:end">
        <label>Username (partial)
            <input type="text" name="username" value="<?= e($username) ?>" placeholder="e.g., admin">
        </label>
        <label>Role Name (partial)
            <input type="text" name="role_name" value="<?= e($role_name) ?>" placeholder="e.g., admin">
        </label>
        <label>Status
            <select name="is_active">
                <option value="either" <?= $is_active==='either'?'selected':'' ?>>Either</option>
                <option value="yes" <?= $is_active==='yes'?'selected':'' ?>>Active</option>
                <option value="no" <?= $is_active==='no'?'selected':'' ?>>Inactive</option>
            </select>
        </label>
        <label>Sort
            <select name="sort">
                <option value="recent" <?= $sort==='recent'?'selected':'' ?>>Recent Assignments</option>
                <option value="username_asc" <?= $sort==='username_asc'?'selected':'' ?>>Username A→Z</option>
                <option value="role_asc" <?= $sort==='role_asc'?'selected':'' ?>>Role A→Z</option>
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
            <a class="button secondary" href="<?= BASE_URL ?>/pages/admin_user_roles.php" style="margin-left:8px;display:inline-block;padding:12px 16px;background:#6c757d;color:#fff;border-radius:8px;text-decoration:none;">Reset</a>
        </div>
    </form>

    <?php if (!$rows): ?>
        <div class="alert info">No results available.</div>
    <?php else: ?>
        <form method="post" onsubmit="return confirm('Remove all matching role assignments? This cannot be undone.');" style="margin-bottom:12px">
            <input type="hidden" name="action" value="remove_all">
            <input type="hidden" name="username" value="<?= e($username) ?>">
            <input type="hidden" name="role_name" value="<?= e($role_name) ?>">
            <input type="hidden" name="is_active" value="<?= e($is_active) ?>">
            <button type="submit" class="button secondary">Remove All (matching)</button>
        </form>

        <div style="overflow:auto">
            <table style="width:100%;border-collapse:collapse">
                <thead>
                    <tr style="text-align:left;border-bottom:1px solid #eee">
                        <th style="padding:8px">User</th>
                        <th style="padding:8px">Email</th>
                        <th style="padding:8px">Role</th>
                        <th style="padding:8px">Description</th>
                        <th style="padding:8px">Status</th>
                        <th style="padding:8px">Assigned On</th>
                        <th style="padding:8px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr style="border-bottom:1px solid #f0f0f0">
                        <td style="padding:8px"><strong><?= e($r['username']) ?></strong></td>
                        <td style="padding:8px"><?= e($r['email']) ?></td>
                        <td style="padding:8px"><span style="padding:4px 8px;background:#e3f2fd;border-radius:4px;font-size:0.9em"><?= e($r['role_name']) ?></span></td>
                        <td style="padding:8px"><?= e($r['description'] ?? '—') ?></td>
                        <td style="padding:8px">
                            <span style="padding:4px 8px;border-radius:4px;font-size:0.9em;background:<?= $r['is_active'] ? '#d4edda' : '#f8d7da' ?>;color:<?= $r['is_active'] ? '#155724' : '#721c24' ?>">
                                <?= $r['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td style="padding:8px"><?= e($r['assigned_on']) ?></td>
                        <td style="padding:8px;white-space:nowrap">
                            <form method="post" style="display:inline" onsubmit="return confirm('Remove this role assignment?');">
                                <input type="hidden" name="action" value="remove_role">
                                <input type="hidden" name="user_id" value="<?= (int)$r['user_id'] ?>">
                                <input type="hidden" name="role_id" value="<?= (int)$r['role_id'] ?>">
                                <button type="submit" style="background:none;border:none;color:#dc3545;cursor:pointer;text-decoration:underline;">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php
            $base = BASE_URL . '/pages/admin_user_roles.php?' . http_build_query(array_merge($_GET, ['page' => null]));
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
function validateAdminAssocFilters(form) {
    const limit = form.limit.value.trim();
    const page = form.page.value.trim();
    if (!limit || +limit < 1 || +limit > 100) { alert("Limit must be between 1 and 100."); return false; }
    if (page && +page < 1) { alert("Page must be 1 or greater."); return false; }
    return true;
}
</script>

</body>
</html>
