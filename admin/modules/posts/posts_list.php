<?php
/**
 * Posts List Page
 * Manage posts/articles with advanced filters
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../core/Model.php';
require_once '../../../core/Pagination.php';
require_once '../../../models/Post.php';
require_once '../../../models/PostCategory.php';

$pageTitle = 'Kelola Post';

$postModel = new Post();
$categoryModel = new PostCategory();

// Get items per page
$itemsPerPage = (int)getSetting('items_per_page', 10);

// Get filters
$status = $_GET['status'] ?? '';
$categoryId = $_GET['category_id'] ?? '';
$isFeatured = $_GET['is_featured'] ?? '';
$search = $_GET['search'] ?? '';
$page = $_GET['page'] ?? 1;

// Build filters
$filters = [];
if ($status) $filters['status'] = $status;
if ($categoryId) $filters['category_id'] = $categoryId;
if ($isFeatured !== '') $filters['is_featured'] = $isFeatured;
if ($search) $filters['search'] = $search;

// Get posts with pagination
$result = $postModel->getPaginated($page, $itemsPerPage, $filters);
$posts = $result['data'];

// Initialize pagination
$pagination = new Pagination(
    $result['total'],
    $result['per_page'],
    $result['current_page']
);

// Get categories for filter
$categories = $categoryModel->getActive();

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6">
                <h3><?= $pageTitle ?></h3>
                <p class="text-subtitle text-muted">Kelola berita, artikel, dan pengumuman</p>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active">Post</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Daftar Post</h5>
                    <a href="posts_add.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Tambah Post
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
                                <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                                <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
                                <option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>Archived</option>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <select name="category_id" class="form-select">
                                <option value="">Semua Kategori</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <select name="is_featured" class="form-select">
                                <option value="">Featured?</option>
                                <option value="1" <?= $isFeatured === '1' ? 'selected' : '' ?>>Ya</option>
                                <option value="0" <?= $isFeatured === '0' ? 'selected' : '' ?>>Tidak</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Cari judul post..." 
                                   value="<?= htmlspecialchars($search) ?>">
                        </div>
                        
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                        
                        <?php if ($status || $categoryId || $isFeatured !== '' || $search): ?>
                            <div class="col-md-2">
                                <a href="posts_list.php" class="btn btn-secondary w-100">
                                    <i class="bi bi-x-circle"></i> Reset
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </form>

                <!-- Info -->
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    Menampilkan <strong><?= count($posts) ?></strong> dari <strong><?= formatNumber($result['total']) ?></strong> post
                </div>

                <!-- Posts Table -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="50">No</th>
                                <th>Judul</th>
                                <th width="120">Kategori</th>
                                <th width="120">Penulis</th>
                                <th width="100" class="text-center">Status</th>
                                <th width="100" class="text-center">Tanggal</th>
                                <th width="70" class="text-center">Views</th>
                                <th width="180" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($posts)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        Belum ada data
                                        <?php if ($status || $categoryId || $search): ?>
                                            <br><small>Coba ubah filter pencarian</small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($posts as $post): ?>
                                    <tr>
                                        <td><?= $post['id'] ?></td>
                                        <td>
                                            <div>
                                                <strong><?= htmlspecialchars($post['title']) ?></strong>
                                                <?php if ($post['is_featured']): ?>
                                                    <span class="badge bg-warning text-dark ms-1">
                                                        <i class="bi bi-star-fill"></i> Featured
                                                    </span>
                                                <?php endif; ?>
                                                <br>
                                                <small class="text-muted">
                                                    <?= truncateText(strip_tags($post['excerpt']), 60) ?>
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary">
                                                <?= htmlspecialchars($post['category_name']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small><?= htmlspecialchars($post['author_name']) ?></small>
                                        </td>
                                        <td class="text-center">
                                            <?= getStatusBadge($post['status']) ?>
                                        </td>
                                        <td class="text-center">
                                            <small><?= formatTanggal($post['published_at'] ?: $post['created_at'], 'd M Y') ?></small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-info"><?= formatNumber($post['view_count']) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <a href="posts_view.php?id=<?= $post['id'] ?>" 
                                                   class="btn btn-info" title="Lihat">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <?php if (hasRole(['super_admin', 'admin', 'editor'])): ?>
                                                    <a href="posts_edit.php?id=<?= $post['id'] ?>" 
                                                       class="btn btn-warning" title="Edit">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (hasRole(['super_admin', 'admin'])): ?>
                                                    <a href="posts_delete.php?id=<?= $post['id'] ?>" 
                                                       class="btn btn-danger" 
                                                       onclick="return confirm('Yakin hapus post ini?')"
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
