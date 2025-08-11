<?php
/**
 * UCID: LM64 | Date: 11/08/2025
 * Details: Add/Remove a team favorite for the current user.
 * Usage: favorite_team.php?action=add|remove&id={team_id}
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_login();

$action  = $_GET['action'] ?? '';
$team_id = (int)($_GET['id'] ?? 0);
$back    = $_SERVER['HTTP_REFERER'] ?? (BASE_URL . '/pages/teams_list.php');

if ($team_id < 1) { flash('error','Invalid team id.'); header('Location: '.$back); exit; }

// verify team exists
try {
    $chk = db()->prepare('SELECT id, name FROM teams WHERE id=?');
    $chk->execute([$team_id]);
    $team = $chk->fetch();
    if (!$team) { flash('error','Team not found.'); header('Location: '.$back); exit; }
} catch (Throwable $e) {
    error_log('favorite_team check: '.$e->getMessage());
    flash('error','Something went wrong. Please try again.');
    header('Location: '.$back); exit;
}

$user = current_user();
try {
    if ($action === 'add') {
        $ins = db()->prepare('INSERT IGNORE INTO user_team_favorites (user_id, team_id) VALUES (?, ?)');
        $ins->execute([$user['id'], $team_id]);
        flash('success','Added to favorites: ' . e($team['name']));
    } elseif ($action === 'remove') {
        $del = db()->prepare('DELETE FROM user_team_favorites WHERE user_id=? AND team_id=?');
        $del->execute([$user['id'], $team_id]);
        flash('success','Removed from favorites: ' . e($team['name']));
    } else {
        flash('error','Invalid action.');
    }
} catch (Throwable $e) {
    error_log('favorite_team action: '.$e->getMessage());
    flash('error','We could not update your favorites right now.');
}

header('Location: ' . $back); exit;
