<?php
/**
 * Pages - Delete
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

$db = Database::getInstance()->getConnection();

$id = $_GET['id'] ?? null;
if (!$id) {
    setAlert('danger', 'Halaman tidak ditemukan.');
    redirect(ADMIN_URL . 'modules/pages/pages_list.php');
}

$stmt = $db->prepare("SELECT * FROM pages WHERE id = ?");
$stmt->execute([$id]);
$page = $stmt->fetch();

if (!$page) {
    setAlert('danger', 'Halaman tidak ditemukan.');
    redirect(ADMIN_URL . 'modules/pages/pages_list.php');
}

try {
    $stmtDelete = $db->prepare("DELETE FROM pages WHERE id = ?");
    $stmtDelete->execute([$id]);
    logActivity('DELETE', "Menghapus halaman: {$page['title']}", 'pages', $id);
    setAlert('success', 'Halaman berhasil dihapus!');
} catch (PDOException $e) {
    error_log($e->getMessage());
    setAlert('danger', 'Gagal menghapus halaman. Silakan coba lagi.');
}
redirect(ADMIN_URL . 'modules/pages/pages_list.php');
