<?php
/**
 * Logout Handler
 */

require_once '../config/config.php';
require_once '../core/Database.php';
require_once '../core/Helper.php';

// Log aktivitas logout
if (isLoggedIn()) {
    try {
        $db = Database::getInstance()->getConnection();
        $user = getCurrentUser();
        
        $stmt = $db->prepare("
            INSERT INTO activity_logs (user_id, user_name, action_type, description, ip_address) 
            VALUES (?, ?, 'LOGOUT', 'User melakukan logout', ?)
        ");
        $stmt->execute([
            $user['id'], 
            $user['name'], 
            $_SERVER['REMOTE_ADDR']
        ]);
    } catch (PDOException $e) {
        error_log($e->getMessage());
    }
}

// Hapus semua session
session_destroy();

// Hapus remember me cookie
if (isset($_COOKIE['remember_user'])) {
    setcookie('remember_user', '', time() - 3600, '/');
}

// Redirect ke login
redirect(ADMIN_URL . 'login.php');
