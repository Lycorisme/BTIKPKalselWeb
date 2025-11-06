<?php
/**
 * Delete User Handler
 * Soft delete user with validation & debugging
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../core/Model.php';
require_once '../../../models/User.php';

// Only super_admin and admin can access
if (!hasRole(['super_admin', 'admin'])) {
    setAlert('danger', 'Anda tidak memiliki akses ke halaman ini');
    redirect(ADMIN_URL);
}

// Get user ID
$userId = $_GET['id'] ?? 0;

if (!$userId) {
    setAlert('danger', 'ID pengguna tidak valid');
    redirect(ADMIN_URL . 'modules/users/users_list.php');
}

$userModel = new User();
$user = $userModel->find($userId);

if (!$user) {
    setAlert('danger', 'Pengguna tidak ditemukan');
    redirect(ADMIN_URL . 'modules/users/users_list.php');
}

// Prevent deleting self
if ($user['id'] == getCurrentUser()['id']) {
    setAlert('danger', 'Tidak bisa menghapus akun sendiri');
    redirect(ADMIN_URL . 'modules/users/users_list.php');
}

// Prevent deleting super_admin by non-super_admin
if ($user['role'] == 'super_admin' && !hasRole('super_admin')) {
    setAlert('danger', 'Anda tidak bisa menghapus Super Admin');
    redirect(ADMIN_URL . 'modules/users/users_list.php');
}

// Delete user
try {
    // Debug log
    error_log("Attempting to SOFT DELETE user ID: {$userId}, Name: {$user['name']}");
    
    // ----- PERBAIKAN DI SINI -----
    // Pastikan kita memanggil 'softDelete' (sesuai Model.php dan User.php)
    // BUKAN 'delete'
    $result = $userModel->softDelete($userId);
    // ----- BATAS PERBAIKAN -----
    
    // Debug result
    error_log("Delete result: " . var_export($result, true));
    
    if ($result) {
        // Log activity
        try {
            logActivity('DELETE', "Menghapus pengguna: {$user['name']}", 'users', $userId);
        } catch (Exception $e) {
            error_log("Activity log failed: " . $e->getMessage());
        }
        
        setAlert('success', "Pengguna {$user['name']} berhasil dihapus");
    } else {
        setAlert('danger', 'Gagal menghapus pengguna. Tidak ada perubahan data.');
    }
    
} catch (PDOException $e) {
    error_log("PDO Error in delete: " . $e->getMessage());
    setAlert('danger', 'Gagal menghapus pengguna: ' . $e->getMessage());
} catch (Exception $e) {
    error_log("General Error in delete: " . $e->getMessage());
    setAlert('danger', 'Terjadi kesalahan: ' . $e->getMessage());
}

redirect(ADMIN_URL . 'modules/users/users_list.php');