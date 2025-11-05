<?php
session_start();

// Simulate logged in user
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Test User';
$_SESSION['user_email'] = 'test@test.com';
$_SESSION['user_role'] = 'admin';

require_once 'config/config.php';
require_once 'core/Helper.php';

echo "<h1>Test Upload Handler Access</h1>";

echo "<p><strong>Upload Handler Path:</strong> " . ADMIN_URL . "ajax/upload_image.php</p>";

echo "<p><strong>Session Check:</strong> " . (isLoggedIn() ? '✅ Logged In' : '❌ Not Logged In') . "</p>";

echo "<form action='" . ADMIN_URL . "ajax/upload_image.php' method='POST' enctype='multipart/form-data'>";
echo "<input type='file' name='upload' required><br><br>";
echo "<button type='submit'>Test Upload</button>";
echo "</form>";
