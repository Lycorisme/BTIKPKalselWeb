<?php
/**
 * View Post Page
 * Display post details
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../core/Model.php';
require_once '../../../models/Post.php';

$pageTitle = 'Detail Post';

$postModel = new Post();

// Get post ID
$postId = $_GET['id'] ?? 0;
$post = $postModel->getById($postId);

if (!$post) {
    setAlert('danger', 'Post tidak ditemukan');
    redirect(ADMIN_URL . 'modules/posts/posts_list.php');
}

// Get tags
$postTags = $postModel->getTags($postId);

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6">
                <h3><?= $pageTitle ?></h3>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="posts_list.php">Post</a></li>
                        <li class="breadcrumb-item active">Detail</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="row">
            <!-- Main Content -->
            <div class="col-md-8">
                <!-- Post Content Card -->
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="card-title mb-0"><?= htmlspecialchars($post['title']) ?></h4>
                            <?php if ($post['is_featured']): ?>
                                <span class="badge bg-warning text-dark">
                                    <i class="bi bi-star-fill"></i> Featured
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($post['featured_image']): ?>
                        <img src="<?= uploadUrl($post['featured_image']) ?>" 
                             alt="<?= htmlspecialchars($post['title']) ?>" 
                             class="card-img-top">
                    <?php endif; ?>
                    
                    <div class="card-body">
                        <!-- Meta Info -->
                        <div class="mb-3 pb-3 border-bottom">
                            <div class="row g-2">
                                <div class="col-auto">
                                    <span class="badge bg-primary">
                                        <i class="bi bi-folder"></i> <?= htmlspecialchars($post['category_name']) ?>
                                    </span>
                                </div>
                                <div class="col-auto">
                                    <?= getStatusBadge($post['status']) ?>
                                </div>
                                <div class="col-auto">
                                    <small class="text-muted">
                                        <i class="bi bi-person"></i> <?= htmlspecialchars($post['author_name']) ?>
                                    </small>
                                </div>
                                <div class="col-auto">
                                    <small class="text-muted">
                                        <i class="bi bi-calendar"></i> <?= formatTanggal($post['published_at'] ?: $post['created_at'], 'd M Y H:i') ?>
                                    </small>
                                </div>
                                <div class="col-auto">
                                    <small class="text-muted">
                                        <i class="bi bi-eye"></i> <?= formatNumber($post['view_count']) ?> views
                                    </small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Excerpt -->
                        <?php if ($post['excerpt']): ?>
                            <div class="alert alert-light">
                                <strong>Ringkasan:</strong><br>
                                <?= htmlspecialchars($post['excerpt']) ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Content -->
                        <div class="post-content">
                            <?= $post['content'] ?>
                        </div>
                        
                        <!-- Tags -->
                        <?php if (!empty($postTags)): ?>
                            <div class="mt-4 pt-3 border-top">
                                <strong class="d-block mb-2">Tags:</strong>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ($postTags as $tag): ?>
                                        <span class="badge bg-secondary">
                                            #<?= htmlspecialchars($tag['name']) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- SEO Info Card -->
                <?php if ($post['meta_title'] || $post['meta_description'] || $post['meta_keywords']): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">SEO Information</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($post['meta_title']): ?>
                                <div class="mb-2">
                                    <strong>Meta Title:</strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($post['meta_title']) ?></small>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($post['meta_description']): ?>
                                <div class="mb-2">
                                    <strong>Meta Description:</strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($post['meta_description']) ?></small>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($post['meta_keywords']): ?>
                                <div class="mb-0">
                                    <strong>Meta Keywords:</strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($post['meta_keywords']) ?></small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar -->
            <div class="col-md-4">
                <!-- Actions Card -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Aksi</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <?php if (hasRole(['super_admin', 'admin', 'editor'])): ?>
                                <a href="posts_edit.php?id=<?= $post['id'] ?>" class="btn btn-warning">
                                    <i class="bi bi-pencil"></i> Edit Post
                                </a>
                            <?php endif; ?>
                            
                            <?php if (hasRole(['super_admin', 'admin'])): ?>
                                <a href="posts_delete.php?id=<?= $post['id'] ?>" 
                                   class="btn btn-danger"
                                   onclick="return confirm('Yakin hapus post ini?')">
                                    <i class="bi bi-trash"></i> Hapus Post
                                </a>
                            <?php endif; ?>
                            
                            <a href="posts_list.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Info Card -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Informasi</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td width="100"><strong>No</strong></td>
                                <td><?= $post['id'] ?></td>
                            </tr>
                            <tr>
                                <td><strong>Slug</strong></td>
                                <td><code><?= $post['slug'] ?></code></td>
                            </tr>
                            <tr>
                                <td><strong>Kategori</strong></td>
                                <td><?= htmlspecialchars($post['category_name']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Status</strong></td>
                                <td><?= getStatusBadge($post['status']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Views</strong></td>
                                <td><?= formatNumber($post['view_count']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Penulis</strong></td>
                                <td><?= htmlspecialchars($post['author_name']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Dibuat</strong></td>
                                <td><?= formatTanggal($post['created_at'], 'd M Y H:i') ?></td>
                            </tr>
                            <?php if ($post['updated_at']): ?>
                                <tr>
                                    <td><strong>Diupdate</strong></td>
                                    <td><?= formatTanggal($post['updated_at'], 'd M Y H:i') ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if ($post['published_at']): ?>
                                <tr>
                                    <td><strong>Dipublish</strong></td>
                                    <td><?= formatTanggal($post['published_at'], 'd M Y H:i') ?></td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
.post-content {
    font-size: 1rem;
    line-height: 1.8;
}

.post-content img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin: 1rem 0;
}

.post-content h2, .post-content h3, .post-content h4 {
    margin-top: 1.5rem;
    margin-bottom: 1rem;
    font-weight: 600;
}

.post-content p {
    margin-bottom: 1rem;
}

.post-content ul, .post-content ol {
    margin-bottom: 1rem;
    padding-left: 1.5rem;
}

.post-content blockquote {
    border-left: 4px solid var(--bs-primary);
    padding-left: 1rem;
    margin: 1.5rem 0;
    font-style: italic;
    color: #666;
}

.post-content table {
    width: 100%;
    margin: 1rem 0;
    border-collapse: collapse;
}

.post-content table td, .post-content table th {
    border: 1px solid #ddd;
    padding: 8px;
}

.post-content table th {
    background-color: #f8f9fa;
    font-weight: 600;
}
</style>

<?php include '../../includes/footer.php'; ?>
