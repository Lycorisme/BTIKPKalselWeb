<?php
/**
 * AJAX: Quick Delete Photo
 * Delete foto tanpa page reload
 */

require_once '../../../includes/auth_check.php';
require_once '../../../../core/Database.php';
require_once '../../../../core/Helper.php';

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

$photoId = $input['photo_id'] ?? null;

if (!$photoId) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid photo ID'
    ]);
    exit;
}

try {
    // Get photo data untuk logging
    $stmt = $db->prepare("SELECT title, album_id FROM gallery_photos WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([$photoId]);
    $photo = $stmt->fetch();
    
    if (!$photo) {
        echo json_encode([
            'success' => false,
            'message' => 'Photo not found'
        ]);
        exit;
    }
    
    // Soft delete photo
    $stmt = $db->prepare("UPDATE gallery_photos SET deleted_at = NOW() WHERE id = ?");
    $stmt->execute([$photoId]);
    
    // Log activity
    $photoTitle = $photo['title'] ?? 'Untitled Photo';
    logActivity('DELETE', "Quick delete foto: {$photoTitle}", 'gallery_photos', $photoId);
    
    echo json_encode([
        'success' => true,
        'message' => 'Photo deleted successfully',
        'album_id' => $photo['album_id']
    ]);
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error'
    ]);
}
