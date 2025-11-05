<?php
/**
 * Categories List Page
 * Manage post categories with post count
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../core/Model.php';
require_once '../../../core/Pagination.php';
require_once '../../../models/PostCategory.php';

$pageTitle = 'Kelola Kategori';

$categoryModel = new PostCategory();

// Get items per page
$itemsPerPage = (int)getSetting('items_per_page', 10);

// Get filters
$isActive = $_GET['is_active'] ?? '';
$search = $_GET['search'] ?? '';
$page = $_GET['page'] ?? 1;

// Build filters
$filters = [];
if ($isActive !== '') $filters['is_active'] = $isActive;
if ($search) $filters['search'] = $search;

// Get categories with pagination
$result = $categoryModel->getPaginated($page, $itemsPerPage, $filters);
$categories = $result['data'];

// Initialize pagination
$pagination = new Pagination(
    $result['total'],
    $result['per_page'],
    $result['current_page']
);

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6">
                <h3><?= $pageTitle ?></h3>
                <p class="text-subtitle text-muted">Kelola kategori berita & artikel</p>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active">Kategori</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Daftar Kategori</h5>
                    <a href="categories_add.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Tambah Kategori
                    </a>
                </div>
            </div>
            
            <div class="card-body">
                <!-- Filters -->
                <form method="GET" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <select name="is_active" class="form-select">
                                <option value="">Semua Status</option>
                                <option value="1" <?= $isActive === '1' ? 'selected' : '' ?>>Aktif</option>
                                <option value="0" <?= $isActive === '0' ? 'selected' : '' ?>>Nonaktif</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Cari nama kategori..." 
                                   value="<?= htmlspecialchars($search) ?>">
                        </div>
                        
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search"></i> Filter
                            </button>
                        </div>
                        
                        <?php if ($isActive !== '' || $search): ?>
                            <div class="col-md-2">
                                <a href="categories_list.php" class="btn btn-secondary w-100">
                                    <i class="bi bi-x-circle"></i> Reset
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </form>

                <!-- Info -->
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    Menampilkan <strong><?= count($categories) ?></strong> dari <strong><?= formatNumber($result['total']) ?></strong> kategori
                </div>

                <!-- Categories Table -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="50">No</th>
                                <th>Kategori</th>
                                <th width="100" class="text-center">Jumlah Post</th>
                                <th width="100" class="text-center">Status</th>
                                <th width="150" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categories)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        Tidak ada data
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td><?= $category['id'] ?></td>
                                        <td>
                                            <div>
                                                <strong><?= htmlspecialchars($category['name']) ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    <code><?= $category['slug'] ?></code>
                                                </small>
                                                <?php if ($category['description']): ?>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?= truncateText($category['description'], 60) ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-info">
                                                <?= $category['post_count'] ?> post
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($category['is_active']): ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Nonaktif</span>
                                            <?php endif; ?>
                                        </td>
<td class="text-center">
    <div class="btn-group btn-group-sm">
        <!-- Tombol Lihat (lihat kategori di halaman publik) -->
        <a href="<?= BASE_URL ?>news/category.php?slug=<?= $category['slug'] ?>" 
           class="btn btn-info" title="Lihat" target="_blank">
            <i class="bi bi-eye"></i>
        </a>

        <?php if (hasRole(['super_admin', 'admin', 'editor'])): ?>
            <a href="categories_edit.php?id=<?= $category['id'] ?>" 
               class="btn btn-warning" title="Edit">
                <i class="bi bi-pencil"></i>
            </a>
        <?php endif; ?>

        <?php if (hasRole(['super_admin', 'admin'])): ?>
            <a href="categories_delete.php?id=<?= $category['id'] ?>" 
               class="btn btn-danger" 
               onclick="return confirm('Yakin hapus kategori ini?<?= $category['post_count'] > 0 ? '\n\nKategori ini memiliki ' . $category['post_count'] . ' post!' : '' ?>')"
               title="Hapus">
                <i class="bi bi-trash"></i>
            </a>
        <?php endif; ?>
    </div>
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
