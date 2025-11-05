<?php

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../core/Model.php';
require_once '../../../models/Post.php';

// Only super_admin and admin can delete
if (!hasRole(['super_admin', 'admin'])) {
    setAlert('danger', 'Anda tidak memiliki akses ke halaman ini');
    redirect(ADMIN_URL);
}

// Get post ID
$postId = $_GET['id'] ?? 0;

if (!$postId) {
    setAlert('danger', 'ID post tidak valid');
    redirect(ADMIN_URL . 'modules/posts/posts_list.php');
}

$postModel = new Post();
$post = $postModel->getById($postId);

if (!$post) {
    setAlert('danger', 'Post tidak ditemukan');
    redirect(ADMIN_URL . 'modules/posts/posts_list.php');
}

// Delete post
try {
    error_log("Attempting to delete post ID: {$postId}, Title: {$post['title']}");
    
    $result = $postModel->softDelete($postId);
    
    if ($result) {
        // Log activity
        try {
            logActivity('DELETE', "Menghapus post: {$post['title']}", 'posts', $postId);
        } catch (Exception $e) {
            error_log("Activity log failed: " . $e->getMessage());
        }
        
        setAlert('success', "Post '{$post['title']}' berhasil dihapus");
    } else {
        setAlert('danger', 'Gagal menghapus post');
    }
    
} catch (Exception $e) {
    error_log("Error in delete: " . $e->getMessage());
    setAlert('danger', 'Tidak dapat menghapus post: ' . $e->getMessage());
}

redirect(ADMIN_URL . 'modules/posts/posts_list.php');
