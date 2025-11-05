<?php
/**
 * AJAX Upload Image Handler
 * Untuk CKEditor image upload
 */

// Require dependencies SEBELUM session_start
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/core/Database.php';
require_once dirname(__DIR__, 2) . '/core/Upload.php';
require_once dirname(__DIR__, 2) . '/core/Helper.php';

// Set JSON response header
header('Content-Type: application/json');

// Function untuk send JSON response
function sendJson($data) {
    echo json_encode($data);
    exit;
}

// Check if user is logged in
if (!isLoggedIn()) {
    sendJson([
        'error' => [
            'message' => 'Unauthorized. Please login first.'
        ]
    ]);
}

// Check if file exists in request
if (!isset($_FILES['upload']) || empty($_FILES['upload']['name'])) {
    sendJson([
        'error' => [
            'message' => 'No file uploaded. Field name must be "upload"'
        ]
    ]);
}

// Initialize Upload class
try {
    $upload = new Upload();
    
    // Upload file
    $filePath = $upload->upload($_FILES['upload'], 'posts');
    
    if ($filePath) {
        // Success - Return CKEditor format
        sendJson([
            'url' => uploadUrl($filePath),
            'uploaded' => 1,
            'fileName' => basename($filePath)
        ]);
    } else {
        // Failed - Get errors
        $errors = $upload->getErrors();
        $errorMessage = !empty($errors) ? implode(', ', $errors) : 'Upload failed';
        
        sendJson([
            'error' => [
                'message' => $errorMessage
            ],
            'uploaded' => 0
        ]);
    }
    
} catch (Exception $e) {
    // Exception occurred
    error_log('Upload error: ' . $e->getMessage());
    
    sendJson([
        'error' => [
            'message' => 'Server error: ' . $e->getMessage()
        ],
        'uploaded' => 0
    ]);
}
