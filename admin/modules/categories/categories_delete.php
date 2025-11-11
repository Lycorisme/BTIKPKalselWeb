<?php
/**
 * Categories Delete Page - Soft Delete Implementation
 * Set deleted_at = NOW() untuk soft delete, bukan menggunakan model method
 */
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

// Only super_admin and admin can delete
if (!hasRole(['super_admin', 'admin'])) {
    setAlert('danger', 'Anda tidak memiliki akses ke halaman ini');
    redirect(ADMIN_URL);
}

$db = Database::getInstance()->getConnection();

// Get category ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    setAlert('danger', 'ID kategori tidak valid.');
    redirect(ADMIN_URL . 'modules/categories/categories_list.php');
}

try {
    // Cek apakah data kategori ada dan belum di-delete
    $stmtCheck = $db->prepare("SELECT * FROM post_categories WHERE id = ? AND deleted_at IS NULL");
    $stmtCheck->execute([$id]);
    $category = $stmtCheck->fetch();
    
    if (!$category) {
        setAlert('danger', 'Kategori tidak ditemukan atau sudah dihapus.');
        redirect(ADMIN_URL . 'modules/categories/categories_list.php');
    }

    // Check if category has posts
    $stmtCheckPosts = $db->prepare("SELECT COUNT(*) FROM posts WHERE category_id = ? AND deleted_at IS NULL");
    $stmtCheckPosts->execute([$id]);
    $postCount = $stmtCheckPosts->fetchColumn();
    
    if ($postCount > 0) {
        setAlert('warning', "Kategori '{$category['name']}' memiliki {$postCount} posts. Silakan pindahkan atau hapus posts terlebih dahulu.");
        redirect(ADMIN_URL . 'modules/categories/categories_list.php');
    }

    // Lakukan soft delete dengan update kolom deleted_at menjadi waktu sekarang
    $stmt = $db->prepare("UPDATE post_categories SET deleted_at = NOW(), updated_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);

    logActivity('DELETE', "Soft delete kategori: {$category['name']}", 'post_categories', $id);
    setAlert('success', "Kategori '{$category['name']}' berhasil dipindahkan ke Trash.");

} catch (PDOException $e) {
    error_log($e->getMessage());
    setAlert('danger', 'Gagal menghapus kategori. Silakan coba lagi.');
}

redirect(ADMIN_URL . 'modules/categories/categories_list.php');
