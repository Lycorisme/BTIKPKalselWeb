<?php
/**
 * Database Configuration - Environment Based
 * Automatic switch between Development & Production
 */

// ============================================
// ENVIRONMENT DETECTION
// ============================================
// Auto-detect environment based on domain
$isDevelopment = (
    $_SERVER['HTTP_HOST'] === 'localhost' || 
    strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false ||
    strpos($_SERVER['HTTP_HOST'], '.local') !== false ||
    strpos($_SERVER['HTTP_HOST'], '.test') !== false
);

// Force environment (optional - uncomment jika perlu)
// $isDevelopment = true;  // Force development
// $isDevelopment = false; // Force production

// ============================================
// DATABASE CREDENTIALS
// ============================================

if ($isDevelopment) {
    // ============== DEVELOPMENT (Localhost) ==============
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'btikp_kalsel');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_PORT', '3306');
    
    // Error reporting ON untuk development
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    
} else {
    // ============== PRODUCTION (Hosting) ==============
    // ⚠️ GANTI DENGAN CREDENTIALS HOSTING NANTI!
    define('DB_HOST', 'localhost');           // Biasanya 'localhost' di shared hosting
    define('DB_NAME', 'u123456_btikp');       // Database name dari hosting
    define('DB_USER', 'u123456_admin');       // Database username dari hosting
    define('DB_PASS', 'password_hosting');     // Database password dari hosting
    define('DB_PORT', '3306');
    
    // Error reporting OFF untuk production
    error_reporting(0);
    ini_set('display_errors', 0);
}

define('DB_CHARSET', 'utf8mb4');

// ============================================
// TIMEZONE
// ============================================
date_default_timezone_set('Asia/Makassar'); // WITA (GMT+8)

// ============================================
// SESSION CONFIGURATION
// ============================================
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    
    // Auto-detect HTTPS untuk session security
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
               (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    ini_set('session.cookie_secure', $isHttps ? 1 : 0);
    
    session_start();
}

// ============================================
// DATABASE CONNECTION (mysqli) - OPTIONAL
// Keep this if you want direct mysqli connection
// ============================================
/*
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
if ($db->connect_errno) {
    if ($isDevelopment) {
        die('Database connection failed: ' . $db->connect_error);
    } else {
        die('Database connection failed. Please contact administrator.');
    }
}
$db->set_charset(DB_CHARSET);
*/

// ============================================
// DEBUG INFO (Development Only)
// ============================================
if ($isDevelopment) {
    // echo "<!-- Database Config: " . ($isDevelopment ? 'DEVELOPMENT' : 'PRODUCTION') . " -->\n";
    // echo "<!-- DB Host: " . DB_HOST . " -->\n";
    // echo "<!-- DB Name: " . DB_NAME . " -->\n";
}
