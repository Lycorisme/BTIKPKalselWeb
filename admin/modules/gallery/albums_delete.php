<?php
/**
 * Gallery Albums - Delete
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

$db = Database::getInstance()->getConnection();

// Get album ID
$albumId = $_GET['id'] ?? null;

if (!$albumId) {
    setAlert('danger', 'Album tidak ditemukan.');
    redirect(ADMIN_URL . 'modules/gallery/albums_list.php');
}

// Get album data
$stmt = $db->prepare("SELECT * FROM gallery_albums WHERE id = ? AND deleted_at IS NULL");
$stmt->execute([$albumId]);
$album = $stmt->fetch();

if (!$album) {
    setAlert('danger', 'Album tidak ditemukan.');
    redirect(ADMIN_URL . 'modules/gallery/albums_list.php');
}

try {
    // Soft delete album
    $stmt = $db->prepare("UPDATE gallery_albums SET deleted_at = NOW() WHERE id = ?");
    $stmt->execute([$albumId]);
    
    // Soft delete all photos in this album
    $stmt = $db->prepare("UPDATE gallery_photos SET deleted_at = NOW() WHERE album_id = ? AND deleted_at IS NULL");
    $stmt->execute([$albumId]);
    
    // Log activity
    logActivity('DELETE', "Menghapus album gallery: {$album['name']}", 'gallery_albums', $albumId);
    
    setAlert('success', 'Album dan semua foto di dalamnya berhasil dihapus!');
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    setAlert('danger', 'Gagal menghapus album. Silakan coba lagi.');
}

redirect(ADMIN_URL . 'modules/gallery/albums_list.php');
