<?php
/**
 * AJAX: Reorder Photos
 * Update display_order untuk drag & drop sorting
 */

require_once '../../../includes/auth_check.php';
require_once '../../../../core/Database.php';
require_once '../../../../core/Helper.php';

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

$photoIds = $input['photo_ids'] ?? [];
$albumId = $input['album_id'] ?? null;

if (empty($photoIds) || !$albumId) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid parameters'
    ]);
    exit;
}

try {
    $db->beginTransaction();
    
    // Update display_order untuk setiap foto
    $stmt = $db->prepare("
        UPDATE gallery_photos 
        SET display_order = ? 
        WHERE id = ? AND album_id = ? AND deleted_at IS NULL
    ");
    
    foreach ($photoIds as $order => $photoId) {
        $stmt->execute([$order, $photoId, $albumId]);
    }
    
    $db->commit();
    
    // Log activity
    logActivity('UPDATE', "Reorder foto di album ID: {$albumId}", 'gallery_photos', null);
    
    echo json_encode([
        'success' => true,
        'message' => 'Photos reordered successfully'
    ]);
    
} catch (PDOException $e) {
    $db->rollBack();
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error'
    ]);
}
