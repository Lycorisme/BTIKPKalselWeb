<?php

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../core/Model.php';
require_once '../../../models/PostCategory.php';

// Only super_admin and admin can delete
if (!hasRole(['super_admin', 'admin'])) {
    setAlert('danger', 'Anda tidak memiliki akses ke halaman ini');
    redirect(ADMIN_URL);
}

// Get category ID
$categoryId = $_GET['id'] ?? 0;

if (!$categoryId) {
    setAlert('danger', 'ID kategori tidak valid');
    redirect(ADMIN_URL . 'modules/categories/categories_list.php');
}

$categoryModel = new PostCategory();
$category = $categoryModel->find($categoryId);

if (!$category) {
    setAlert('danger', 'Kategori tidak ditemukan');
    redirect(ADMIN_URL . 'modules/categories/categories_list.php');
}

// Delete category
try {
    error_log("Attempting to HARD DELETE category ID: {$categoryId}, Name: {$category['name']}");

    $result = $categoryModel->hardDelete($categoryId); 
    
    if ($result) {
        // Log activity
        try {
            logActivity('DELETE', "Menghapus kategori: {$category['name']}", 'post_categories', $categoryId);
        } catch (Exception $e) {
            error_log("Activity log failed: " . $e->getMessage());
        }
        
        setAlert('success', "Kategori '{$category['name']}' berhasil dihapus permanen");
    } else {
        setAlert('danger', 'Gagal menghapus kategori');
    }
    
} catch (Exception $e) {
    error_log("Error in delete: " . $e->getMessage());
    // Pesan error ini kemungkinan besar akan tetap muncul
    setAlert('danger', 'Tidak dapat menghapus kategori: ' . $e->getMessage());
}

redirect(ADMIN_URL . 'modules/categories/categories_list.php');