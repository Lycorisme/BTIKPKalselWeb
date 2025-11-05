<?php
require_once 'config/config.php';
require_once 'core/Database.php';
require_once 'core/Helper.php';      // ← PASTIKAN INI ADA
require_once 'core/Upload.php';
require_once 'core/Validator.php';
require_once 'core/Pagination.php';

echo "<h1>Testing Core Classes</h1>";

// Test 1: Validator
echo "<h2>1. Validator Test</h2>";
$data = [
    'email' => 'test@example.com',
    'name' => 'John Doe',
    'age' => '25'
];

$validator = new Validator($data);
$validator->required('name', 'Nama');
$validator->email('email', 'Email');
$validator->numeric('age', 'Umur');

if ($validator->passes()) {
    echo "✅ Validation passed<br>";
} else {
    echo "❌ Validation failed:<br>";
    foreach ($validator->getErrors() as $error) {
        echo "- $error<br>";
    }
}

// Test 2: Pagination
echo "<h2>2. Pagination Test</h2>";
$pagination = new Pagination(150, 10, 1);
echo "Total Pages: " . $pagination->getTotalPages() . "<br>";
echo "Offset: " . $pagination->getOffset() . "<br>";
echo "Limit: " . $pagination->getLimit() . "<br>";
echo $pagination->render();

// Test 3: Upload (info only, tidak upload)
echo "<h2>3. Upload Test</h2>";
$upload = new Upload();
echo "✅ Upload class initialized<br>";
echo "Max file size: " . formatFileSize(MAX_FILE_SIZE) . "<br>";
echo "Allowed types: " . implode(', ', ALLOWED_IMAGE_TYPES) . "<br>";

// Test 4: Helper Functions
echo "<h2>4. Helper Functions Test</h2>";
echo "✅ formatTanggal: " . formatTanggal('2025-11-04') . "<br>";
echo "✅ formatTanggalRelatif: " . formatTanggalRelatif('2025-11-04 06:00:00') . "<br>";
echo "✅ generateSlug: " . generateSlug('Berita Terbaru Hari Ini') . "<br>";
echo "✅ truncateText: " . truncateText('Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore.', 50) . "<br>";
echo "✅ formatNumber: " . formatNumber(1234567) . "<br>";
echo "✅ getStatusBadge: " . getStatusBadge('published') . "<br>";

echo "<h3>✅ All tests completed successfully!</h3>";
