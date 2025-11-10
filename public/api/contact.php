<?php
/**
 * Contact Form API
 * Handles contact form submissions
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get POST data
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

// Validation
$errors = [];

if (empty($name) || strlen($name) < 2) {
    $errors[] = 'Nama harus diisi minimal 2 karakter';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Email tidak valid';
}

if (empty($subject) || strlen($subject) < 3) {
    $errors[] = 'Subjek harus diisi minimal 3 karakter';
}

if (empty($message) || strlen($message) < 10) {
    $errors[] = 'Pesan harus diisi minimal 10 karakter';
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
$phone = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
$subject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
$message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

// Get user info
$ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

try {
    // Rate limiting: Check if user submitted recently (within 2 minutes)
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM contact_messages 
        WHERE ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL 2 MINUTE)
    ");
    $stmt->execute([$ip_address]);
    $recent_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($recent_count > 0) {
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'message' => 'Mohon tunggu 2 menit sebelum mengirim pesan lagi'
        ]);
        exit;
    }
    
    // Insert contact message
    $stmt = $db->prepare("
        INSERT INTO contact_messages 
        (name, email, phone, subject, message, ip_address, user_agent, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'unread', NOW())
    ");
    
    $stmt->execute([
        $name,
        $email,
        $phone,
        $subject,
        $message,
        $ip_address,
        $user_agent
    ]);
    
    // Get inserted ID
    $message_id = $db->lastInsertId();
    
    // Success response
    echo json_encode([
        'success' => true,
        'message' => 'Pesan berhasil dikirim! Kami akan segera menghubungi Anda.',
        'message_id' => $message_id
    ]);
    
} catch (PDOException $e) {
    error_log('Contact API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan server. Silakan coba lagi.'
    ]);
}
