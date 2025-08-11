<?php
/**
 * UCID: lm64d | Date: 09/08/2025
 * Details: Destroy session and redirect to login with a success message.
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

logout_user();
flash('success', 'You have been logged out.');
redirect('/pages/login.php');
