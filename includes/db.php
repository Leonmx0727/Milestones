<?php
/**
 * UCID: LM64 | Date: 07/08/2025
 * Details: my database connection file
 */

require_once __DIR__ . '/config.php';

// pdo connection
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (Throwable $e) {
            error_log('DB Connect Failed: ' . $e->getMessage());
            die('Something went wrong. Please try again later.');
        }
    }
    return $pdo;
}
