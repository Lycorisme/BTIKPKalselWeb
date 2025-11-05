<?php
/**
 * Database Configuration
 * Portal BTIKP Kalimantan Selatan
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'btikp_kalsel');
define('DB_USER', 'root');          // Default Laragon
define('DB_PASS', '');              // Default Laragon (kosong)
define('DB_CHARSET', 'utf8mb4');

// Timezone
date_default_timezone_set('Asia/Makassar');

// Error Reporting (Development)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session Configuration - HANYA jika session belum dimulai
if (session_status() === PHP_SESSION_NONE) {
    // Set ini_set SEBELUM session_start
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set 1 jika HTTPS
    
    // Start session
    session_start();
}
