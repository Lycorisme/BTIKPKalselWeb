<?php

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../core/Model.php';
require_once '../../../models/Service.php';

// Only super_admin and admin can delete
if (!hasRole(['super_admin', 'admin'])) {
    setAlert('danger', 'Anda tidak memiliki akses ke halaman ini');
    redirect(ADMIN_URL);
}

// Get service ID
$serviceId = $_GET['id'] ?? 0;

if (!$serviceId) {
    setAlert('danger', 'ID layanan tidak valid');
    redirect(ADMIN_URL . 'modules/services/services_list.php');
}

$serviceModel = new Service();
$service = $serviceModel->find($serviceId);

if (!$service) {
    setAlert('danger', 'Layanan tidak ditemukan');
    redirect(ADMIN_URL . 'modules/services/services_list.php');
}

// Delete service
try {
    error_log("Attempting to delete service ID: {$serviceId}, Title: {$service['title']}");
    
    $result = $serviceModel->softDelete($serviceId);
    
    if ($result) {
        // Log activity
        try {
            logActivity('DELETE', "Menghapus layanan: {$service['title']}", 'services', $serviceId);
        } catch (Exception $e) {
            error_log("Activity log failed: " . $e->getMessage());
        }
        
        setAlert('success', "Layanan '{$service['title']}' berhasil dihapus");
    } else {
        setAlert('danger', 'Gagal menghapus layanan');
    }
    
} catch (PDOException $e) {
    error_log("PDO Error in delete: " . $e->getMessage());
    setAlert('danger', 'Gagal menghapus layanan: ' . $e->getMessage());
} catch (Exception $e) {
    error_log("General Error in delete: " . $e->getMessage());
    setAlert('danger', 'Terjadi kesalahan: ' . $e->getMessage());
}

redirect(ADMIN_URL . 'modules/services/services_list.php');
