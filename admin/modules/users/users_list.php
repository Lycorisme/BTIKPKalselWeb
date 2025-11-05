<?php
/**
 * Users List Page
 * Multi-user management with roles & filters
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../core/Model.php';
require_once '../../../core/Pagination.php';
require_once '../../../models/User.php';

// Only super_admin and admin can access
if (!hasRole(['super_admin', 'admin'])) {
    setAlert('danger', 'Anda tidak memiliki akses ke halaman ini');
    redirect(ADMIN_URL);
}

$pageTitle = 'Kelola Pengguna';

$userModel = new User();

// Get items per page from settings
$itemsPerPage = (int)getSetting('items_per_page', 10);

// Get filters - ✅ DEFINE $showDeleted HERE
$role = $_GET['role'] ?? '';
$isActive = $_GET['is_active'] ?? '';
$search = $_GET['search'] ?? '';
$showDeleted = $_GET['show_deleted'] ?? '0'; // ✅ FIX: Define here
$page = $_GET['page'] ?? 1;

// Build filters
$filters = [];
if ($role) $filters['role'] = $role;
if ($isActive !== '') $filters['is_active'] = $isActive;
if ($search) $filters['search'] = $search;
if ($showDeleted == '1') $filters['show_deleted'] = true;

// Get users with pagination
$result = $userModel->getPaginated($page, $itemsPerPage, $filters);
$users = $result['data'];

// Initialize pagination
$pagination = new Pagination(
    $result['total'],
    $result['per_page'],
    $result['current_page']
);

// Get role counts (only active users)
$roleCounts = $userModel->countByRole();

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6">
                <h3><?= $pageTitle ?></h3>
                <p class="text-subtitle text-muted">Kelola pengguna sistem dan hak akses</p>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active">Pengguna</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <!-- Role Statistics -->
    <section class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body px-4 py-4-5">
                    <div class="row">
                        <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start">
                            <div class="stats-icon purple mb-2">
                                <i class="bi bi-shield-check"></i>
                            </div>
                        </div>
                        <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                            <h6 class="text-muted font-semibold">Super Admin</h6>
                            <h6 class="font-extrabold mb-0"><?= $roleCounts['super_admin'] ?></h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body px-4 py-4-5">
                    <div class="row">
                        <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start">
                            <div class="stats-icon blue mb-2">
                                <i class="bi bi-person-badge"></i>
                            </div>
                        </div>
                        <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                            <h6 class="text-muted font-semibold">Admin</h6>
                            <h6 class="font-extrabold mb-0"><?= $roleCounts['admin'] ?></h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body px-4 py-4-5">
                    <div class="row">
                        <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start">
                            <div class="stats-icon green mb-2">
                                <i class="bi bi-pencil-square"></i>
                            </div>
                        </div>
                        <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                            <h6 class="text-muted font-semibold">Editor</h6>
                            <h6 class="font-extrabold mb-0"><?= $roleCounts['editor'] ?></h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body px-4 py-4-5">
                    <div class="row">
                        <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start">
                            <div class="stats-icon red mb-2">
                                <i class="bi bi-person"></i>
                            </div>
                        </div>
                        <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                            <h6 class="text-muted font-semibold">Author</h6>
                            <h6 class="font-extrabold mb-0"><?= $roleCounts['author'] ?></h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Daftar Pengguna</h5>
                    <a href="users_add.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Tambah Pengguna
                    </a>
                </div>
            </div>
            
            <div class="card-body">
                <!-- Alert Messages -->
                <?php if ($alert = getAlert()): ?>
                    <div class="alert alert-<?= $alert['type'] ?> alert-dismissible fade show">
                        <?= $alert['message'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Filters -->
                <form method="GET" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <select name="role" class="form-select">
                                <option value="">Semua Role</option>
                                <option value="super_admin" <?= $role == 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                                <option value="admin" <?= $role == 'admin' ? 'selected' : '' ?>>Admin</option>
                                <option value="editor" <?= $role == 'editor' ? 'selected' : '' ?>>Editor</option>
                                <option value="author" <?= $role == 'author' ? 'selected' : '' ?>>Author</option>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <select name="is_active" class="form-select">
                                <option value="">Semua Status</option>
                                <option value="1" <?= $isActive === '1' ? 'selected' : '' ?>>Aktif</option>
                                <option value="0" <?= $isActive === '0' ? 'selected' : '' ?>>Tidak Aktif</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Cari nama atau email..." 
                                   value="<?= htmlspecialchars($search) ?>">
                        </div>
                        
                        <!-- ✅ SHOW DELETED CHECKBOX -->
                        <div class="col-md-2">
                            <div class="form-check mt-2">
                                <input type="checkbox" name="show_deleted" value="1" 
                                       class="form-check-input" id="show_deleted"
                                       <?= $showDeleted == '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="show_deleted">
                                    Tampilkan Terhapus
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search"></i> Filter
                            </button>
                        </div>
                    </div>
                    
                    <?php if ($role || $isActive !== '' || $search || $showDeleted == '1'): ?>
                        <div class="mt-2">
                            <a href="users_list.php" class="btn btn-sm btn-secondary">
                                <i class="bi bi-x-circle"></i> Reset Filter
                            </a>
                        </div>
                    <?php endif; ?>
                </form>

                <!-- Info -->
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    Menampilkan <strong><?= count($users) ?></strong> dari <strong><?= formatNumber($result['total']) ?></strong> pengguna
                    <?php if ($showDeleted == '1'): ?>
                        <span class="badge bg-warning text-dark ms-2">Termasuk yang terhapus</span>
                    <?php endif; ?>
                </div>

                <!-- Users Table -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="50">ID</th>
                                <th>Nama</th>
                                <th width="150">Role</th>
                                <th width="100">Status</th>
                                <th width="150">Last Login</th>
                                <th width="180" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        Tidak ada data
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <tr <?= $user['deleted_at'] ? 'class="table-secondary"' : '' ?>>
                                        <td><?= $user['id'] ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-md me-3">
                                                    <?php if ($user['photo']): ?>
                                                        <img src="<?= uploadUrl($user['photo']) ?>" alt="">
                                                    <?php else: ?>
                                                        <img src="<?= ADMIN_URL ?>assets/static/images/faces/1.jpg" alt="">
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <strong><?= htmlspecialchars($user['name']) ?></strong>
                                                    <?php if ($user['deleted_at']): ?>
                                                        <span class="badge bg-danger ms-1">Deleted</span>
                                                    <?php endif; ?>
                                                    <br><small class="text-muted"><?= htmlspecialchars($user['email']) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= getRoleBadge($user['role']) ?></td>
                                        <td>
                                            <?php if ($user['is_active']): ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Nonaktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small>
                                                <?= $user['last_login_at'] ? formatTanggal($user['last_login_at'], 'd M Y H:i') : 'Belum login' ?>
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($user['deleted_at']): ?>
                                                <span class="text-muted">
                                                    <small>Deleted: <?= formatTanggal($user['deleted_at'], 'd M Y') ?></small>
                                                </span>
                                            <?php else: ?>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="users_view.php?id=<?= $user['id'] ?>" 
                                                       class="btn btn-info" title="Detail">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="users_edit.php?id=<?= $user['id'] ?>" 
                                                       class="btn btn-warning" title="Edit">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <?php if ($user['id'] != getCurrentUser()['id']): ?>
                                                        <a href="users_delete.php?id=<?= $user['id'] ?>" 
                                                           class="btn btn-danger" 
                                                           onclick="return confirm('Yakin hapus pengguna ini?')"
                                                           title="Hapus">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($result['total'] > 0): ?>
                    <div class="mt-4 d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">
                                Halaman <?= $result['current_page'] ?> dari <?= $result['last_page'] ?>
                            </small>
                        </div>
                        <?= $pagination->render() ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>
