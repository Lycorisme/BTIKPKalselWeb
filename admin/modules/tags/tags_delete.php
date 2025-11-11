<?php
/**
 * Tags Delete Page - Soft Delete Implementation
 * Set deleted_at = NOW() untuk soft delete (seperti kategori dan services)
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

// Get tag ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    setAlert('danger', 'ID tag tidak valid.');
    redirect(ADMIN_URL . 'modules/tags/tags_list.php');
}

try {
    // Cek apakah data tag ada dan belum di-delete
    $stmtCheck = $db->prepare("SELECT * FROM tags WHERE id = ? AND deleted_at IS NULL");
    $stmtCheck->execute([$id]);
    $tag = $stmtCheck->fetch();
    
    if (!$tag) {
        setAlert('danger', 'Tag tidak ditemukan atau sudah dihapus.');
        redirect(ADMIN_URL . 'modules/tags/tags_list.php');
    }

    // Check if tag has posts
    $stmtCheckPosts = $db->prepare("SELECT COUNT(*) FROM post_tags WHERE tag_id = ?");
    $stmtCheckPosts->execute([$id]);
    $postCount = $stmtCheckPosts->fetchColumn();
    
    if ($postCount > 0) {
        setAlert('warning', "Tag '{$tag['name']}' memiliki {$postCount} posts. Silakan hapus tag dari posts terlebih dahulu.");
        redirect(ADMIN_URL . 'modules/tags/tags_list.php');
    }

    // Lakukan soft delete dengan update kolom deleted_at menjadi waktu sekarang
    $stmt = $db->prepare("UPDATE tags SET deleted_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);

    logActivity('DELETE', "Soft delete tag: {$tag['name']}", 'tags', $id);
    setAlert('success', "Tag '{$tag['name']}' berhasil dipindahkan ke Trash.");

} catch (PDOException $e) {
    error_log($e->getMessage());
    setAlert('danger', 'Gagal menghapus tag. Silakan coba lagi.');
}

redirect(ADMIN_URL . 'modules/tags/tags_list.php');
