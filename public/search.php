<?php
/**
 * Global Search Page
 */

require_once '../config/config.php';
require_once '../core/Database.php';
require_once '../core/Helper.php';
require_once '../core/Pagination.php';
require_once '../models/Post.php';

$pageTitle = 'Pencarian';

// Get search query
$q = $_GET['q'] ?? '';
$page = $_GET['page'] ?? 1;

// Initialize model
$postModel = new Post();

// Search posts
$filters = [
    'status' => 'published',
    'search' => $q
];

$result = $postModel->getPaginated($page, 12, $filters);
$posts = $result['data'];

// Initialize pagination
$pagination = new Pagination(
    $result['total'],
    $result['per_page'],
    $result['current_page'],
    BASE_URL . 'search.php?q=' . urlencode($q)
);

include 'includes/header.php';
?>

<!-- Page Header -->
<div class="bg-light py-4">
    <div class="container">
        <h1 class="fw-bold mb-2">Hasil Pencarian</h1>
        <p class="lead mb-3">
            Menampilkan <?= formatNumber($result['total']) ?> hasil untuk 
            "<strong><?= htmlspecialchars($q) ?></strong>"
        </p>
        
        <!-- Search Again -->
        <form method="GET" action="<?= BASE_URL ?>search.php" class="mb-0">
            <div class="input-group">
                <input type="text" name="q" class="form-control form-control-lg" 
                       placeholder="Cari berita, artikel..." 
                       value="<?= htmlspecialchars($q) ?>" required>
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-search"></i> Cari
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Results -->
<section class="py-5">
    <div class="container">
        <?php if (empty($posts)): ?>
            <div class="text-center py-5">
                <i class="bi bi-search display-1 text-muted"></i>
                <h3 class="mt-3">Tidak ada hasil ditemukan</h3>
                <p class="text-muted">Coba gunakan kata kunci lain atau cek ejaan Anda</p>
                <a href="<?= BASE_URL ?>news/" class="btn btn-primary mt-3">
                    <i class="bi bi-arrow-left"></i> Kembali ke Berita
                </a>
            </div>
        <?php else: ?>
            <div class="row g-4 mb-4">
                <?php foreach ($posts as $post): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm">
                            <?php if ($post['featured_image']): ?>
                                <img src="<?= uploadUrl($post['featured_image']) ?>" 
                                     class="card-img-top" alt="<?= htmlspecialchars($post['title']) ?>"
                                     style="height: 200px; object-fit: cover;">
                            <?php endif; ?>
                            <div class="card-body">
                                <span class="badge bg-primary mb-2"><?= $post['category_name'] ?></span>
                                <h5 class="card-title">
                                    <a href="<?= BASE_URL ?>news/detail.php?slug=<?= $post['slug'] ?>" 
                                       class="text-decoration-none text-dark">
                                        <?= htmlspecialchars($post['title']) ?>
                                    </a>
                                </h5>
                                <p class="card-text text-muted small">
                                    <?= truncateText($post['excerpt'], 100) ?>
                                </p>
                                <small class="text-muted">
                                    <i class="bi bi-calendar"></i> <?= formatTanggal($post['published_at'], 'd M Y') ?> |
                                    <i class="bi bi-eye"></i> <?= formatNumber($post['view_count']) ?>
                                </small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($result['total'] > $result['per_page']): ?>
                <?= $pagination->render() ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
