<?php
/**
 * Permanent Delete from Trash
 * FIXED VERSION - Correct column names for each table
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

// Only super_admin and admin can permanently delete
if (!hasRole(['super_admin', 'admin'])) {
    setAlert('danger', 'Akses ditolak');
    redirect(ADMIN_URL);
}

$db = Database::getInstance()->getConnection();
$type = $_GET['type'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    setAlert('danger', 'ID tidak valid.');
    header("Location: trash_list.php" . ($type ? "?type=$type" : ""));
    exit;
}

/**
 * Function to safely delete files
 */
function deleteFileIfExists($filePath) {
    if (empty($filePath)) {
        return;
    }
    
    $fullPath = __DIR__ . '/../../../public/' . $filePath;
    if (is_file($fullPath)) {
        @unlink($fullPath);
    }
}

try {
    $db->beginTransaction();
    
    switch ($type) {
        case 'post':
            // FIXED: posts table uses 'featured_image' not 'image_path'
            $stmt = $db->prepare("SELECT featured_image FROM posts WHERE id = ?");
            $stmt->execute([$id]);
            $file = $stmt->fetchColumn();
            
            if ($file) {
                deleteFileIfExists($file);
            }
            
            // Delete post tags first (foreign key)
            $db->prepare("DELETE FROM post_tags WHERE post_id = ?")->execute([$id]);
            
            // Delete post
            $stmt = $db->prepare("DELETE FROM posts WHERE id = ?");
            $stmt->execute([$id]);
            
            logActivity('DELETE', "Menghapus permanen post ID: {$id}", 'posts', $id);
            setAlert('success', 'Post berhasil dihapus permanen!');
            break;
            
        case 'service':
            // FIXED: services table uses 'icon' not 'image_path'
            $stmt = $db->prepare("SELECT icon FROM services WHERE id = ?");
            $stmt->execute([$id]);
            $file = $stmt->fetchColumn();
            
            if ($file) {
                deleteFileIfExists($file);
            }
            
            $stmt = $db->prepare("DELETE FROM services WHERE id = ?");
            $stmt->execute([$id]);
            
            logActivity('DELETE', "Menghapus permanen service ID: {$id}", 'services', $id);
            setAlert('success', 'Layanan berhasil dihapus permanen!');
            break;
            
        case 'user':
            // FIXED: users table uses 'avatar' not 'avatar_path'
            $stmt = $db->prepare("SELECT avatar FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $file = $stmt->fetchColumn();
            
            if ($file) {
                deleteFileIfExists($file);
            }
            
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            
            logActivity('DELETE', "Menghapus permanen user ID: {$id}", 'users', $id);
            setAlert('success', 'User berhasil dihapus permanen!');
            break;
            
        case 'file':
            // Correct: downloadable_files uses 'file_path'
            $stmt = $db->prepare("SELECT file_path FROM downloadable_files WHERE id = ?");
            $stmt->execute([$id]);
            $file = $stmt->fetchColumn();
            
            if ($file) {
                deleteFileIfExists($file);
            }
            
            $stmt = $db->prepare("DELETE FROM downloadable_files WHERE id = ?");
            $stmt->execute([$id]);
            
            logActivity('DELETE', "Menghapus permanen file ID: {$id}", 'downloadable_files', $id);
            setAlert('success', 'File berhasil dihapus permanen!');
            break;
            
        case 'album':
            // Delete all photos in album first
            $stmt = $db->prepare("SELECT image_path FROM gallery_photos WHERE album_id = ?");
            $stmt->execute([$id]);
            $photos = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($photos as $photoFile) {
                deleteFileIfExists($photoFile);
            }
            
            // Delete photos from database
            $db->prepare("DELETE FROM gallery_photos WHERE album_id = ?")->execute([$id]);
            
            // FIXED: gallery_albums uses 'cover_image' not 'image_path'
            $stmt = $db->prepare("SELECT cover_image FROM gallery_albums WHERE id = ?");
            $stmt->execute([$id]);
            $file = $stmt->fetchColumn();
            
            if ($file) {
                deleteFileIfExists($file);
            }
            
            $stmt = $db->prepare("DELETE FROM gallery_albums WHERE id = ?");
            $stmt->execute([$id]);
            
            logActivity('DELETE', "Menghapus permanen album ID: {$id} beserta semua foto", 'gallery_albums', $id);
            setAlert('success', 'Album berhasil dihapus permanen (beserta semua foto)!');
            break;
            
        case 'photo':
            // Correct: gallery_photos uses 'image_path'
            $stmt = $db->prepare("SELECT image_path FROM gallery_photos WHERE id = ?");
            $stmt->execute([$id]);
            $file = $stmt->fetchColumn();
            
            if ($file) {
                deleteFileIfExists($file);
            }
            
            $stmt = $db->prepare("DELETE FROM gallery_photos WHERE id = ?");
            $stmt->execute([$id]);
            
            logActivity('DELETE', "Menghapus permanen foto ID: {$id}", 'gallery_photos', $id);
            setAlert('success', 'Foto berhasil dihapus permanen!');
            break;
            
        case 'banner':
            // Correct: banners uses 'image_path'
            $stmt = $db->prepare("SELECT image_path FROM banners WHERE id = ?");
            $stmt->execute([$id]);
            $file = $stmt->fetchColumn();
            
            if ($file) {
                deleteFileIfExists($file);
            }
            
            $stmt = $db->prepare("DELETE FROM banners WHERE id = ?");
            $stmt->execute([$id]);
            
            logActivity('DELETE', "Menghapus permanen banner ID: {$id}", 'banners', $id);
            setAlert('success', 'Banner berhasil dihapus permanen!');
            break;
            
        case 'contact':
            $stmt = $db->prepare("DELETE FROM contact_messages WHERE id = ?");
            $stmt->execute([$id]);
            
            logActivity('DELETE', "Menghapus permanen pesan kontak ID: {$id}", 'contact_messages', $id);
            setAlert('success', 'Pesan kontak berhasil dihapus permanen!');
            break;
            
        case 'page':
            // FIXED: pages table uses 'featured_image' not 'image_path'
            $stmt = $db->prepare("SELECT featured_image FROM pages WHERE id = ?");
            $stmt->execute([$id]);
            $file = $stmt->fetchColumn();
            
            if ($file) {
                deleteFileIfExists($file);
            }
            
            $stmt = $db->prepare("DELETE FROM pages WHERE id = ?");
            $stmt->execute([$id]);
            
            logActivity('DELETE', "Menghapus permanen halaman ID: {$id}", 'pages', $id);
            setAlert('success', 'Halaman berhasil dihapus permanen!');
            break;
            
        default:
            setAlert('danger', 'Tipe data tidak dikenal.');
            $db->rollBack();
            header("Location: trash_list.php");
            exit;
    }
    
    $db->commit();
    
} catch (PDOException $e) {
    $db->rollBack();
    error_log("Trash Delete Error: " . $e->getMessage());
    setAlert('danger', 'Error! Terjadi kesalahan saat menghapus item: ' . $e->getMessage());
}

header("Location: trash_list.php" . ($type ? "?type=$type" : ""));
exit;
