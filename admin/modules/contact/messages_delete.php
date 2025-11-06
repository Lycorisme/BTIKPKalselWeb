<?php
/**
 * Contact Messages - Delete (Soft Delete)
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

$db = Database::getInstance()->getConnection();

// Get message ID
$messageId = $_GET['id'] ?? null;

if (!$messageId) {
    setAlert('danger', 'Pesan tidak ditemukan.');
    redirect(ADMIN_URL . 'modules/contact/messages_list.php');
}

// Get message data for logging
// Pastikan pesan ada dan belum di-soft-delete
$stmt = $db->prepare("SELECT * FROM contact_messages WHERE id = ? AND deleted_at IS NULL");
$stmt->execute([$messageId]);
$message = $stmt->fetch();

if (!$message) {
    setAlert('danger', 'Pesan tidak ditemukan.');
    redirect(ADMIN_URL . 'modules/contact/messages_list.php');
}

try {
    // PERUBAHAN: Gunakan UPDATE untuk soft delete, bukan DELETE
    $stmt = $db->prepare("UPDATE contact_messages SET deleted_at = NOW() WHERE id = ?");
    $stmt->execute([$messageId]);
    
    // Log activity (tindakan tetap 'DELETE' dari perspektif pengguna)
    logActivity('DELETE', "Menghapus pesan dari: {$message['name']} ({$message['email']})", 'contact_messages', $messageId);
    
    setAlert('success', 'Pesan berhasil dihapus!');
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    setAlert('danger', 'Gagal menghapus pesan. Silakan coba lagi.');
}

redirect(ADMIN_URL . 'modules/contact/messages_list.php');