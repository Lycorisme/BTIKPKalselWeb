<?php
/**
 * Comment API - Auto-Approve Version
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get POST data with correct field names
$post_id = $_POST['post_id'] ?? 0;
$name = trim($_POST['author_name'] ?? '');
$email = trim($_POST['author_email'] ?? '');
$content = trim($_POST['content'] ?? '');
$parent_id = $_POST['parent_id'] ?? null;

// Validation
$errors = [];

if (empty($post_id) || !is_numeric($post_id)) {
    $errors[] = 'Invalid post ID';
}

if (empty($name) || strlen($name) < 2) {
    $errors[] = 'Nama harus diisi minimal 2 karakter';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Email tidak valid';
}

if (empty($content) || strlen($content) < 10) {
    $errors[] = 'Komentar harus diisi minimal 10 karakter';
}

// Return validation errors
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => implode(', ', $errors)
    ]);
    exit;
}

// Sanitize input
$name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
$content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');

// Get user info
$ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

try {
    // Check if post exists
    $stmt = $db->prepare("SELECT id FROM posts WHERE id = ? AND status = 'published' AND deleted_at IS NULL");
    $stmt->execute([$post_id]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Post tidak ditemukan']);
        exit;
    }
    
    // Rate limiting: Check if user commented recently (within 1 minute)
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM comments 
        WHERE ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
    ");
    $stmt->execute([$ip_address]);
    $recent_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($recent_count > 0) {
        http_response_code(429);
        echo json_encode([
            'success' => false, 
            'message' => 'Mohon tunggu 1 menit sebelum mengirim komentar lagi'
        ]);
        exit;
    }
    
    // ✅ FIX: Insert comment dengan status 'approved' (auto-approve)
    $stmt = $db->prepare("
        INSERT INTO comments 
        (commentable_type, commentable_id, parent_id, name, email, content, ip_address, user_agent, status, created_at)
        VALUES ('post', ?, ?, ?, ?, ?, ?, ?, 'approved', NOW())
    ");
    
    $stmt->execute([
        $post_id,
        $parent_id,
        $name,
        $email,
        $content,
        $ip_address,
        $user_agent
    ]);
    
    // Get inserted comment ID
    $comment_id = $db->lastInsertId();
    
    // Success response
    echo json_encode([
        'success' => true,
        'message' => 'Komentar berhasil ditambahkan!',
        'comment_id' => $comment_id,
        'reload' => true // Signal to reload page
    ]);
    
} catch (PDOException $e) {
    error_log('Comment API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Terjadi kesalahan server. Silakan coba lagi.'
    ]);
}
