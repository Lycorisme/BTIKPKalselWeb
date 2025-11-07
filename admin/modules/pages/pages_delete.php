<?php
/**
 * Pages - Soft Delete
 */
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

$db = Database::getInstance()->getConnection();

// Ambil ID halaman yang akan dihapus
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    setAlert('danger', 'Halaman tidak ditemukan.');
    redirect(ADMIN_URL . 'modules/pages/pages_list.php');
    exit;
}

// Cek apakah halaman ada
$stmt = $db->prepare("SELECT * FROM pages WHERE id = ?");
$stmt->execute([$id]);
$page = $stmt->fetch();

if (!$page) {
    setAlert('danger', 'Halaman tidak ditemukan.');
    redirect(ADMIN_URL . 'modules/pages/pages_list.php');
    exit;
}

try {
    // Soft delete: update deleted_at, updated_at
    $stmt = $db->prepare("UPDATE pages SET deleted_at = NOW(), updated_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);

    // Log aktivitas
    logActivity('DELETE', "Soft delete halaman: {$page['title']}", 'pages', $id);

    setAlert('success', 'Halaman berhasil dihapus dan masuk Trash.');
} catch (PDOException $e) {
    error_log($e->getMessage());
    setAlert('danger', 'Gagal menghapus halaman. Silakan coba lagi.');
}

// Redirect selalu, jangan blank page
redirect(ADMIN_URL . 'modules/pages/pages_list.php');
exit;
