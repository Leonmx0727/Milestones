<?php
/**
 * UCID: LM64 | Date: 08/08/2025
 * Details: Auth utilities: login, logout, guards, role checks.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// check if user is logged in
function is_logged_in(): bool {
    return !empty($_SESSION['user']);
}

// if not logged in, go to login page
function require_login(): void {
    if (!is_logged_in()) {
        flash('error', 'Please log in to continue.');
        redirect('/pages/login.php');
    }
}

// get the current user
function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

// get roles for a user
function load_user_roles(int $user_id): array {
    try {
        $stmt = db()->prepare('
            SELECT r.name 
            FROM user_roles ur 
            JOIN roles r ON r.id = ur.role_id AND r.is_active = 1
            WHERE ur.user_id = ? AND ur.is_active = 1
        ');
        $stmt->execute([$user_id]);
        return array_column($stmt->fetchAll(), 'name');
    } catch (Throwable $e) {
        error_log('load_user_roles: ' . $e->getMessage());
        return [];
    }
}

// check if user has a role
function has_role(string $role): bool {
    $roles = $_SESSION['roles'] ?? [];
    return in_array($role, $roles, true);
}

// log in a user
function login_user(array $user): void {
    $_SESSION['user']  = [
        'id'       => (int)$user['id'],
        'username' => $user['username'],
        'email'    => $user['email'],
    ];
    $_SESSION['roles'] = load_user_roles((int)$user['id']);
}

// log out a user
function logout_user(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time()-42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
