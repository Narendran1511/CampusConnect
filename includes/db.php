<?php
/**
 * includes/db.php - configuration + PDO connection.
 * Edit the DB_* constants if your MySQL credentials differ (XAMPP default: root / empty password).
 */
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'campusconnect_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('APP_TIMEZONE', 'Asia/Kolkata');
define('CERTIFICATES_ENABLED', true);
define('MAX_POSTER_BYTES', 2 * 1024 * 1024); // 2 MB

date_default_timezone_set(APP_TIMEZONE);

// Auto-detect the URL of the project root (e.g. /campusconnect), regardless of sub-folder.
$__dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
$__base = preg_replace('#/(admin|student|actions)$#', '', $__dir);
define('BASE_URL', rtrim($__base, '/'));
define('ROOT_PATH', dirname(__DIR__));

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER, DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            http_response_code(500);
            exit('Database connection failed. Check includes/db.php and make sure MySQL is running and campusconnect.sql is imported.');
        }
    }
    return $pdo;
}
