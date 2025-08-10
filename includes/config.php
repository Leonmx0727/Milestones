<?php
/**
 * UCID: LM64 | Date: 07/08/2025
 * Details: Core configuration (DB creds, error settings, timezone).
 */

declare(strict_types=1);

ini_set('display_errors', '0');          // never show technical errors to users
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../php_errors.log');

date_default_timezone_set('America/New_York');

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'api_project');
define('DB_USER', 'root');
define('DB_PASS', ''); // <-- set your password

// Base URL (adjust if in a subfolder)
define('BASE_URL', '/milestones');
