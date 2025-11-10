<?php
/**
 * Download File Handler
 * Tracks download count and serves file
 */

require_once __DIR__ . '/../config.php';

// Get file ID
$file_id = $_GET['id'] ?? 0;

if (empty($file_id) || !is_numeric($file_id)) {
    die('Invalid file ID');
}

try {
    // Get file info
    $stmt = $db->prepare("
        SELECT * FROM downloadable_files 
        WHERE id = ? AND is_active = 1 AND deleted_at IS NULL
    ");
    $stmt->execute([$file_id]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$file) {
        die('File not found');
    }
    
    // Build full file path
    $file_path = __DIR__ . '/../' . $file['file_path'];
    
    // Check if file exists
    if (!file_exists($file_path)) {
        die('File not found on server');
    }
    
    // Increment download count
    $stmt = $db->prepare("UPDATE downloadable_files SET download_count = download_count + 1 WHERE id = ?");
    $stmt->execute([$file_id]);
    
    // Log download activity
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $stmt = $db->prepare("
        INSERT INTO activity_logs 
        (user_id, action_type, description, model_type, model_id, ip_address, user_agent, created_at)
        VALUES (NULL, 'DOWNLOAD', ?, 'downloadable_files', ?, ?, ?, NOW())
    ");
    $stmt->execute([
        'Download file: ' . $file['title'],
        $file_id,
        $ip_address,
        $user_agent
    ]);
    
    // Set headers for download
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $file['mime_type']);
    header('Content-Disposition: attachment; filename="' . basename($file['file_path']) . '"');
    header('Content-Length: ' . $file['file_size']);
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    
    // Clear output buffer
    ob_clean();
    flush();
    
    // Read and output file
    readfile($file_path);
    exit;
    
} catch (Exception $e) {
    error_log('Download Error: ' . $e->getMessage());
    die('Error downloading file');
}
