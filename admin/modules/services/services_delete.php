<?php
/**
 * Services - Soft Delete
 */
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

$db = Database::getInstance()->getConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    setAlert('danger', 'ID layanan tidak valid.');
    redirect(ADMIN_URL . 'modules/services/services_list.php');
}

try {
    // Cek apakah data layanan ada
    $stmtCheck = $db->prepare("SELECT * FROM services WHERE id = ?");
    $stmtCheck->execute([$id]);
    $service = $stmtCheck->fetch();
    if (!$service) {
        setAlert('danger', 'Layanan tidak ditemukan.');
        redirect(ADMIN_URL . 'modules/services/services_list.php');
    }

    // Lakukan soft delete dengan update kolom deleted_at menjadi waktu sekarang
    $stmt = $db->prepare("UPDATE services SET deleted_at = NOW(), updated_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);

    logActivity('DELETE', "Soft delete layanan: {$service['title']}", 'services', $id);
    setAlert('success', 'Layanan berhasil dihapus (masuk Trash).');
} catch (PDOException $e) {
    error_log($e->getMessage());
    setAlert('danger', 'Gagal menghapus layanan. Silakan coba lagi.');
}

redirect(ADMIN_URL . 'modules/services/services_list.php');
