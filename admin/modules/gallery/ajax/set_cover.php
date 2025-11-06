<?php
/**
 * AJAX: Set Photo as Album Cover
 */

require_once '../../../includes/auth_check.php';
require_once '../../../../core/Database.php';
require_once '../../../../core/Helper.php';

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

$photoId = $input['photo_id'] ?? null;
$albumId = $input['album_id'] ?? null;

if (!$photoId || !$albumId) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid parameters'
    ]);
    exit;
}

try {
    // Get photo filename
    $stmt = $db->prepare("SELECT filename FROM gallery_photos WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([$photoId]);
    $photo = $stmt->fetch();
    
    if (!$photo) {
        echo json_encode([
            'success' => false,
            'message' => 'Photo not found'
        ]);
        exit;
    }
    
    // Update album cover
    $stmt = $db->prepare("UPDATE gallery_albums SET cover_photo = ? WHERE id = ?");
    $stmt->execute([$photo['filename'], $albumId]);
    
    // Log activity
    logActivity('UPDATE', "Set cover photo untuk album ID: {$albumId}", 'gallery_albums', $albumId);
    
    echo json_encode([
        'success' => true,
        'message' => 'Cover photo updated successfully'
    ]);
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error'
    ]);
}
