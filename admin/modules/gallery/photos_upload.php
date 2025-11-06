<?php
/**
 * Gallery Photos - Upload Multiple
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

$pageTitle = 'Upload Foto';
$currentPage = 'photos_upload';

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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploadedCount = 0;
    $errorCount = 0;
    $currentUser = getCurrentUser();
    
    if (isset($_FILES['photos']) && !empty($_FILES['photos']['name'][0])) {
        $files = $_FILES['photos'];
        $totalFiles = count($files['name']);
        
        $uploadPath = '../../../public/uploads/gallery/photos/';
        $thumbnailPath = '../../../public/uploads/gallery/thumbnails/';
        
        // Create directories if not exist
        if (!is_dir($uploadPath)) mkdir($uploadPath, 0755, true);
        if (!is_dir($thumbnailPath)) mkdir($thumbnailPath, 0755, true);
        
        for ($i = 0; $i < $totalFiles; $i++) {
            // Skip if error
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                $errorCount++;
                continue;
            }
            
            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!in_array($files['type'][$i], $allowedTypes)) {
                $errorCount++;
                continue;
            }
            
            // Validate file size (5MB max)
            if ($files['size'][$i] > 5 * 1024 * 1024) {
                $errorCount++;
                continue;
            }
            
            // Generate unique filename
            $extension = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
            $filename = 'photo_' . time() . '_' . uniqid() . '_' . $i . '.' . $extension;
            $thumbnailFilename = 'thumb_' . $filename;
            
            // Move uploaded file
            if (move_uploaded_file($files['tmp_name'][$i], $uploadPath . $filename)) {
                // Get image dimensions
                list($width, $height) = getimagesize($uploadPath . $filename);
                
                // Create thumbnail
                createThumbnail(
                    $uploadPath . $filename, 
                    $thumbnailPath . $thumbnailFilename, 
                    400, 
                    400
                );
                
                // Insert to database
                try {
                    $stmt = $db->prepare("
                        INSERT INTO gallery_photos 
                        (album_id, filename, thumbnail, file_size, width, height, uploaded_by) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $stmt->execute([
                        $albumId,
                        'gallery/photos/' . $filename,
                        'gallery/thumbnails/' . $thumbnailFilename,
                        $files['size'][$i],
                        $width,
                        $height,
                        $currentUser['id']
                    ]);
                    
                    $uploadedCount++;
                    
                } catch (PDOException $e) {
                    error_log($e->getMessage());
                    $errorCount++;
                }
            } else {
                $errorCount++;
            }
        }
    }
    
    // Log activity
    if ($uploadedCount > 0) {
        logActivity('CREATE', "Upload {$uploadedCount} foto ke album: {$album['name']}", 'gallery_photos', null);
    }
    
    // Set alert message
    if ($uploadedCount > 0 && $errorCount === 0) {
        setAlert('success', "{$uploadedCount} foto berhasil diupload!");
    } elseif ($uploadedCount > 0 && $errorCount > 0) {
        setAlert('warning', "{$uploadedCount} foto berhasil diupload, {$errorCount} gagal.");
    } else {
        setAlert('danger', 'Tidak ada foto yang berhasil diupload.');
    }
    
    redirect(ADMIN_URL . 'modules/gallery/photos_list.php?album_id=' . $albumId);
}

// Function to create thumbnail
function createThumbnail($source, $destination, $maxWidth, $maxHeight) {
    list($width, $height, $type) = getimagesize($source);
    
    // Calculate new dimensions
    $ratio = min($maxWidth / $width, $maxHeight / $height);
    $newWidth = (int)($width * $ratio);
    $newHeight = (int)($height * $ratio);
    
    // Create image resource
    switch ($type) {
        case IMAGETYPE_JPEG:
            $image = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $image = imagecreatefrompng($source);
            break;
        case IMAGETYPE_GIF:
            $image = imagecreatefromgif($source);
            break;
        default:
            return false;
    }
    
    // Create new image
    $thumbnail = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preserve transparency for PNG and GIF
    if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF) {
        imagealphablending($thumbnail, false);
        imagesavealpha($thumbnail, true);
    }
    
    // Resize
    imagecopyresampled($thumbnail, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    // Save thumbnail
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($thumbnail, $destination, 85);
            break;
        case IMAGETYPE_PNG:
            imagepng($thumbnail, $destination, 8);
            break;
        case IMAGETYPE_GIF:
            imagegif($thumbnail, $destination);
            break;
    }
    
    // Clean up
    imagedestroy($image);
    imagedestroy($thumbnail);
    
    return true;
}

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
                        <li class="breadcrumb-item"><a href="photos_list.php?album_id=<?= $album['id'] ?>">Foto</a></li>
                        <li class="breadcrumb-item active">Upload</li>
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
                        <h5 class="card-title mb-0">Upload Foto</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" id="uploadForm">
                            <?= csrfField() ?>
                            
                            <!-- File Input -->
                            <div class="form-group mb-3">
                                <label for="photos" class="form-label">Pilih Foto <span class="text-danger">*</span></label>
                                <input type="file" 
                                       class="form-control" 
                                       id="photos" 
                                       name="photos[]" 
                                       multiple 
                                       accept="image/*"
                                       required
                                       onchange="previewPhotos(this)">
                                <small class="text-muted">
                                    Format: JPG, PNG, GIF. Maks: 5MB per file. Bisa pilih multiple files.
                                </small>
                            </div>
                            
                            <!-- Preview Area -->
                            <div id="preview-container" class="row g-2 mb-3" style="display: none;"></div>
                            
                            <hr>
                            
                            <div class="d-flex justify-content-between">
                                <a href="photos_list.php?album_id=<?= $album['id'] ?>" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> Kembali
                                </a>
                                <button type="submit" class="btn btn-primary" id="uploadBtn">
                                    <i class="bi bi-upload"></i> Upload Foto
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
                        <h5 class="card-title mb-0">Tips Upload</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <i class="bi bi-check-circle text-success"></i>
                                Pilih multiple file sekaligus dengan Ctrl+Click
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-check-circle text-success"></i>
                                Ukuran file maksimal 5MB per foto
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-check-circle text-success"></i>
                                Format yang didukung: JPG, PNG, GIF
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-check-circle text-success"></i>
                                Thumbnail akan dibuat otomatis
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-check-circle text-success"></i>
                                Setelah upload, Anda bisa edit detail tiap foto
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Info Album</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Nama:</strong><br><?= htmlspecialchars($album['name']) ?></p>
                        <p><strong>Foto saat ini:</strong><br><?= $album['photo_count'] ?> foto</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
function previewPhotos(input) {
    const container = document.getElementById('preview-container');
    container.innerHTML = '';
    container.style.display = 'none';
    
    if (input.files && input.files.length > 0) {
        container.style.display = 'flex';
        
        Array.from(input.files).forEach((file, index) => {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const col = document.createElement('div');
                    col.className = 'col-md-3 col-sm-4 col-6';
                    col.innerHTML = `
                        <div class="card">
                            <img src="${e.target.result}" class="card-img-top" style="height: 150px; object-fit: cover;">
                            <div class="card-body p-2">
                                <small class="text-muted">${file.name}</small><br>
                                <small class="text-muted">${(file.size / 1024 / 1024).toFixed(2)} MB</small>
                            </div>
                        </div>
                    `;
                    container.appendChild(col);
                }
                
                reader.readAsDataURL(file);
            }
        });
        
        // Update button text
        document.getElementById('uploadBtn').innerHTML = `
            <i class="bi bi-upload"></i> Upload ${input.files.length} Foto
        `;
    }
}
</script>

<?php include '../../includes/footer.php'; ?>
