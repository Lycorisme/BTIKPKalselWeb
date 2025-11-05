<?php
/**
 * Delete Tag Handler
 * Hard delete tag with validation
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../core/Model.php';
require_once '../../../models/Tag.php';

// Only super_admin and admin can delete
if (!hasRole(['super_admin', 'admin'])) {
    setAlert('danger', 'Anda tidak memiliki akses ke halaman ini');
    redirect(ADMIN_URL);
}

// Get tag ID
$tagId = $_GET['id'] ?? 0;

if (!$tagId) {
    setAlert('danger', 'ID tag tidak valid');
    redirect(ADMIN_URL . 'modules/tags/tags_list.php');
}

$tagModel = new Tag();
$tag = $tagModel->find($tagId);

if (!$tag) {
    setAlert('danger', 'Tag tidak ditemukan');
    redirect(ADMIN_URL . 'modules/tags/tags_list.php');
}

// Delete tag
try {
    error_log("Attempting to HARD DELETE tag ID: {$tagId}, Name: {$tag['name']}");
    
    // =======================================================
    // PERUBAHAN DI SINI: Menggunakan hardDelete (Hapus permanen)
    // =======================================================
    $result = $tagModel->hardDelete($tagId);
    // =======================================================
    
    if ($result) {
        // Log activity
        try {
            logActivity('DELETE', "Menghapus tag: {$tag['name']}", 'tags', $tagId);
        } catch (Exception $e) {
            error_log("Activity log failed: " . $e->getMessage());
        }
        
        setAlert('success', "Tag '{$tag['name']}' berhasil dihapus permanen");
    } else {
        setAlert('danger', 'Gagal menghapus tag');
    }
    
} catch (Exception $e) {
    error_log("Error in delete: " . $e->getMessage());
    // Pesan error ini akan tetap muncul jika tag masih dipakai
    setAlert('danger', 'Tidak dapat menghapus tag: ' . $e->getMessage());
}

redirect(ADMIN_URL . 'modules/tags/tags_list.php');