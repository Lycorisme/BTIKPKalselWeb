<?php
/**
 * News List Page
 * Menampilkan daftar semua berita dengan filter & pagination
 */

require_once '../../config/config.php';
require_once '../../core/Database.php';
require_once '../../core/Helper.php';
require_once '../../core/Pagination.php';
require_once '../../models/Post.php';
require_once '../../models/PostCategory.php';

$pageTitle = 'Berita & Artikel';

// Initialize models
$postModel = new Post();
$categoryModel = new PostCategory();

// Get filters
$categoryId = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';
$page = $_GET['page'] ?? 1;

// Build filters
$filters = ['status' => 'published'];
if ($categoryId) $filters['category_id'] = $categoryId;
if ($search) $filters['search'] = $search;

// Get posts with pagination
$result = $postModel->getPaginated($page, 12, $filters);
$posts = $result['data'];

// Initialize pagination
$pagination = new Pagination(
    $result['total'],
    $result['per_page'],
    $result['current_page']
);

// Get categories for filter
$categories = $categoryModel->getActive();

include '../includes/header.php';
?>

<!-- Page Header -->
<div class="bg-primary text-white py-5">
    <div class="container">
        <h1 class="fw-bold mb-2">Berita & Artikel</h1>
        <p class="lead mb-0">Informasi terbaru seputar pendidikan dan teknologi</p>
    </div>
</div>

<!-- Main Content -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-9">
                <!-- Filter Bar -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <select name="category" class="form-select">
                                    <option value="">Semua Kategori</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Cari berita..." value="<?= htmlspecialchars($search) ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-search"></i> Cari
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Results Info -->
                <?php if ($search || $categoryId): ?>
                    <div class="mb-3">
                        <p class="text-muted">
                            Menampilkan <?= formatNumber($result['total']) ?> hasil
                            <?php if ($search): ?>
                                untuk pencarian "<strong><?= htmlspecialchars($search) ?></strong>"
                            <?php endif; ?>
                            <?php if ($categoryId): ?>
                                dalam kategori "<strong><?= htmlspecialchars(array_column(array_filter($categories, fn($c) => $c['id'] == $categoryId), 'name')[0] ?? '') ?></strong>"
                            <?php endif; ?>
                            
                            <?php if ($search || $categoryId): ?>
                                <a href="<?= BASE_URL ?>news/" class="ms-2">Reset filter</a>
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endif; ?>
                
                <!-- Posts Grid -->
                <?php if (empty($posts)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox display-1 text-muted"></i>
                        <h4 class="mt-3">Tidak ada berita ditemukan</h4>
                        <p class="text-muted">Coba ubah filter atau kata kunci pencarian Anda</p>
                        <a href="<?= BASE_URL ?>news/" class="btn btn-primary">Lihat Semua Berita</a>
                    </div>
                <?php else: ?>
                    <div class="row g-4 mb-4">
                        <?php foreach ($posts as $post): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card h-100 shadow-sm hover-card">
                                    <?php if ($post['featured_image']): ?>
                                        <img src="<?= uploadUrl($post['featured_image']) ?>" 
                                             class="card-img-top" alt="<?= htmlspecialchars($post['title']) ?>"
                                             style="height: 200px; object-fit: cover;">
                                    <?php endif; ?>
                                    <div class="card-body d-flex flex-column">
                                        <span class="badge bg-primary mb-2 align-self-start">
                                            <?= htmlspecialchars($post['category_name']) ?>
                                        </span>
                                        <h5 class="card-title">
                                            <a href="<?= BASE_URL ?>news/detail.php?slug=<?= $post['slug'] ?>" 
                                               class="text-decoration-none text-dark">
                                                <?= htmlspecialchars($post['title']) ?>
                                            </a>
                                        </h5>
                                        <p class="card-text text-muted small flex-grow-1">
                                            <?= truncateText($post['excerpt'], 100) ?>
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <i class="bi bi-calendar"></i> <?= formatTanggal($post['published_at'], 'd M Y') ?>
                                            </small>
                                            <small class="text-muted">
                                                <i class="bi bi-eye"></i> <?= formatNumber($post['view_count']) ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($result['total'] > $result['per_page']): ?>
                        <div class="mt-4">
                            <?= $pagination->render() ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-3">
                <!-- Categories -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Kategori</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="<?= BASE_URL ?>news/" class="list-group-item list-group-item-action <?= empty($categoryId) ? 'active' : '' ?>">
                            Semua Kategori
                        </a>
                        <?php foreach ($categories as $cat): ?>
                            <a href="<?= BASE_URL ?>news/?category=<?= $cat['id'] ?>" 
                               class="list-group-item list-group-item-action <?= $categoryId == $cat['id'] ? 'active' : '' ?>">
                                <?= htmlspecialchars($cat['name']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Featured Posts -->
                <?php
                $featuredPosts = $postModel->getFeatured(3);
                if (!empty($featuredPosts)):
                ?>
                    <div class="card mb-4 shadow-sm">
                        <div class="card-header bg-warning">
                            <h5 class="mb-0">Berita Pilihan</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php foreach ($featuredPosts as $featured): ?>
                                <div class="border-bottom p-3">
                                    <a href="<?= BASE_URL ?>news/detail.php?slug=<?= $featured['slug'] ?>" 
                                       class="text-decoration-none">
                                        <div class="d-flex">
                                            <?php if ($featured['featured_image']): ?>
                                                <img src="<?= uploadUrl($featured['featured_image']) ?>" 
                                                     class="me-2 rounded" width="80" height="60" style="object-fit: cover;">
                                            <?php endif; ?>
                                            <div>
                                                <h6 class="mb-1 text-dark"><?= truncateText($featured['title'], 50) ?></h6>
                                                <small class="text-muted">
                                                    <?= formatTanggal($featured['published_at'], 'd M Y') ?>
                                                </small>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<style>
.hover-card {
    transition: all 0.3s ease;
}
.hover-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.15) !important;
}
</style>

<?php include '../includes/footer.php'; ?>
