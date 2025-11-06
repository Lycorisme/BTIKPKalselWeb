<?php
/**
 * Gallery Photos - Delete
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

$db = Database::getInstance()->getConnection();

// Get photo ID and album ID
$photoId = $_GET['id'] ?? null;
$albumId = $_GET['album_id'] ?? null;

if (!$photoId) {
    setAlert('danger', 'Foto tidak ditemukan.');
    redirect(ADMIN_URL . 'modules/gallery/albums_list.php');
}

// Get photo data
$stmt = $db->prepare("SELECT * FROM gallery_photos WHERE id = ? AND deleted_at IS NULL");
$stmt->execute([$photoId]);
$photo = $stmt->fetch();

if (!$photo) {
    setAlert('danger', 'Foto tidak ditemukan.');
    redirect(ADMIN_URL . 'modules/gallery/albums_list.php');
}

// Use album_id from photo if not provided
if (!$albumId) {
    $albumId = $photo['album_id'];
}

try {
    // Soft delete photo
    $stmt = $db->prepare("UPDATE gallery_photos SET deleted_at = NOW() WHERE id = ?");
    $stmt->execute([$photoId]);
    
    // Log activity
    logActivity('DELETE', "Menghapus foto: {$photo['title']}", 'gallery_photos', $photoId);
    
    setAlert('success', 'Foto berhasil dihapus!');
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    setAlert('danger', 'Gagal menghapus foto. Silakan coba lagi.');
}

redirect(ADMIN_URL . 'modules/gallery/photos_list.php?album_id=' . $albumId);
