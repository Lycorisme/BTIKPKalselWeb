<?php
/**
 * Edit Tag Page
 * Update existing post tag
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../core/Model.php';
require_once '../../../core/Validator.php';
require_once '../../../models/Tag.php';

// Only admin and editor can edit
if (!hasRole(['super_admin', 'admin', 'editor'])) {
    setAlert('danger', 'Anda tidak memiliki akses ke halaman ini');
    redirect(ADMIN_URL);
}

$pageTitle = 'Edit Tag';

$tagModel = new Tag();
$validator = null;

// Get tag ID
$tagId = $_GET['id'] ?? 0;
$tag = $tagModel->find($tagId);

if (!$tag) {
    setAlert('danger', 'Tag tidak ditemukan');
    redirect(ADMIN_URL . 'modules/tags/tags_list.php');
}

// Process form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $validator = new Validator($_POST);
    
    // Validation rules
    $validator->required('name', 'Nama Tag');
    
    if ($validator->passes()) {
        try {
            // Check if name changed, regenerate slug
            $slug = $tag['slug'];
            if ($_POST['name'] != $tag['name']) {
                $slug = $tagModel->generateSlug($_POST['name'], $tagId);
            }
            
            // Prepare data
            $data = [
                'name' => clean($_POST['name']),
                'slug' => $slug
            ];
            
            if ($tagModel->update($tagId, $data)) {
                logActivity('UPDATE', "Mengupdate tag: {$data['name']}", 'tags', $tagId);
                
                setAlert('success', 'Tag berhasil diupdate');
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
                        <li class="breadcrumb-item active">Edit</li>
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
                                       value="<?= htmlspecialchars($_POST['name'] ?? $tag['name']) ?>" required>
                                <?php if ($validator && $validator->getError('name')): ?>
                                    <div class="invalid-feedback"><?= $validator->getError('name') ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Slug (Read-only) -->
                            <div class="form-group mb-3">
                                <label class="form-label">Slug</label>
                                <input type="text" class="form-control" value="<?= $tag['slug'] ?>" readonly>
                                <small class="text-muted">Slug akan diupdate otomatis jika nama diubah</small>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Update Tag
                                </button>
                                <a href="tags_list.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> Kembali
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
                        <small>
                            <strong>No:</strong> <?= $tag['id'] ?><br>
                            <strong>Dibuat:</strong> <?= formatTanggal($tag['created_at'], 'd M Y H:i') ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>
