<?php
require_once 'config/config.php';
require_once 'core/Database.php';
require_once 'core/Helper.php';
require_once 'core/Model.php';
require_once 'models/Post.php';
require_once 'models/PostCategory.php';
require_once 'models/Tag.php';

echo "<h1>Testing Model Classes</h1>";

// Test 1: PostCategory Model
echo "<h2>1. PostCategory Model Test</h2>";
try {
    $categoryModel = new PostCategory();
    $categories = $categoryModel->getActive();
    echo "✅ PostCategory initialized<br>";
    echo "Total active categories: " . count($categories) . "<br>";
    
    if (!empty($categories)) {
        echo "<ul>";
        foreach ($categories as $cat) {
            echo "<li>{$cat['name']} ({$cat['slug']})</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 2: Post Model
echo "<h2>2. Post Model Test</h2>";
try {
    $postModel = new Post();
    $stats = $postModel->getStats();
    echo "✅ Post Model initialized<br>";
    echo "Total posts: " . ($stats['total'] ?? 0) . "<br>";
    echo "Published: " . ($stats['published'] ?? 0) . "<br>";
    echo "Draft: " . ($stats['draft'] ?? 0) . "<br>";
    echo "Total views: " . formatNumber($stats['total_views'] ?? 0) . "<br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 3: Tag Model
echo "<h2>3. Tag Model Test</h2>";
try {
    $tagModel = new Tag();
    echo "✅ Tag Model initialized<br>";
    
    // Test find or create
    $tag = $tagModel->findOrCreate('Test Tag');
    if ($tag) {
        echo "✅ Tag found/created: {$tag['name']} (ID: {$tag['id']})<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<h3>✅ All model tests completed!</h3>";

echo '<hr><a href="admin/modules/posts/posts_list.php">→ Go to Posts List</a>';
