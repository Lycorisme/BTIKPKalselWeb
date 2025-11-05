<?php
/**
 * Configuration File
 */

// Environment
define('ENVIRONMENT', 'development'); // development | production

// Base URLs
define('BASE_URL', 'http://localhost/btikp-kalsel/public/');
define('ADMIN_URL', 'http://localhost/btikp-kalsel/admin/');
define('UPLOAD_URL', 'http://localhost/btikp-kalsel/uploads/');

// Paths
define('ROOT_PATH', dirname(__DIR__) . '/');
define('UPLOAD_PATH', ROOT_PATH . 'uploads/');

// Site Info
define('SITE_NAME', 'BTIKP Kalimantan Selatan');
define('SITE_TAGLINE', 'Balai Teknologi Informasi dan Komunikasi Pendidikan');

// Pagination
define('ITEMS_PER_PAGE', 10);

// Upload Settings
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_DOC_TYPES', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx']);

// Include database config
require_once 'database.php';
