<?php
session_start();
require_once '../core/Database.php';
require_once '../core/Helper.php';

echo "<h1>User Role Debug</h1>";

if (isLoggedIn()) {
    $user = getCurrentUser();
    echo "<pre>";
    print_r($user);
    echo "</pre>";
    
    echo "<hr>";
    echo "<strong>Role dari session:</strong> " . $_SESSION['user_role'] . "<br>";
    echo "<strong>ID:</strong> " . $_SESSION['user_id'] . "<br>";
    
    echo "<hr>";
    echo "<h3>Test hasRole:</h3>";
    echo "hasRole('admin'): " . (hasRole('admin') ? '✅ YES' : '❌ NO') . "<br>";
    echo "hasRole('super_admin'): " . (hasRole('super_admin') ? '✅ YES' : '❌ NO') . "<br>";
    echo "hasRole(['admin', 'super_admin']): " . (hasRole(['admin', 'super_admin']) ? '✅ YES' : '❌ NO') . "<br>";
    
} else {
    echo "Not logged in";
}
