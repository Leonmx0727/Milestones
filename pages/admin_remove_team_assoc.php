<?php
/**
 * UCID: LM64 | Date: 11/08/2025
 * Details: Admin removes a specific user↔team relationship.
 * Usage: admin_remove_team_assoc.php?uid={user_id}&tid={team_id}
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_login();
if (!has_role('admin')) { flash('error','Admins only.'); redirect('/pages/home.php'); }

$uid = (int)($_GET['uid'] ?? 0);
$tid = (int)($_GET['tid'] ?? 0);
$back = $_SERVER['HTTP_REFERER'] ?? (BASE_URL . '/pages/admin_associations_teams.php');

if ($uid < 1 || $tid < 1) { flash('error','Invalid request.'); header('Location: '.$back); exit; }

try {
  $stmt = db()->prepare('DELETE FROM user_team_favorites WHERE user_id=? AND team_id=?');
  $stmt->execute([$uid, $tid]);
  flash('success','Association removed.');
} catch (Throwable $e) {
  error_log('admin_remove_team_assoc: '.$e->getMessage());
  flash('error','Could not remove association right now.');
}

header('Location: '.$back); exit;
