<?php
/**
 * Database Configuration
 * Portal BTIKP Kalimantan Selatan
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'btikp_kalsel');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

date_default_timezone_set('Asia/Makassar');
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0);
    session_start();
}

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_errno) {
    die('Database connection failed: ' . $db->connect_error);
}
$db->set_charset(DB_CHARSET);
?>
