<?php
/**
 * UCID: LM64 | Date: 08/08/2025
 * Details: Common helpers: flash messages, redirects, sanitization, sticky form.
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function flash(string $key, ?string $message = null) {
    if ($message === null) {
        if (!empty($_SESSION['flash'][$key])) {
            $msg = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $msg;
        }
        return null;
    }
    $_SESSION['flash'][$key] = $message;
}

function redirect(string $path): void {
    header('Location: ' . BASE_URL . $path);
    exit;
}

/** Keep previous POST values (sticky form) */
function old(string $key, string $default = ''): string {
    return isset($_POST[$key]) ? e($_POST[$key]) : $default;
}

/** Simple banner rendering */
function render_flash(): void {
    foreach (['success','error','info'] as $type) {
        $msg = flash($type);
        if ($msg) {
            echo '<div class="alert '.$type.'">'.e($msg).'</div>';
        }
    }
}
