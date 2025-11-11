<?php
/**
 * Gallery Albums - List (Full Mazer Design)
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../models/Gallery.php';

$pageTitle = 'Gallery Albums';
$currentPage = 'albums_list';

$db = Database::getInstance()->getConnection();
$galleryModel = new Gallery();

// Get all albums with photo count
$stmt = $db->query("
    SELECT 
        a.*,
        u.name as creator_name,
        COUNT(p.id) as photo_count
    FROM gallery_albums a
    LEFT JOIN users u ON a.created_by = u.id
    LEFT JOIN gallery_photos p ON a.id = p.album_id AND p.deleted_at IS NULL
    WHERE a.deleted_at IS NULL
    GROUP BY a.id
    ORDER BY a.created_at DESC
");
$albums = $stmt->fetchAll();

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6">
                <h3><?= $pageTitle ?></h3>
                <p class="text-subtitle text-muted">Kelola album galeri foto</p>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item">Gallery</li>
                        <li class="breadcrumb-item active">Albums</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card shadow">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Daftar Album</h5>
                <a href="albums_add.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Tambah Album
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($albums)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Belum ada album. <a href="albums_add.php">Tambah album pertama</a>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($albums as $album): ?>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="card h-100 shadow-sm hover-shadow">
                                    <!-- Album Cover -->
                                    <div style="height: 200px; overflow: hidden; background: #f5f5f5; cursor: pointer;">
                                        <?php if ($album['cover_photo']): ?>
                                            <img src="<?= uploadUrl($album['cover_photo']) ?>" 
                                                 alt="<?= htmlspecialchars($album['name']) ?>" 
                                                 class="w-100 h-100" 
                                                 style="object-fit: cover;">
                                        <?php else: ?>
                                            <div class="d-flex align-items-center justify-content-center h-100 bg-light">
                                                <i class="bi bi-images" style="font-size: 3rem; color: #ccc;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="card-body">
                                        <h5 class="card-title mb-2">
                                            <?= htmlspecialchars($album['name']) ?>
                                            <?php if (!$album['is_active']): ?>
                                                <span class="badge bg-secondary ms-1">Inactive</span>
                                            <?php else: ?>
                                                <span class="badge bg-success ms-1">Active</span>
                                            <?php endif; ?>
                                        </h5>
                                        
                                        <?php if ($album['description']): ?>
                                            <p class="card-text text-muted small">
                                                <?= htmlspecialchars(truncateText($album['description'], 80)) ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <i class="bi bi-images"></i> <?= $album['photo_count'] ?> foto
                                            </small>
                                            <small class="text-muted">
                                                <?= formatTanggal($album['created_at'], 'd M Y') ?>
                                            </small>
                                        </div>
                                    </div>
                                    
                                    <div class="card-footer bg-transparent">
                                        <div class="btn-group w-100 btn-group-sm" role="group">
                                            <a href="photos_list.php?album_id=<?= $album['id'] ?>" 
                                               class="btn btn-outline-primary">
                                                <i class="bi bi-images"></i> <span class="d-none d-md-inline">Foto</span>
                                            </a>
                                            <a href="albums_edit.php?id=<?= $album['id'] ?>" 
                                               class="btn btn-outline-secondary">
                                                <i class="bi bi-pencil"></i> <span class="d-none d-md-inline">Edit</span>
                                            </a>
                                            <a href="albums_delete.php?id=<?= $album['id'] ?>" 
                                               class="btn btn-outline-danger"
                                               data-confirm-delete
                                               data-title="<?= htmlspecialchars($album['name']) ?>"
                                               data-message="Album &quot;<?= htmlspecialchars($album['name']) ?>&quot; dan semua foto di dalamnya akan dipindahkan ke Trash. Lanjutkan?"
                                               data-loading-text="Menghapus album...">
                                                <i class="bi bi-trash"></i> <span class="d-none d-md-inline">Hapus</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<style>
.hover-shadow {
    transition: box-shadow 0.3s ease, transform 0.3s ease;
}

.hover-shadow:hover {
    box-shadow: 0 8px 16px rgba(0,0,0,0.15) !important;
    transform: translateY(-4px);
}
</style>

<?php include '../../includes/footer.php'; ?>
