<?php
/**
 * Services List Page
 * Manage all services with filters, search, and quick actions
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../core/Model.php';
require_once '../../../core/Pagination.php';
require_once '../../../models/Service.php';

$pageTitle = 'Kelola Layanan';

$serviceModel = new Service();

// Get items per page
$itemsPerPage = (int)getSetting('items_per_page', 10);

// Get filters
$status = $_GET['status'] ?? '';
$featured = $_GET['featured'] ?? '';
$search = $_GET['search'] ?? '';
$showDeleted = $_GET['show_deleted'] ?? '0';
$page = $_GET['page'] ?? 1;

// Build filters
$filters = [];
if ($status) $filters['status'] = $status;
if ($featured !== '') $filters['featured'] = $featured;
if ($search) $filters['search'] = $search;
if ($showDeleted == '1') $filters['show_deleted'] = true;

// Get services with pagination
$result = $serviceModel->getPaginated($page, $itemsPerPage, $filters);
$services = $result['data'];

// Initialize pagination
$pagination = new Pagination(
    $result['total'],
    $result['per_page'],
    $result['current_page']
);

// Get status counts
$statusCounts = $serviceModel->countByStatus();

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6">
                <h3><?= $pageTitle ?></h3>
                <p class="text-subtitle text-muted">Kelola semua layanan yang ditawarkan</p>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active">Layanan</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <!-- Status Statistics -->
    <section class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body px-4 py-4-5">
                    <div class="row">
                        <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start">
                            <div class="stats-icon purple mb-2">
                                <i class="bi bi-file-earmark-text"></i>
                            </div>
                        </div>
                        <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                            <h6 class="text-muted font-semibold">Draft</h6>
                            <h6 class="font-extrabold mb-0"><?= $statusCounts['draft'] ?></h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body px-4 py-4-5">
                    <div class="row">
                        <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start">
                            <div class="stats-icon green mb-2">
                                <i class="bi bi-check-circle"></i>
                            </div>
                        </div>
                        <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                            <h6 class="text-muted font-semibold">Published</h6>
                            <h6 class="font-extrabold mb-0"><?= $statusCounts['published'] ?></h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body px-4 py-4-5">
                    <div class="row">
                        <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start">
                            <div class="stats-icon red mb-2">
                                <i class="bi bi-archive"></i>
                            </div>
                        </div>
                        <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                            <h6 class="text-muted font-semibold">Archived</h6>
                            <h6 class="font-extrabold mb-0"><?= $statusCounts['archived'] ?></h6>
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
                    <h5 class="card-title mb-0">Daftar Layanan</h5>
                    <a href="services_add.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Tambah Layanan
                    </a>
                </div>
            </div>
            
            <div class="card-body">
                <!-- Filters -->
                <form method="GET" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <select name="status" class="form-select">
                                <option value="">Semua Status</option>
                                <option value="draft" <?= $status == 'draft' ? 'selected' : '' ?>>Draft</option>
                                <option value="published" <?= $status == 'published' ? 'selected' : '' ?>>Published</option>
                                <option value="archived" <?= $status == 'archived' ? 'selected' : '' ?>>Archived</option>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <select name="featured" class="form-select">
                                <option value="">Semua</option>
                                <option value="1" <?= $featured === '1' ? 'selected' : '' ?>>Featured</option>
                                <option value="0" <?= $featured === '0' ? 'selected' : '' ?>>Not Featured</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Cari judul atau deskripsi..." 
                                   value="<?= htmlspecialchars($search) ?>">
                        </div>
                        
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
                        
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search"></i> Filter
                            </button>
                        </div>
                    </div>
                    
                    <?php if ($status || $featured !== '' || $search || $showDeleted == '1'): ?>
                        <div class="mt-2">
                            <a href="services_list.php" class="btn btn-sm btn-secondary">
                                <i class="bi bi-x-circle"></i> Reset Filter
                            </a>
                        </div>
                    <?php endif; ?>
                </form>

                <!-- Info -->
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    Menampilkan <strong><?= count($services) ?></strong> dari <strong><?= formatNumber($result['total']) ?></strong> layanan
                    <?php if ($showDeleted == '1'): ?>
                        <span class="badge bg-warning text-dark ms-2">Termasuk yang terhapus</span>
                    <?php endif; ?>
                </div>

                <!-- Services Table -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="50">No</th>
                                <th>Layanan</th>
                                <th width="100">Featured</th>
                                <th width="120">Status</th>
                                <th width="150">Penulis</th>
                                <th width="200" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($services)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        Tidak ada data
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php 
                                $no = ($result['current_page'] - 1) * $result['per_page'] + 1; // biar tetap urut di tiap halaman
                                foreach ($services as $service): 
                                ?>
                                    <tr <?= $service['deleted_at'] ? 'class="table-secondary"' : '' ?>>
                                        <td>
                                            <span class="badge bg-secondary"><?= $no++ ?></span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if ($service['image']): ?>
                                                    <img src="<?= uploadUrl($service['image']) ?>" 
                                                         alt="<?= htmlspecialchars($service['title']) ?>" 
                                                         class="rounded me-3" 
                                                         style="width: 60px; height: 60px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="avatar avatar-lg bg-light me-3">
                                                        <i class="<?= $service['icon'] ?: 'bi-gear' ?> fs-4"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?= htmlspecialchars($service['title']) ?></strong>
                                                    <?php if ($service['deleted_at']): ?>
                                                        <span class="badge bg-danger ms-1">Deleted</span>
                                                    <?php endif; ?>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?= truncateText($service['description'], 60) ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($service['featured']): ?>
                                                <span class="badge bg-warning text-dark">
                                                    <i class="bi bi-star-fill"></i> Featured
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($service['deleted_at']): ?>
                                                <span class="badge bg-danger">Deleted</span>
                                            <?php else: ?>
                                                <?php if ($service['status'] == 'published'): ?>
                                                    <span class="badge bg-success">Published</span>
                                                <?php elseif ($service['status'] == 'draft'): ?>
                                                    <span class="badge bg-secondary">Draft</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">Archived</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small><?= htmlspecialchars($service['author_name'] ?: 'N/A') ?></small>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($service['deleted_at']): ?>
                                                <small class="text-muted">
                                                    Deleted: <?= formatTanggal($service['deleted_at'], 'd M Y') ?>
                                                </small>
                                            <?php else: ?>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="services_view.php?id=<?= $service['id'] ?>" 
                                                       class="btn btn-info" title="Detail">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <?php if (hasRole(['super_admin', 'admin', 'editor'])): ?>
                                                        <a href="services_edit.php?id=<?= $service['id'] ?>" 
                                                           class="btn btn-warning" title="Edit">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if (hasRole(['super_admin', 'admin'])): ?>
                                                        <a href="services_delete.php?id=<?= $service['id'] ?>" 
                                                           class="btn btn-danger" 
                                                           onclick="return confirm('Yakin hapus layanan ini?')"
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
