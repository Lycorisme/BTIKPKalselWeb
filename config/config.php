<?php
/**
 * Configuration File - Auto-detect BASE_URL
 * Works on localhost & hosting (HTTP/HTTPS)
 */

// ============================================
// ENVIRONMENT
// ============================================
define('ENVIRONMENT', 'development'); // development | production

// ============================================
// ERROR REPORTING
// ============================================
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// ============================================
// AUTO-DETECT BASE URL
// Works: localhost & hosting, HTTP & HTTPS
// ============================================

// Detect protocol (HTTP or HTTPS)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';

// Get host (e.g., localhost or yourdomain.com)
$host = $_SERVER['HTTP_HOST'];

// Get script path and extract base directory
$scriptPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
// Example: /btikp-kalsel/public or /public or /

// Extract base directory (up to /public)
$pathParts = explode('/', trim($scriptPath, '/'));
// Example: ['btikp-kalsel', 'public'] or ['public'] or ['btikp-kalsel', 'admin']

// Find 'public' or 'admin' in path
$publicIndex = array_search('public', $pathParts);
$adminIndex = array_search('admin', $pathParts);

if ($publicIndex !== false) {
    // We're in public folder
    $baseParts = array_slice($pathParts, 0, $publicIndex);
    $baseDir = '/' . implode('/', $baseParts);
} elseif ($adminIndex !== false) {
    // We're in admin folder
    $baseParts = array_slice($pathParts, 0, $adminIndex);
    $baseDir = '/' . implode('/', $baseParts);
} else {
    // Fallback: take first directory
    $baseDir = '/' . ($pathParts[0] ?? '');
}

// Clean up base dir
$baseDir = rtrim($baseDir, '/');
if (empty($baseDir)) {
    $baseDir = '';
}

// Define base URLs
define('BASE_URL', $protocol . $host . $baseDir . '/public/');
define('ADMIN_URL', $protocol . $host . $baseDir . '/admin/');
define('UPLOAD_URL', $protocol . $host . $baseDir . '/uploads/');

// ============================================
// PATHS (Filesystem)
// ============================================
define('ROOT_PATH', dirname(__DIR__) . '/');
define('UPLOAD_PATH', ROOT_PATH . 'uploads/');

// ============================================
// SITE INFO
// ============================================
define('SITE_NAME', 'BTIKP Kalimantan Selatan');
define('SITE_TAGLINE', 'Balai Teknologi Informasi dan Komunikasi Pendidikan');

// ============================================
// PAGINATION
// ============================================
define('ITEMS_PER_PAGE', 10);

// ============================================
// UPLOAD SETTINGS
// ============================================
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_DOC_TYPES', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx']);

// ============================================
// TIMEZONE
// ============================================
date_default_timezone_set('Asia/Makassar'); // WITA (GMT+8)

// ============================================
// INCLUDE DATABASE CONFIG
// ============================================
require_once __DIR__ . '/database.php';