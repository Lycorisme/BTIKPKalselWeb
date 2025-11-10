<?php
/**
 * Like API - DEBUGGING VERSION
 */

// Enable all errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Log request
file_put_contents(__DIR__ . '/../../storage/logs/like_debug.log', 
    date('Y-m-d H:i:s') . ' - Request received' . "\n", 
    FILE_APPEND
);

try {
    require_once __DIR__ . '/../config.php';
    
    // Check request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON: ' . json_last_error_msg());
    }
    
    $post_id = isset($input['post_id']) ? (int)$input['post_id'] : 0;
    
    if ($post_id <= 0) {
        throw new Exception('Invalid post ID: ' . $post_id);
    }
    
    // Check if post exists
    $stmt = $db->prepare("SELECT id FROM posts WHERE id = ? AND status = 'published' AND deleted_at IS NULL");
    $stmt->execute([$post_id]);
    
    if (!$stmt->fetch()) {
        throw new Exception('Post not found');
    }
    
    // Get user IP
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Check if already liked
    $stmt = $db->prepare("SELECT id FROM post_likes WHERE post_id = ? AND ip_address = ?");
    $stmt->execute([$post_id, $ip_address]);
    $existing_like = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_like) {
        // Unlike
        $stmt = $db->prepare("DELETE FROM post_likes WHERE id = ?");
        $stmt->execute([$existing_like['id']]);
        $action = 'unliked';
    } else {
        // Like
        $stmt = $db->prepare("INSERT INTO post_likes (post_id, ip_address, user_agent, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$post_id, $ip_address, $user_agent]);
        $action = 'liked';
    }
    
    // Get updated count
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM post_likes WHERE post_id = ?");
    $stmt->execute([$post_id]);
    $likes_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Log success
    file_put_contents(__DIR__ . '/../../storage/logs/like_debug.log', 
        date('Y-m-d H:i:s') . ' - SUCCESS: ' . $action . ' - Count: ' . $likes_count . "\n", 
        FILE_APPEND
    );
    
    echo json_encode([
        'success' => true,
        'action' => $action,
        'likes_count' => $likes_count,
        'message' => $action === 'liked' ? 'Post liked!' : 'Like removed',
        'debug' => [
            'post_id' => $post_id,
            'ip' => $ip_address
        ]
    ]);
    
} catch (Exception $e) {
    // Log error
    file_put_contents(__DIR__ . '/../../storage/logs/like_debug.log', 
        date('Y-m-d H:i:s') . ' - ERROR: ' . $e->getMessage() . "\n", 
        FILE_APPEND
    );
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error' => $e->getMessage()
    ]);
}
