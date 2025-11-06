<?php
/**
 * AJAX Live Search Helper
 */

require_once '../../../includes/auth_check.php';
require_once '../../../../core/Database.php';

$db = Database::getInstance()->getConnection();

$q = trim($_GET['q'] ?? '');
$type = $_GET['type'] ?? '';

if (!$q) {
    echo json_encode([]);
    exit;
}

// Modules supported
$modules = ['posts','services','users','files','albums','photos'];

if ($type && !in_array($type, $modules)) {
    $type = '';
}

$results = [];
$limit = 10;

function highlight($text, $query) {
    $escapedQuery = preg_quote($query, '/');
    return preg_replace_callback("/($escapedQuery)/i", function ($matches) {
        return '<mark>' . $matches[0] . '</mark>';
    }, $text);
}

if (!$type || $type === 'posts') {
    $sql = "SELECT id, title FROM posts WHERE (title LIKE ? OR content LIKE ?) AND deleted_at IS NULL ORDER BY created_at DESC LIMIT $limit";
    $stmt = $db->prepare($sql);
    $param = "%{$q}%";
    $stmt->execute([$param,$param]);
    foreach ($stmt->fetchAll() as $row) {
        $results[] = [
            'id' => $row['id'],
            'label' => highlight($row['title'], $q),
            'url' => ADMIN_URL . "modules/posts/posts_edit.php?id=" . $row['id']
        ];
    }
}

if (!$type || $type === 'services') {
    $sql = "SELECT id, title FROM services WHERE (title LIKE ? OR description LIKE ?) AND deleted_at IS NULL ORDER BY created_at DESC LIMIT $limit";
    $stmt = $db->prepare($sql);
    $stmt->execute([$param,$param]);
    foreach ($stmt->fetchAll() as $row) {
        $results[] = [
            'id' => $row['id'],
            'label' => highlight($row['title'], $q),
            'url' => ADMIN_URL . "modules/services/services_edit.php?id=" . $row['id']
        ];
    }
}

if (!$type || $type === 'users') {
    $sql = "SELECT id, name FROM users WHERE (name LIKE ? OR email LIKE ?) AND deleted_at IS NULL ORDER BY created_at DESC LIMIT $limit";
    $stmt = $db->prepare($sql);
    $stmt->execute([$param,$param]);
    foreach ($stmt->fetchAll() as $row) {
        $results[] = [
            'id' => $row['id'],
            'label' => highlight($row['name'], $q),
            'url' => ADMIN_URL . "modules/users/users_edit.php?id=" . $row['id']
        ];
    }
}

if (!$type || $type === 'files') {
    $sql = "SELECT id, title FROM downloadable_files WHERE (title LIKE ? OR description LIKE ?) AND deleted_at IS NULL ORDER BY created_at DESC LIMIT $limit";
    $stmt = $db->prepare($sql);
    $stmt->execute([$param,$param]);
    foreach ($stmt->fetchAll() as $row) {
        $results[] = [
            'id' => $row['id'],
            'label' => highlight($row['title'], $q),
            'url' => ADMIN_URL . "modules/files/files_edit.php?id=" . $row['id']
        ];
    }
}

if (!$type || $type === 'albums') {
    $sql = "SELECT id, name FROM gallery_albums WHERE (name LIKE ? OR description LIKE ?) AND deleted_at IS NULL ORDER BY created_at DESC LIMIT $limit";
    $stmt = $db->prepare($sql);
    $stmt->execute([$param,$param]);
    foreach ($stmt->fetchAll() as $row) {
        $results[] = [
            'id' => $row['id'],
            'label' => highlight($row['name'], $q),
            'url' => ADMIN_URL . "modules/gallery/albums_edit.php?id=" . $row['id']
        ];
    }
}

if (!$type || $type === 'photos') {
    $sql = "SELECT id, title FROM gallery_photos WHERE (title LIKE ? OR caption LIKE ?) AND deleted_at IS NULL ORDER BY created_at DESC LIMIT $limit";
    $stmt = $db->prepare($sql);
    $stmt->execute([$param,$param]);
    foreach ($stmt->fetchAll() as $row) {
        $results[] = [
            'id' => $row['id'],
            'label' => highlight($row['title'], $q),
            'url' => ADMIN_URL . "modules/gallery/photos_edit.php?id=" . $row['id']
        ];
    }
}

echo json_encode($results);
