<?php
// UCID: LM64 | Date: 11/08/2025
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_login();
if (!has_role('admin')) { flash('error','Admins only.'); redirect('/pages/home.php'); }

$uid = (int)($_GET['uid'] ?? 0);
$lid = (int)($_GET['lid'] ?? 0);
$back = $_SERVER['HTTP_REFERER'] ?? (BASE_URL . '/pages/admin_associations_leagues.php');

if ($uid < 1 || $lid < 1) { flash('error','Invalid request.'); header('Location: '.$back); exit; }

try {
  $stmt = db()->prepare('DELETE FROM user_league_follows WHERE user_id=? AND league_id=?');
  $stmt->execute([$uid, $lid]);
  flash('success','Association removed.');
} catch (Throwable $e) {
  error_log('admin_remove_league_assoc: '.$e->getMessage());
  flash('error','Could not remove association right now.');
}
header('Location: '.$back); exit;
