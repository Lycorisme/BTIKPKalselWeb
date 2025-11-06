<?php
/**
 * Gallery Albums - Add New
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

$pageTitle = 'Tambah Album Baru';
$currentPage = 'albums_add';

$db = Database::getInstance()->getConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean($_POST['name']);
    $description = clean($_POST['description'] ?? '');
    $displayOrder = (int)($_POST['display_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    // Generate slug
    $slug = generateSlug($name);
    
    // Check if slug already exists
    $checkStmt = $db->prepare("SELECT id FROM gallery_albums WHERE slug = ? AND deleted_at IS NULL");
    $checkStmt->execute([$slug]);
    
    if ($checkStmt->fetch()) {
        $slug = $slug . '-' . time();
    }
    
    // Handle cover photo upload
    $coverPhoto = null;
    if (isset($_FILES['cover_photo']) && $_FILES['cover_photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['cover_photo'];
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        
        if (in_array($file['type'], $allowedTypes)) {
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'cover_' . time() . '_' . uniqid() . '.' . $extension;
            $uploadPath = '../../../public/uploads/gallery/albums/';
            
            // Create directory if not exists
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            
            if (move_uploaded_file($file['tmp_name'], $uploadPath . $filename)) {
                $coverPhoto = 'gallery/albums/' . $filename;
            }
        }
    }
    
    try {
        $stmt = $db->prepare("
            INSERT INTO gallery_albums 
            (name, slug, description, cover_photo, display_order, is_active, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $currentUser = getCurrentUser();
        $stmt->execute([
            $name, 
            $slug, 
            $description, 
            $coverPhoto, 
            $displayOrder, 
            $isActive,
            $currentUser['id']
        ]);
        
        $albumId = $db->lastInsertId();
        
        // Log activity
        logActivity('CREATE', "Membuat album gallery: {$name}", 'gallery_albums', $albumId);
        
        setAlert('success', 'Album berhasil ditambahkan!');
        redirect(ADMIN_URL . 'modules/gallery/albums_list.php');
        
    } catch (PDOException $e) {
        error_log($e->getMessage());
        setAlert('danger', 'Gagal menambahkan album. Silakan coba lagi.');
    }
}

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
                        <li class="breadcrumb-item"><a href="albums_list.php">Gallery Albums</a></li>
                        <li class="breadcrumb-item active">Tambah Baru</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Informasi Album</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <?= csrfField() ?>
                            
                            <!-- Nama Album -->
                            <div class="form-group mb-3">
                                <label for="name" class="form-label">Nama Album <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control" 
                                       id="name" 
                                       name="name" 
                                       required
                                       placeholder="Contoh: Pelatihan Guru 2024">
                                <small class="text-muted">Nama album yang akan ditampilkan</small>
                            </div>
                            
                            <!-- Deskripsi -->
                            <div class="form-group mb-3">
                                <label for="description" class="form-label">Deskripsi</label>
                                <textarea class="form-control" 
                                          id="description" 
                                          name="description" 
                                          rows="4"
                                          placeholder="Deskripsi singkat tentang album ini..."></textarea>
                            </div>
                            
                            <!-- Cover Photo -->
                            <div class="form-group mb-3">
                                <label for="cover_photo" class="form-label">Cover Photo</label>
                                <input type="file" 
                                       class="form-control" 
                                       id="cover_photo" 
                                       name="cover_photo" 
                                       accept="image/*"
                                       onchange="previewImage(this, 'cover-preview')">
                                <small class="text-muted">Format: JPG, PNG, GIF. Maks: 5MB</small>
                                
                                <!-- Preview -->
                                <div id="cover-preview" class="mt-3" style="display: none;">
                                    <img src="" alt="Preview" class="img-thumbnail" style="max-height: 200px;">
                                </div>
                            </div>
                            
                            <!-- Display Order -->
                            <div class="form-group mb-3">
                                <label for="display_order" class="form-label">Urutan Tampilan</label>
                                <input type="number" 
                                       class="form-control" 
                                       id="display_order" 
                                       name="display_order" 
                                       value="0"
                                       min="0">
                                <small class="text-muted">Semakin kecil angka, semakin awal ditampilkan</small>
                            </div>
                            
                            <!-- Status -->
                            <div class="form-group mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           id="is_active" 
                                           name="is_active" 
                                           checked>
                                    <label class="form-check-label" for="is_active">
                                        Aktif (ditampilkan di website)
                                    </label>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="d-flex justify-content-between">
                                <a href="albums_list.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> Kembali
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Simpan Album
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Info Card -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Tips</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <i class="bi bi-check-circle text-success"></i>
                                <strong>Nama Album</strong> harus jelas dan deskriptif
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-check-circle text-success"></i>
                                <strong>Cover Photo</strong> akan menjadi thumbnail album
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-check-circle text-success"></i>
                                Setelah album dibuat, Anda bisa upload foto-foto
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-check-circle text-success"></i>
                                Album yang tidak aktif tidak akan ditampilkan di website
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    const img = preview.querySelector('img');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            img.src = e.target.result;
            preview.style.display = 'block';
        }
        
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.style.display = 'none';
    }
}
</script>

<?php include '../../includes/footer.php'; ?>
