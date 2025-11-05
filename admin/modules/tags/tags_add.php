<?php
/**
 * Add Tag Page
 * Create new post tag
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../core/Model.php';
require_once '../../../core/Validator.php';
require_once '../../../models/Tag.php';

// Only admin and editor can add
if (!hasRole(['super_admin', 'admin', 'editor'])) {
    setAlert('danger', 'Anda tidak memiliki akses ke halaman ini');
    redirect(ADMIN_URL);
}

$pageTitle = 'Tambah Tag';

$tagModel = new Tag();
$validator = null;

// Process form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $validator = new Validator($_POST);
    
    // Validation rules
    $validator->required('name', 'Nama Tag');
    
    if ($validator->passes()) {
        try {
            // Generate slug
            $slug = $tagModel->generateSlug($_POST['name']);
            
            // Prepare data
            $data = [
                'name' => clean($_POST['name']),
                'slug' => $slug
            ];
            
            $tagId = $tagModel->insert($data);
            
            if ($tagId) {
                logActivity('CREATE', "Menambah tag: {$data['name']}", 'tags', $tagId);
                
                setAlert('success', 'Tag berhasil ditambahkan');
                redirect(ADMIN_URL . 'modules/tags/tags_list.php');
            } else {
                $validator->addError('general', 'Gagal menyimpan data');
            }
            
        } catch (PDOException $e) {
            error_log("PDO Error: " . $e->getMessage());
            $validator->addError('general', 'Terjadi kesalahan database: ' . $e->getMessage());
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
                        <li class="breadcrumb-item"><a href="tags_list.php">Tag</a></li>
                        <li class="breadcrumb-item active">Tambah</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Form Tag</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($validator && $validator->getError('general')): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <strong>Error:</strong> <?= $validator->getError('general') ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <!-- Name -->
                            <div class="form-group mb-3">
                                <label class="form-label">Nama Tag <span class="text-danger">*</span></label>
                                <input type="text" name="name" 
                                       class="form-control <?= $validator && $validator->getError('name') ? 'is-invalid' : '' ?>" 
                                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" 
                                       placeholder="Contoh: teknologi, workshop, seminar" required>
                                <?php if ($validator && $validator->getError('name')): ?>
                                    <div class="invalid-feedback"><?= $validator->getError('name') ?></div>
                                <?php endif; ?>
                                <small class="text-muted">Slug akan dibuat otomatis. Gunakan lowercase dan hindari spasi.</small>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Simpan Tag
                                </button>
                                <a href="tags_list.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Batal
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Info</h5>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted">
                            <i class="bi bi-info-circle"></i> Tag digunakan untuk menandai post dengan kata kunci spesifik.
                        </p>
                        <hr>
                        <p class="small text-muted mb-0">
                            <strong>Tips:</strong><br>
                            • Gunakan tag yang relevan dan spesifik<br>
                            • Hindari tag yang terlalu umum<br>
                            • Satu post bisa memiliki multiple tags<br>
                            • Gunakan lowercase untuk konsistensi
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>
