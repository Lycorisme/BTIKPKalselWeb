<?php
/**
 * View Service Detail Page
 * Display complete service information
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../core/Model.php';
require_once '../../../models/Service.php';

$pageTitle = 'Detail Layanan';

$serviceModel = new Service();

// Get service ID
$serviceId = $_GET['id'] ?? 0;
$service = $serviceModel->find($serviceId);

if (!$service) {
    setAlert('danger', 'Layanan tidak ditemukan');
    redirect(ADMIN_URL . 'modules/services/services_list.php');
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
                        <li class="breadcrumb-item active">Detail</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="row">
            <!-- Main Content -->
            <div class="col-md-8">
                <!-- Service Info Card -->
                <div class="card">
                    <div class="card-body">
                        <!-- Image -->
                        <?php if ($service['image']): ?>
                            <div class="mb-4">
                                <img src="<?= uploadUrl($service['image']) ?>" 
                                     alt="<?= htmlspecialchars($service['title']) ?>" 
                                     class="img-fluid rounded">
                            </div>
                        <?php endif; ?>
                        
                        <!-- Title & Description -->
                        <div class="d-flex align-items-start mb-4">
                            <?php if ($service['icon']): ?>
                                <div class="avatar avatar-xl bg-primary me-3">
                                    <i class="<?= $service['icon'] ?> fs-3 text-white"></i>
                                </div>
                            <?php endif; ?>
                            <div class="flex-grow-1">
                                <h2 class="mb-2"><?= htmlspecialchars($service['title']) ?></h2>
                                <p class="lead text-muted"><?= htmlspecialchars($service['description']) ?></p>
                                
                                <div class="mt-2">
                                    <?php if ($service['status'] == 'published'): ?>
                                        <span class="badge bg-success">Published</span>
                                    <?php elseif ($service['status'] == 'draft'): ?>
                                        <span class="badge bg-secondary">Draft</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Archived</span>
                                    <?php endif; ?>
                                    
                                    <?php if ($service['featured']): ?>
                                        <span class="badge bg-warning text-dark">
                                            <i class="bi bi-star-fill"></i> Featured
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Content -->
                        <?php if ($service['content']): ?>
                            <hr>
                            <div class="content">
                                <?= $service['content'] ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- SEO Info Card -->
                <?php if ($service['meta_title'] || $service['meta_description'] || $service['meta_keywords']): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">SEO Information</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($service['meta_title']): ?>
                                <div class="mb-3">
                                    <strong>Meta Title:</strong><br>
                                    <?= htmlspecialchars($service['meta_title']) ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($service['meta_description']): ?>
                                <div class="mb-3">
                                    <strong>Meta Description:</strong><br>
                                    <?= htmlspecialchars($service['meta_description']) ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($service['meta_keywords']): ?>
                                <div class="mb-0">
                                    <strong>Meta Keywords:</strong><br>
                                    <?= htmlspecialchars($service['meta_keywords']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar -->
            <div class="col-md-4">
                <!-- Actions Card -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Aksi</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <?php if (hasRole(['super_admin', 'admin', 'editor'])): ?>
                                <a href="services_edit.php?id=<?= $service['id'] ?>" class="btn btn-warning">
                                    <i class="bi bi-pencil"></i> Edit Layanan
                                </a>
                            <?php endif; ?>
                            
                            <?php if (hasRole(['super_admin', 'admin'])): ?>
                                <a href="services_delete.php?id=<?= $service['id'] ?>" 
                                   class="btn btn-danger"
                                   onclick="return confirm('Yakin hapus layanan ini?')">
                                    <i class="bi bi-trash"></i> Hapus Layanan
                                </a>
                            <?php endif; ?>
                            
                            <a href="services_list.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Details Card -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Detail</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td width="100"><strong>ID</strong></td>
                                <td><?= $service['id'] ?></td>
                            </tr>
                            <tr>
                                <td><strong>Slug</strong></td>
                                <td><code><?= $service['slug'] ?></code></td>
                            </tr>
                            <tr>
                                <td><strong>Urutan</strong></td>
                                <td><?= $service['order'] ?></td>
                            </tr>
                            <tr>
                                <td><strong>Penulis</strong></td>
                                <td><?= isset($service['author_name']) ? htmlspecialchars($service['author_name']) : 'N/A' ?></td>
                            </tr>
                            <tr>
                                <td><strong>Dibuat</strong></td>
                                <td><?= formatTanggal($service['created_at'], 'd M Y H:i') ?></td>
                            </tr>
                            <?php if ($service['updated_at']): ?>
                                <tr>
                                    <td><strong>Diupdate</strong></td>
                                    <td><?= formatTanggal($service['updated_at'], 'd M Y H:i') ?></td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>
