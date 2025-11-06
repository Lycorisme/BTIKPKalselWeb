<?php
/**
 * Gallery Photos - List
 * Display all photos in an album with drag & drop reordering
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

$pageTitle = 'Kelola Foto';
$currentPage = 'photos_list';

$db = Database::getInstance()->getConnection();

// Get album ID
$albumId = $_GET['album_id'] ?? null;

if (!$albumId) {
    setAlert('danger', 'Album tidak ditemukan.');
    redirect(ADMIN_URL . 'modules/gallery/albums_list.php');
}

// Get album data
$stmt = $db->prepare("SELECT * FROM gallery_albums WHERE id = ? AND deleted_at IS NULL");
$stmt->execute([$albumId]);
$album = $stmt->fetch();

if (!$album) {
    setAlert('danger', 'Album tidak ditemukan.');
    redirect(ADMIN_URL . 'modules/gallery/albums_list.php');
}

// Get all photos in this album
$stmt = $db->prepare("
    SELECT 
        p.*,
        u.name as uploader_name
    FROM gallery_photos p
    LEFT JOIN users u ON p.uploaded_by = u.id
    WHERE p.album_id = ? AND p.deleted_at IS NULL
    ORDER BY p.display_order ASC, p.created_at DESC
");
$stmt->execute([$albumId]);
$photos = $stmt->fetchAll();

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6">
                <h3><?= $pageTitle ?></h3>
                <p class="text-subtitle text-muted">Album: <strong><?= htmlspecialchars($album['name']) ?></strong></p>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="albums_list.php">Albums</a></li>
                        <li class="breadcrumb-item active">Foto</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<?php
// ----- PERBAIKAN DI SINI -----
// Menampilkan notifikasi/alert dari session (yang di-set oleh halaman sebelumnya, 
// misalnya setelah upload foto atau jika ada error redirect).
// Ini akan memunculkan alert statis seperti yang ada di screenshot Anda.
if (function_exists('displayAlert')) {
    displayAlert();
}
// ----- BATAS PERBAIKAN -----
?>

<section class="section">
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <a href="albums_list.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Kembali ke Albums
                    </a>
                    <a href="albums_edit.php?id=<?= $album['id'] ?>" class="btn btn-outline-primary">
                        <i class="bi bi-pencil"></i> Edit Album
                    </a>
                </div>
                <a href="photos_upload.php?album_id=<?= $album['id'] ?>" class="btn btn-primary">
                    <i class="bi bi-upload"></i> Upload Foto
                </a>
            </div>
        </div>
    </div>
    
    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <h6 class="text-muted mb-1">Total Foto</h6>
                    <h4><?= count($photos) ?></h4>
                </div>
                <div class="col-md-3">
                    <h6 class="text-muted mb-1">Cover Photo</h6>
                    <h4><?= $album['cover_photo'] ? '<i class="bi bi-check-circle text-success"></i>' : '<i class="bi bi-x-circle text-danger"></i>' ?></h4>
                </div>
                <div class="col-md-3">
                    <h6 class="text-muted mb-1">Status</h6>
                    <h4><?= $album['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></h4>
                </div>
                <div class="col-md-3">
                    <h6 class="text-muted mb-1">Dibuat</h6>
                    <h4><?= formatTanggal($album['created_at'], 'd M Y') ?></h4>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Daftar Foto</h5>
                <?php if (!empty($photos)): ?>
                    <small class="text-muted">
                        <i class="bi bi-arrows-move"></i> Drag foto untuk mengubah urutan
                    </small>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($photos)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Belum ada foto di album ini. 
                    <a href="photos_upload.php?album_id=<?= $album['id'] ?>">Upload foto sekarang</a>
                </div>
            <?php else: ?>
                <div class="row" id="photos-grid">
                    <?php foreach ($photos as $photo): ?>
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-4" data-photo-id="<?= $photo['id'] ?>">
                            <div class="card h-100 photo-card">
                                <div class="drag-handle">
                                    <i class="bi bi-grip-vertical"></i>
                                </div>
                                
                                <div style="height: 200px; overflow: hidden; background: #f5f5f5; cursor: pointer;"
                                     onclick="viewPhoto('<?= uploadUrl($photo['filename']) ?>', '<?= htmlspecialchars($photo['title'] ?? 'Photo', ENT_QUOTES) ?>')">
                                    <img src="<?= uploadUrl($photo['thumbnail'] ?? $photo['filename']) ?>" 
                                         alt="<?= htmlspecialchars($photo['title'] ?? '') ?>" 
                                         class="w-100 h-100" 
                                         style="object-fit: cover;">
                                </div>
                                
                                <div class="card-body p-2">
                                    <?php if ($photo['title']): ?>
                                        <h6 class="card-title mb-1 small"><?= htmlspecialchars(truncateText($photo['title'], 30)) ?></h6>
                                    <?php endif; ?>
                                    
                                    <?php if ($photo['caption']): ?>
                                        <p class="card-text text-muted small mb-2">
                                            <?= htmlspecialchars(truncateText($photo['caption'], 50)) ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <small class="text-muted d-block">
                                        <i class="bi bi-eye"></i> <?= formatNumber($photo['view_count']) ?> views
                                    </small>
                                </div>
                                
                                <div class="card-footer bg-transparent p-2">
                                    <div class="btn-group w-100 btn-group-sm" role="group">
                                        <a href="photos_edit.php?id=<?= $photo['id'] ?>" 
                                           class="btn btn-outline-primary"
                                           title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-outline-success"
                                                onclick="setCover(<?= $photo['id'] ?>)"
                                                title="Set sebagai cover">
                                            <i class="bi bi-star"></i>
                                        </button>
                                        <button type="button"
                                                class="btn btn-outline-danger"
                                                onclick="quickDeletePhoto(<?= $photo['id'] ?>, this.closest('[data-photo-id]'))"
                                                title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
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

<div class="modal fade" id="photoModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="photoModalLabel">Photo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="photoModalImage" src="" alt="" class="img-fluid">
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
// Initialize Sortable for drag & drop reordering
const photosGrid = document.getElementById('photos-grid');
if (photosGrid && photosGrid.children.length > 0) {
    new Sortable(photosGrid, {
        animation: 150,
        handle: '.drag-handle', // Drag by handle only
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        dragClass: 'sortable-drag',
        onEnd: function(evt) {
            // Get new order
            const photoIds = [];
            document.querySelectorAll('[data-photo-id]').forEach(el => {
                photoIds.push(el.dataset.photoId);
            });
            
            // Send to server
            reorderPhotos(photoIds);
        }
    });
}

// Reorder photos via AJAX
function reorderPhotos(photoIds) {
    fetch('ajax/reorder.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            photo_ids: photoIds,
            album_id: <?= $album['id'] ?>
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success notification
            showNotification('Urutan foto berhasil diupdate!', 'success');
        } else {
            showNotification('Gagal reorder: ' + data.message, 'danger');
            location.reload(); // Reload to restore original order
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Terjadi kesalahan saat reorder', 'danger');
        location.reload();
    });
}

// Quick delete photo
function quickDeletePhoto(photoId, cardElement) {
    if (!confirm('Yakin ingin menghapus foto ini?')) return;
    
    // Disable button to prevent double click
    const deleteBtn = cardElement.querySelector('.btn-outline-danger');
    deleteBtn.disabled = true;
    
    fetch('ajax/quick_delete.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            photo_id: photoId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Animate and remove card
            cardElement.style.transition = 'opacity 0.3s, transform 0.3s';
            cardElement.style.opacity = '0';
            cardElement.style.transform = 'scale(0.8)';
            
            setTimeout(() => {
                cardElement.remove();
                
                // Show success notification
                showNotification('Foto berhasil dihapus!', 'success');
                
                // Check if no more photos
                const remainingPhotos = document.querySelectorAll('[data-photo-id]');
                if (remainingPhotos.length === 0) {
                    location.reload(); // Reload to show empty state
                }
            }, 300);
        } else {
            deleteBtn.disabled = false;
            showNotification('Gagal hapus foto: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        deleteBtn.disabled = false;
        showNotification('Terjadi kesalahan saat hapus foto', 'danger');
    });
}

// Set cover photo
function setCover(photoId) {
    if (!confirm('Set foto ini sebagai cover album?')) return;
    
    fetch('ajax/set_cover.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            photo_id: photoId,
            album_id: <?= $album['id'] ?>
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Cover album berhasil diupdate!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification('Gagal update cover: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Terjadi kesalahan saat update cover', 'danger');
    });
}

// View photo in modal
function viewPhoto(url, title) {
    document.getElementById('photoModalImage').src = url;
    document.getElementById('photoModalLabel').textContent = title;
    new bootstrap.Modal(document.getElementById('photoModal')).show();
}

// Show notification helper
function showNotification(message, type = 'success') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i> 
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alertDiv);
    
    // Auto dismiss after 3 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 3000);
}
</script>

<style>
/* Sortable classes */
.sortable-ghost {
    opacity: 0.4;
    background: #f0f0f0;
}

.sortable-chosen {
    opacity: 0.8;
}

.sortable-drag {
    opacity: 1;
}

/* Photo card styling */
.photo-card {
    transition: transform 0.2s, box-shadow 0.2s;
    position: relative;
}

.photo-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

/* Drag handle */
.drag-handle {
    position: absolute;
    top: 5px;
    left: 5px;
    background: rgba(0,0,0,0.6);
    color: white;
    padding: 5px 8px;
    border-radius: 4px;
    cursor: move;
    z-index: 10;
    font-size: 14px;
    transition: background 0.2s;
}

.drag-handle:hover {
    background: rgba(0,0,0,0.8);
}

.drag-handle:active {
    cursor: grabbing;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .drag-handle {
        font-size: 12px;
        padding: 4px 6px;
    }
}
</style>

<?php include '../../includes/footer.php'; ?>