<?php
/**
 * UCID: lm64 | Date: 10/08/2025
 * Details: delete league page
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_login();
if (!has_role('admin')) {
    flash('error','You do not have permission to perform this action.');
    redirect('/pages/leagues_list.php');
}

$id = (int)($_GET['id'] ?? 0);
if ($id < 1) {
    flash('error','Invalid league id.');
    redirect('/pages/leagues_list.php');
}

try {
    $stmt = db()->prepare('DELETE FROM leagues WHERE id = ?');
    $stmt->execute([$id]);
    flash('success','League deleted successfully.');
} catch (Throwable $e) {
    error_log('leagues_delete: ' . $e->getMessage());
    flash('error','We could not delete this league right now.');
}

$back = $_SERVER['HTTP_REFERER'] ?? (BASE_URL . '/pages/leagues_list.php');
header('Location: ' . $back);
exit;
