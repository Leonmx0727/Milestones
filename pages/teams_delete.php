<?php
/**
 * UCID: lm64 | Date: 10/08/2025
 * Details: delete team
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_login();
if (!has_role('admin')) { flash('error','You do not have permission to perform this action.'); redirect('/pages/teams_list.php'); }

$id = (int)($_GET['id'] ?? 0);
if ($id < 1) { flash('error','Invalid team id.'); redirect('/pages/teams_list.php'); }

try {
  $stmt = db()->prepare('DELETE FROM teams WHERE id = ?');
  $stmt->execute([$id]);
  flash('success','Team deleted successfully.');
} catch (Throwable $e) {
  error_log('teams_delete: '.$e->getMessage());
  flash('error','We could not delete this team right now.');
}

$back = $_SERVER['HTTP_REFERER'] ?? (BASE_URL . '/pages/teams_list.php');
header('Location: ' . $back); exit;
