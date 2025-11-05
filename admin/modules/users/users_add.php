<?php
/**
 * Add New User Page - WITH DEBUG
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../core/Model.php';
require_once '../../../core/Validator.php';
require_once '../../../core/Upload.php';
require_once '../../../models/User.php';

// Only super_admin and admin can access
if (!hasRole(['super_admin', 'admin'])) {
    setAlert('danger', 'Anda tidak memiliki akses ke halaman ini');
    redirect(ADMIN_URL);
}

$pageTitle = 'Tambah Pengguna Baru';

$userModel = new User();
$validator = null;
$debugInfo = []; // For debugging

// Process form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Log POST data
    error_log("POST Data: " . print_r($_POST, true));
    
    $validator = new Validator($_POST);
    
    // Validation rules
    $validator->required('name', 'Nama');
    $validator->required('email', 'Email');
    $validator->email('email', 'Email');
    $validator->required('password', 'Password');
    $validator->minLength('password', 6, 'Password');
    $validator->required('role', 'Role');
    
    // Check if email already exists
    if ($userModel->emailExists($_POST['email'])) {
        $validator->addError('email', 'Email sudah digunakan');
    }
    
    if ($validator->passes()) {
        try {
            $upload = new Upload();
            $photoPath = null;
            
            // Handle photo upload
            if (!empty($_FILES['photo']['name'])) {
                $photoPath = $upload->upload($_FILES['photo'], 'users');
                if (!$photoPath) {
                    $validator->addError('photo', $upload->getError());
                }
            }
            
            if ($validator->passes()) {
                // Prepare data
                $data = [
                    'name' => clean($_POST['name']),
                    'email' => clean($_POST['email']),
                    'password' => $_POST['password'], // Will be hashed in model
                    'phone' => clean($_POST['phone'] ?? ''),
                    'address' => clean($_POST['address'] ?? ''),
                    'role' => clean($_POST['role']),
                    'is_active' => isset($_POST['is_active']) ? 1 : 0,
                    'photo' => $photoPath
                ];
                
                // Only add created_by if column exists
                try {
                    $data['created_by'] = getCurrentUser()['id'];
                } catch (Exception $e) {
                    // Column doesn't exist, skip it
                    error_log("created_by column not found, skipping...");
                }
                
                // Debug: Log data to insert
                error_log("Inserting user data: " . print_r($data, true));
                
                $userId = $userModel->insert($data);
                
                if ($userId) {
                    // Log activity
                    try {
                        logActivity('CREATE', "Menambah pengguna baru: {$data['name']}", 'users', $userId);
                    } catch (Exception $e) {
                        error_log("Activity log failed: " . $e->getMessage());
                    }
                    
                    setAlert('success', 'Pengguna berhasil ditambahkan');
                    redirect(ADMIN_URL . 'modules/users/users_list.php');
                } else {
                    $validator->addError('general', 'Gagal menyimpan data. User ID: ' . var_export($userId, true));
                }
            }
            
        } catch (PDOException $e) {
            error_log("PDO Error: " . $e->getMessage());
            $validator->addError('general', 'Terjadi kesalahan database: ' . $e->getMessage());
        } catch (Exception $e) {
            error_log("General Error: " . $e->getMessage());
            $validator->addError('general', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    } else {
        // Debug: Show validation errors
        error_log("Validation failed: " . print_r($validator->getErrors(), true));
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
                        <li class="breadcrumb-item"><a href="users_list.php">Pengguna</a></li>
                        <li class="breadcrumb-item active">Tambah Baru</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Form Tambah Pengguna</h5>
            </div>
            
            <div class="card-body">
                <?php if ($validator && $validator->getError('general')): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <strong>Error:</strong> <?= $validator->getError('general') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($validator && count($validator->getErrors()) > 1): ?>
                    <div class="alert alert-warning">
                        <strong>Validation Errors:</strong>
                        <ul class="mb-0">
                            <?php foreach ($validator->getErrors() as $field => $error): ?>
                                <?php if ($field !== 'general'): ?>
                                    <li><strong><?= ucfirst($field) ?>:</strong> <?= $error ?></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-md-8">
                            <!-- Name -->
                            <div class="form-group mb-3">
                                <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" name="name" 
                                       class="form-control <?= $validator && $validator->getError('name') ? 'is-invalid' : '' ?>" 
                                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                                <?php if ($validator && $validator->getError('name')): ?>
                                    <div class="invalid-feedback"><?= $validator->getError('name') ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Email -->
                            <div class="form-group mb-3">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" 
                                       class="form-control <?= $validator && $validator->getError('email') ? 'is-invalid' : '' ?>" 
                                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                                <?php if ($validator && $validator->getError('email')): ?>
                                    <div class="invalid-feedback"><?= $validator->getError('email') ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Password -->
                            <div class="form-group mb-3">
                                <label class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="password" name="password" 
                                       class="form-control <?= $validator && $validator->getError('password') ? 'is-invalid' : '' ?>" 
                                       required>
                                <small class="text-muted">Minimal 6 karakter</small>
                                <?php if ($validator && $validator->getError('password')): ?>
                                    <div class="invalid-feedback"><?= $validator->getError('password') ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Phone -->
                            <div class="form-group mb-3">
                                <label class="form-label">Telepon</label>
                                <input type="text" name="phone" class="form-control" 
                                       value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                            </div>
                            
                            <!-- Address -->
                            <div class="form-group mb-3">
                                <label class="form-label">Alamat</label>
                                <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                            </div>
                        </div>
                        
                        <!-- Right Column -->
                        <div class="col-md-4">
                            <!-- Photo -->
                            <div class="form-group mb-3">
                                <label class="form-label">Foto Profil</label>
                                <input type="file" name="photo" 
                                       class="form-control <?= $validator && $validator->getError('photo') ? 'is-invalid' : '' ?>" 
                                       accept="image/*">
                                <small class="text-muted">Max <?= getSetting('upload_max_size', 5) ?>MB</small>
                                <?php if ($validator && $validator->getError('photo')): ?>
                                    <div class="invalid-feedback"><?= $validator->getError('photo') ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Role -->
                            <div class="form-group mb-3">
                                <label class="form-label">Role <span class="text-danger">*</span></label>
                                <select name="role" 
                                        class="form-select <?= $validator && $validator->getError('role') ? 'is-invalid' : '' ?>" 
                                        required>
                                    <option value="">Pilih Role</option>
                                    <?php if (hasRole('super_admin')): ?>
                                        <option value="super_admin" <?= ($_POST['role'] ?? '') == 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                                    <?php endif; ?>
                                    <option value="admin" <?= ($_POST['role'] ?? '') == 'admin' ? 'selected' : '' ?>>Admin</option>
                                    <option value="editor" <?= ($_POST['role'] ?? '') == 'editor' ? 'selected' : '' ?>>Editor</option>
                                    <option value="author" <?= ($_POST['role'] ?? '') == 'author' ? 'selected' : '' ?>>Author</option>
                                </select>
                                <?php if ($validator && $validator->getError('role')): ?>
                                    <div class="invalid-feedback"><?= $validator->getError('role') ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Status -->
                            <div class="form-group mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="is_active" id="is_active" 
                                           class="form-check-input" value="1" 
                                           <?= ($_POST['is_active'] ?? '1') == '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="is_active">
                                        Status Aktif
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Role Info -->
                            <div class="alert alert-info">
                                <h6>Penjelasan Role:</h6>
                                <ul class="small mb-0">
                                    <li><strong>Super Admin:</strong> Akses penuh</li>
                                    <li><strong>Admin:</strong> Kelola semua konten</li>
                                    <li><strong>Editor:</strong> Edit semua post</li>
                                    <li><strong>Author:</strong> Buat post sendiri</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between">
                        <a href="users_list.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Simpan Pengguna
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>
