<?php
/**
 * UCID: LM64 | Date: 07/08/2025
 * Details: my config file
 */

declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../php_errors.log');

date_default_timezone_set('America/New_York');

// db stuff
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'api_project');
define('DB_USER', 'root');
define('DB_PASS', ''); 

// url
define('BASE_URL', '/milestones');

// api stuff
define('RAPIDAPI_KEY', 'YOUR_API_KEY'); 
define('RAPIDAPI_HOST', 'api-football-v1.p.rapidapi.com');
define('API_BASE_URL', 'https://api-football-v1.p.rapidapi.com/v3'); 

