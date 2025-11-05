<?php
/**
 * Add New Service Page
 * Create new service with image upload, CKEditor, and icon picker
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../core/Model.php';
require_once '../../../core/Validator.php';
require_once '../../../core/Upload.php';
require_once '../../../models/Service.php';

// Only admin and editor can add services
if (!hasRole(['super_admin', 'admin', 'editor'])) {
    setAlert('danger', 'Anda tidak memiliki akses ke halaman ini');
    redirect(ADMIN_URL);
}

$pageTitle = 'Tambah Layanan Baru';

$serviceModel = new Service();
$validator = null;

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
            $imagePath = null;
            
            // Handle image upload
            if (!empty($_FILES['image']['name'])) {
                $imagePath = $upload->upload($_FILES['image'], 'services');
                if (!$imagePath) {
                    $validator->addError('image', $upload->getError());
                }
            }
            
            if ($validator->passes()) {
                // Generate slug
                $slug = $serviceModel->generateSlug($_POST['title']);
                
                // Prepare data
                $data = [
                    'title' => clean($_POST['title']),
                    'slug' => $slug,
                    'description' => clean($_POST['description']),
                    'content' => $_POST['content'] ?? '', // Allow HTML from CKEditor
                    'icon' => clean($_POST['icon'] ?? ''),
                    'image' => $imagePath,
                    'featured' => isset($_POST['featured']) ? 1 : 0,
                    'order' => (int)($_POST['order'] ?? 0),
                    'status' => clean($_POST['status']),
                    'meta_title' => clean($_POST['meta_title'] ?? ''),
                    'meta_description' => clean($_POST['meta_description'] ?? ''),
                    'meta_keywords' => clean($_POST['meta_keywords'] ?? ''),
                    'author_id' => getCurrentUser()['id']
                ];
                
                $serviceId = $serviceModel->insert($data);
                
                if ($serviceId) {
                    logActivity('CREATE', "Menambah layanan: {$data['title']}", 'services', $serviceId);
                    
                    setAlert('success', 'Layanan berhasil ditambahkan');
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
                        <li class="breadcrumb-item active">Tambah Baru</li>
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
                                       value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" 
                                       placeholder="Contoh: Konsultasi IT" required>
                                <?php if ($validator && $validator->getError('title')): ?>
                                    <div class="invalid-feedback"><?= $validator->getError('title') ?></div>
                                <?php endif; ?>
                                <small class="text-muted">Slug akan dibuat otomatis dari judul</small>
                            </div>
                            
                            <!-- Description -->
                            <div class="form-group mb-3">
                                <label class="form-label">Deskripsi Singkat <span class="text-danger">*</span></label>
                                <textarea name="description" rows="3"
                                          class="form-control <?= $validator && $validator->getError('description') ? 'is-invalid' : '' ?>" 
                                          placeholder="Deskripsi singkat layanan (max 200 karakter)" required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                                <?php if ($validator && $validator->getError('description')): ?>
                                    <div class="invalid-feedback"><?= $validator->getError('description') ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Content -->
                            <div class="form-group mb-3">
                                <label class="form-label">Konten Lengkap</label>
                                <textarea name="content" id="content" rows="10" class="form-control"><?= $_POST['content'] ?? '' ?></textarea>
                                <small class="text-muted">Gunakan editor untuk format teks yang lebih baik</small>
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
                            <!-- Status -->
                            <div class="form-group mb-3">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select" required>
                                    <option value="draft" <?= ($_POST['status'] ?? 'draft') == 'draft' ? 'selected' : '' ?>>Draft</option>
                                    <option value="published" <?= ($_POST['status'] ?? '') == 'published' ? 'selected' : '' ?>>Published</option>
                                    <option value="archived" <?= ($_POST['status'] ?? '') == 'archived' ? 'selected' : '' ?>>Archived</option>
                                </select>
                            </div>
                            
                            <!-- Featured -->
                            <div class="form-group mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="featured" id="featured" 
                                           class="form-check-input" value="1"
                                           <?= ($_POST['featured'] ?? '') == '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="featured">
                                        Featured / Unggulan
                                    </label>
                                </div>
                                <small class="text-muted">Tampilkan di homepage</small>
                            </div>
                            
                            <!-- Order -->
                            <div class="form-group mb-0">
                                <label class="form-label">Urutan Tampil</label>
                                <input type="number" name="order" class="form-control" 
                                       value="<?= htmlspecialchars($_POST['order'] ?? '0') ?>" min="0">
                                <small class="text-muted">0 = tampil paling atas</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Image Card -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Gambar & Icon</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group mb-3">
                                <label class="form-label">Upload Gambar</label>
                                <input type="file" name="image" 
                                       class="form-control <?= $validator && $validator->getError('image') ? 'is-invalid' : '' ?>" 
                                       accept="image/*">
                                <small class="text-muted">Max <?= getSetting('upload_max_size', 5) ?>MB</small>
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
                                        <option value="bi-briefcase">💼 Briefcase</option>
                                        <option value="bi-building">🏢 Building</option>
                                        <option value="bi-people">👥 People</option>
                                        <option value="bi-person-badge">👔 Person Badge</option>
                                    </optgroup>
                                    <optgroup label="Technology">
                                        <option value="bi-laptop">💻 Laptop</option>
                                        <option value="bi-phone">📱 Phone</option>
                                        <option value="bi-code-slash">💻 Code</option>
                                        <option value="bi-cpu">🖥️ CPU</option>
                                        <option value="bi-display">🖥️ Display</option>
                                        <option value="bi-gear" selected>⚙️ Gear</option>
                                    </optgroup>
                                    <optgroup label="Education">
                                        <option value="bi-book">📚 Book</option>
                                        <option value="bi-mortarboard">🎓 Graduation</option>
                                        <option value="bi-pencil">✏️ Pencil</option>
                                    </optgroup>
                                    <optgroup label="Communication">
                                        <option value="bi-chat-dots">💬 Chat</option>
                                        <option value="bi-telephone">📞 Telephone</option>
                                        <option value="bi-envelope">✉️ Envelope</option>
                                    </optgroup>
                                    <optgroup label="Tools & Settings">
                                        <option value="bi-tools">🔧 Tools</option>
                                        <option value="bi-wrench">🔧 Wrench</option>
                                        <option value="bi-hammer">🔨 Hammer</option>
                                    </optgroup>
                                    <optgroup label="Media">
                                        <option value="bi-camera">📷 Camera</option>
                                        <option value="bi-film">🎬 Film</option>
                                        <option value="bi-image">🖼️ Image</option>
                                    </optgroup>
                                    <optgroup label="Security">
                                        <option value="bi-shield-check">🛡️ Shield</option>
                                        <option value="bi-lock">🔒 Lock</option>
                                    </optgroup>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Actions Card -->
                    <div class="card">
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Simpan Layanan
                                </button>
                                <a href="services_list.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Batal
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
