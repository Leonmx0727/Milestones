<?php
/**
 * UCID: lm64d | Date: 07/08/2025
 * Details: Entry redirect to login or home.
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/pages/home.php');
} else {
    header('Location: ' . BASE_URL . '/pages/login.php');
}
exit;
