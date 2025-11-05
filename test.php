<?php
require_once 'config/config.php';
require_once 'core/Database.php';

echo "<h1>Testing Instalasi Portal BTIKP</h1>";

// Test 1: Koneksi Database
try {
    $db = Database::getInstance()->getConnection();
    echo "✅ <strong>Database Connection:</strong> SUCCESS<br>";
} catch (Exception $e) {
    echo "❌ <strong>Database Connection:</strong> FAILED - " . $e->getMessage() . "<br>";
}

// Test 2: Cek Tabel
try {
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "✅ <strong>Total Tables:</strong> " . count($tables) . "<br>";
} catch (Exception $e) {
    echo "❌ <strong>Tables Check:</strong> FAILED<br>";
}

// Test 3: Cek Upload Folder
$uploadDirs = ['posts', 'pages', 'schools', 'galleries', 'files'];
foreach ($uploadDirs as $dir) {
    $path = UPLOAD_PATH . $dir;
    if (is_dir($path) && is_writable($path)) {
        echo "✅ <strong>Upload/$dir:</strong> Writable<br>";
    } else {
        echo "❌ <strong>Upload/$dir:</strong> Not writable or not exists<br>";
    }
}

echo "<br><strong>Base URL:</strong> " . BASE_URL;
echo "<br><strong>Admin URL:</strong> " . ADMIN_URL;
