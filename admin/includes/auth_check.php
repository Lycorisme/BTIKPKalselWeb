<?php
/**
 * Authentication Check
 * Include di setiap halaman admin yang memerlukan login
 */

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/core/Helper.php';

// Cek apakah user sudah login
if (!isLoggedIn()) {
    setAlert('warning', 'Silakan login terlebih dahulu');
    redirect(ADMIN_URL . 'login.php');
    exit;
}

// Optional: Cek role untuk authorization
function checkRole($allowedRoles = []) {
    $user = getCurrentUser();
    if (!empty($allowedRoles) && !in_array($user['role'], $allowedRoles)) {
        setAlert('danger', 'Anda tidak memiliki akses ke halaman ini');
        redirect(ADMIN_URL);
        exit;
    }
}

// Refresh session untuk prevent session fixation
if (!isset($_SESSION['last_activity']) || (time() - $_SESSION['last_activity'] > 1800)) {
    $_SESSION['last_activity'] = time();
}
