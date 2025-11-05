<?php
/**
 * Edit Service Page
 * Update existing service with image management, delete image, icon picker, CKEditor
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../core/Model.php';
require_once '../../../core/Validator.php';
require_once '../../../core/Upload.php';
require_once '../../../models/Service.php';

// Only admin and editor can edit services
if (!hasRole(['super_admin', 'admin', 'editor'])) {
    setAlert('danger', 'Anda tidak memiliki akses ke halaman ini');
    redirect(ADMIN_URL);
}

$pageTitle = 'Edit Layanan';

$serviceModel = new Service();
$validator = null;

// Get service ID
$serviceId = $_GET['id'] ?? 0;
$service = $serviceModel->find($serviceId);

if (!$service) {
    setAlert('danger', 'Layanan tidak ditemukan');
    redirect(ADMIN_URL . 'modules/services/services_list.php');
}

// Process form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $validator = new Validator($_POST);
    
    // Validation rules
    $validator->required('title', 'Judul');
    $validator->required('description', 'Deskripsi');
    $validator->required('status', 'Status');
    
    if ($validator->passes()) {
        try {
            $upload = new Upload();
            $imagePath = $service['image']; // Keep existing image
            
            // Handle image deletion
            if (isset($_POST['delete_image']) && $_POST['delete_image'] == '1') {
                // Delete current image
                if ($service['image']) {
                    $upload->delete($service['image']);
                }
                $imagePath = null;
            }
            // Handle image upload
            elseif (!empty($_FILES['image']['name'])) {
                $newImage = $upload->upload($_FILES['image'], 'services');
                if ($newImage) {
                    // Delete old image
                    if ($service['image']) {
                        $upload->delete($service['image']);
                    }
                    $imagePath = $newImage;
                } else {
                    $validator->addError('image', $upload->getError());
                }
            }
            
            if ($validator->passes()) {
                // Check if title changed, regenerate slug
                $slug = $service['slug'];
                if ($_POST['title'] != $service['title']) {
                    $slug = $serviceModel->generateSlug($_POST['title'], $serviceId);
                }
                
                // Prepare data
                $data = [
                    'title' => clean($_POST['title']),
                    'slug' => $slug,
                    'description' => clean($_POST['description']),
                    'content' => $_POST['content'] ?? '',
                    'icon' => clean($_POST['icon'] ?? ''),
                    'image' => $imagePath,
                    'featured' => isset($_POST['featured']) ? 1 : 0,
                    'order' => (int)($_POST['order'] ?? 0),
                    'status' => clean($_POST['status']),
                    'meta_title' => clean($_POST['meta_title'] ?? ''),
                    'meta_description' => clean($_POST['meta_description'] ?? ''),
                    'meta_keywords' => clean($_POST['meta_keywords'] ?? '')
                ];
                
                if ($serviceModel->update($serviceId, $data)) {
                    logActivity('UPDATE', "Mengupdate layanan: {$data['title']}", 'services', $serviceId);
                    
                    setAlert('success', 'Layanan berhasil diupdate');
                    redirect(ADMIN_URL . 'modules/services/services_list.php');
                } else {
                    $validator->addError('general', 'Gagal menyimpan data');
                }
            }
            
        } catch (PDOException $e) {
            error_log("PDO Error: " . $e->getMessage());
            $validator->addError('general', 'Terjadi kesalahan database: ' . $e->getMessage());
        } catch (Exception $e) {
            error_log("General Error: " . $e->getMessage());
            $validator->addError('general', 'Terjadi kesalahan: ' . $e->getMessage());
        }
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
                        <li class="breadcrumb-item"><a href="services_list.php">Layanan</a></li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <!-- Left Column - Main Content -->
                <div class="col-md-8">
                    <!-- Basic Info Card -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Informasi Layanan</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($validator && $validator->getError('general')): ?>
                                <div class="alert alert-danger alert-dismissible fade show">
                                    <strong>Error:</strong> <?= $validator->getError('general') ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Title -->
                            <div class="form-group mb-3">
                                <label class="form-label">Judul Layanan <span class="text-danger">*</span></label>
                                <input type="text" name="title" 
                                       class="form-control <?= $validator && $validator->getError('title') ? 'is-invalid' : '' ?>" 
                                       value="<?= htmlspecialchars($_POST['title'] ?? $service['title']) ?>" required>
                                <?php if ($validator && $validator->getError('title')): ?>
                                    <div class="invalid-feedback"><?= $validator->getError('title') ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Slug (Read-only) -->
                            <div class="form-group mb-3">
                                <label class="form-label">Slug</label>
                                <input type="text" class="form-control" value="<?= $service['slug'] ?>" readonly>
                                <small class="text-muted">Slug akan diupdate otomatis jika judul diubah</small>
                            </div>
                            
                            <!-- Description -->
                            <div class="form-group mb-3">
                                <label class="form-label">Deskripsi Singkat <span class="text-danger">*</span></label>
                                <textarea name="description" rows="3"
                                          class="form-control <?= $validator && $validator->getError('description') ? 'is-invalid' : '' ?>" 
                                          required><?= htmlspecialchars($_POST['description'] ?? $service['description']) ?></textarea>
                                <?php if ($validator && $validator->getError('description')): ?>
                                    <div class="invalid-feedback"><?= $validator->getError('description') ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Content -->
                            <div class="form-group mb-3">
                                <label class="form-label">Konten Lengkap</label>
                                <textarea name="content" id="content" rows="10" class="form-control"><?= $_POST['content'] ?? $service['content'] ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- SEO Card -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">SEO Settings</h5>
                        </div>
                        <div class="card-body">
                            <!-- Meta Title -->
                            <div class="form-group mb-3">
                                <label class="form-label">Meta Title</label>
                                <input type="text" name="meta_title" class="form-control" 
                                       value="<?= htmlspecialchars($_POST['meta_title'] ?? '') ?>"
                                       placeholder="Kosongkan untuk menggunakan judul layanan">
                            </div>
                            
                            <!-- Meta Description -->
                            <div class="form-group mb-3">
                                <label class="form-label">Meta Description</label>
                                <textarea name="meta_description" rows="2" class="form-control"
                                          placeholder="Deskripsi untuk search engine (max 160 karakter)"><?= htmlspecialchars($_POST['meta_description'] ?? '') ?></textarea>
                            </div>
                            
                            <!-- Meta Keywords -->
                            <div class="form-group mb-0">
                                <label class="form-label">Meta Keywords</label>
                                <input type="text" name="meta_keywords" class="form-control" 
                                       value="<?= htmlspecialchars($_POST['meta_keywords'] ?? '') ?>"
                                       placeholder="keyword1, keyword2, keyword3">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column - Settings -->
                <div class="col-md-4">
                    <!-- Publish Card -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Publikasi</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group mb-3">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select" required>
                                    <option value="draft" <?= ($_POST['status'] ?? $service['status']) == 'draft' ? 'selected' : '' ?>>Draft</option>
                                    <option value="published" <?= ($_POST['status'] ?? $service['status']) == 'published' ? 'selected' : '' ?>>Published</option>
                                    <option value="archived" <?= ($_POST['status'] ?? $service['status']) == 'archived' ? 'selected' : '' ?>>Archived</option>
                                </select>
                            </div>
                            
                            <div class="form-group mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="featured" id="featured" 
                                           class="form-check-input" value="1"
                                           <?= ($_POST['featured'] ?? $service['featured']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="featured">
                                        Featured / Unggulan
                                    </label>
                                </div>
                            </div>
                            
                            <div class="form-group mb-0">
                                <label class="form-label">Urutan Tampil</label>
                                <input type="number" name="order" class="form-control" 
                                       value="<?= htmlspecialchars($_POST['order'] ?? $service['order']) ?>" min="0">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Image Card -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Gambar & Icon</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($service['image']): ?>
                                <div class="mb-3">
                                    <label class="form-label">Gambar Saat Ini</label>
                                    <div class="position-relative">
                                        <img src="<?= uploadUrl($service['image']) ?>" 
                                             alt="<?= htmlspecialchars($service['title']) ?>" 
                                             class="img-fluid rounded" id="currentImage">
                                        
                                        <!-- Delete Image Checkbox -->
                                        <div class="mt-2">
                                            <div class="form-check">
                                                <input type="checkbox" name="delete_image" value="1" 
                                                       class="form-check-input" id="deleteImage">
                                                <label class="form-check-label text-danger" for="deleteImage">
                                                    <i class="bi bi-trash"></i> Hapus gambar ini
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="form-group mb-3">
                                <label class="form-label">
                                    <?= $service['image'] ? 'Upload Gambar Baru' : 'Upload Gambar' ?>
                                </label>
                                <input type="file" name="image" 
                                       class="form-control <?= $validator && $validator->getError('image') ? 'is-invalid' : '' ?>" 
                                       accept="image/*">
                                <?php if ($service['image']): ?>
                                    <small class="text-muted">Kosongkan jika tidak ingin mengubah gambar</small>
                                <?php endif; ?>
                                <?php if ($validator && $validator->getError('image')): ?>
                                    <div class="invalid-feedback"><?= $validator->getError('image') ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Icon Picker -->
                            <div class="form-group mb-0">
                                <label class="form-label">Icon</label>
                                <select name="icon" id="iconSelect" class="form-select">
                                    <option value="">-- Pilih Icon --</option>
                                    <optgroup label="Business & Office">
                                        <option value="bi-briefcase" <?= ($_POST['icon'] ?? $service['icon'] ?? '') == 'bi-briefcase' ? 'selected' : '' ?>>💼 Briefcase</option>
                                        <option value="bi-building" <?= ($_POST['icon'] ?? $service['icon'] ?? '') == 'bi-building' ? 'selected' : '' ?>>🏢 Building</option>
                                        <option value="bi-people" <?= ($_POST['icon'] ?? $service['icon'] ?? '') == 'bi-people' ? 'selected' : '' ?>>👥 People</option>
                                        <option value="bi-person-badge" <?= ($_POST['icon'] ?? $service['icon'] ?? '') == 'bi-person-badge' ? 'selected' : '' ?>>👔 Person Badge</option>
                                    </optgroup>
                                    <optgroup label="Technology">
                                        <option value="bi-laptop" <?= ($_POST['icon'] ?? $service['icon'] ?? '') == 'bi-laptop' ? 'selected' : '' ?>>💻 Laptop</option>
                                        <option value="bi-phone" <?= ($_POST['icon'] ?? $service['icon'] ?? '') == 'bi-phone' ? 'selected' : '' ?>>📱 Phone</option>
                                        <option value="bi-code-slash" <?= ($_POST['icon'] ?? $service['icon'] ?? '') == 'bi-code-slash' ? 'selected' : '' ?>>💻 Code</option>
                                        <option value="bi-cpu" <?= ($_POST['icon'] ?? $service['icon'] ?? '') == 'bi-cpu' ? 'selected' : '' ?>>🖥️ CPU</option>
                                        <option value="bi-display" <?= ($_POST['icon'] ?? $service['icon'] ?? '') == 'bi-display' ? 'selected' : '' ?>>🖥️ Display</option>
                                        <option value="bi-gear" <?= ($_POST['icon'] ?? $service['icon'] ?? '') == 'bi-gear' ? 'selected' : '' ?>>⚙️ Gear</option>
                                    </optgroup>
                                    <optgroup label="Education">
                                        <option value="bi-book" <?= ($_POST['icon'] ?? $service['icon'] ?? '') == 'bi-book' ? 'selected' : '' ?>>📚 Book</option>
                                        <option value="bi-mortarboard" <?= ($_POST['icon'] ?? $service['icon'] ?? '') == 'bi-mortarboard' ? 'selected' : '' ?>>🎓 Graduation</option>
                                        <option value="bi-pencil" <?= ($_POST['icon'] ?? $service['icon'] ?? '') == 'bi-pencil' ? 'selected' : '' ?>>✏️ Pencil</option>
                                    </optgroup>
                                    <optgroup label="Communication">
                                        <option value="bi-chat-dots" <?= ($_POST['icon'] ?? $service['icon'] ?? '') == 'bi-chat-dots' ? 'selected' : '' ?>>💬 Chat</option>
                                        <option value="bi-telephone" <?= ($_POST['icon'] ?? $service['icon'] ?? '') == 'bi-telephone' ? 'selected' : '' ?>>📞 Telephone</option>
                                        <option value="bi-envelope" <?= ($_POST['icon'] ?? $service['icon'] ?? '') == 'bi-envelope' ? 'selected' : '' ?>>✉️ Envelope</option>
                                    </optgroup>
                                    <optgroup label="Tools & Settings">
                                        <option value="bi-tools" <?= ($_POST['icon'] ?? $service['icon'] ?? '') == 'bi-tools' ? 'selected' : '' ?>>🔧 Tools</option>
                                        <option value="bi-wrench" <?= ($_POST['icon'] ?? $service['icon'] ?? '') == 'bi-wrench' ? 'selected' : '' ?>>🔧 Wrench</option>
                                        <option value="bi-hammer" <?= ($_POST['icon'] ?? $service['icon'] ?? '') == 'bi-hammer' ? 'selected' : '' ?>>🔨 Hammer</option>
                                    </optgroup>
                                    <optgroup label="Media">
                                        <option value="bi-camera" <?= ($_POST['icon'] ?? $service['icon'] ?? '') == 'bi-camera' ? 'selected' : '' ?>>📷 Camera</option>
                                        <option value="bi-film" <?= ($_POST['icon'] ?? $service['icon'] ?? '') == 'bi-film' ? 'selected' : '' ?>>🎬 Film</option>
                                        <option value="bi-image" <?= ($_POST['icon'] ?? $service['icon'] ?? '') == 'bi-image' ? 'selected' : '' ?>>🖼️ Image</option>
                                    </optgroup>
                                    <optgroup label="Security">
                                        <option value="bi-shield-check" <?= ($_POST['icon'] ?? $service['icon'] ?? '') == 'bi-shield-check' ? 'selected' : '' ?>>🛡️ Shield</option>
                                        <option value="bi-lock" <?= ($_POST['icon'] ?? $service['icon'] ?? '') == 'bi-lock' ? 'selected' : '' ?>>🔒 Lock</option>
                                    </optgroup>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Info Card -->
                    <div class="card">
                        <div class="card-body">
                            <small>
                                <strong>No:</strong> <?= $service['id'] ?><br>
                                <strong>Dibuat:</strong> <?= formatTanggal($service['created_at'], 'd M Y H:i') ?><br>
                                <?php if ($service['updated_at']): ?>
                                    <strong>Diupdate:</strong> <?= formatTanggal($service['updated_at'], 'd M Y H:i') ?>
                                <?php endif; ?>
                            </small>
                        </div>
                    </div>
                    
                    <!-- Actions Card -->
                    <div class="card">
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Update Layanan
                                </button>
                                <a href="services_list.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> Kembali
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
</div>

<!-- CKEditor 5 -->
<script src="https://cdn.ckeditor.com/ckeditor5/40.1.0/classic/ckeditor.js"></script>
<script>
    // Initialize CKEditor
    ClassicEditor
        .create(document.querySelector('#content'), {
            toolbar: {
                items: [
                    'heading', '|',
                    'bold', 'italic', 'underline', 'strikethrough', '|',
                    'link', 'bulletedList', 'numberedList', '|',
                    'alignment', 'indent', 'outdent', '|',
                    'blockQuote', 'insertTable', '|',
                    'undo', 'redo'
                ],
                shouldNotGroupWhenFull: true
            },
            table: {
                contentToolbar: [
                    'tableColumn', 'tableRow', 'mergeTableCells'
                ]
            },
            heading: {
                options: [
                    { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                    { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                    { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' }
                ]
            },
            language: 'en'
        })
        .catch(error => {
            console.error('CKEditor initialization error:', error);
        });
    
    // Icon Preview
    document.getElementById('iconSelect').addEventListener('change', function() {
        const iconClass = this.value;
        const preview = document.getElementById('iconPreview');
        
        if (iconClass) {
            preview.innerHTML = `
                <div class="alert alert-info">
                    <i class="${iconClass} fs-3"></i>
                    <span class="ms-2">Preview Icon</span>
                </div>
            `;
        } else {
            preview.innerHTML = '';
        }
    });
</script>

<?php include '../../includes/footer.php'; ?>
