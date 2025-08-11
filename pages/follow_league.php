<?php
/**
 * UCID: LM64 | Date: 11/08/2025
 * Details: Follow/Unfollow a league for the current user.
 * Usage: follow_league.php?action=add|remove&id={league_id}
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_login();

$action    = $_GET['action'] ?? '';
$league_id = (int)($_GET['id'] ?? 0);
$back      = $_SERVER['HTTP_REFERER'] ?? (BASE_URL . '/pages/leagues_list.php');

if ($league_id < 1) { flash('error','Invalid league id.'); header('Location: '.$back); exit; }

// verify league exists
try {
    $chk = db()->prepare('SELECT id, name FROM leagues WHERE id=?');
    $chk->execute([$league_id]);
    $league = $chk->fetch();
    if (!$league) { flash('error','League not found.'); header('Location: '.$back); exit; }
} catch (Throwable $e) {
    error_log('follow_league check: '.$e->getMessage());
    flash('error','Something went wrong. Please try again.');
    header('Location: '.$back); exit;
}

$user = current_user();
try {
    if ($action === 'add') {
        $ins = db()->prepare('INSERT IGNORE INTO user_league_follows (user_id, league_id) VALUES (?, ?)');
        $ins->execute([$user['id'], $league_id]);
        flash('success','Now following: ' . e($league['name']));
    } elseif ($action === 'remove') {
        $del = db()->prepare('DELETE FROM user_league_follows WHERE user_id=? AND league_id=?');
        $del->execute([$user['id'], $league_id]);
        flash('success','Unfollowed: ' . e($league['name']));
    } else {
        flash('error','Invalid action.');
    }
} catch (Throwable $e) {
    error_log('follow_league action: '.$e->getMessage());
    flash('error','We could not update your follows right now.');
}

header('Location: ' . $back); exit;
