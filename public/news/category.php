<?php
/**
 * Category Page
 * Menampilkan berita berdasarkan kategori
 */

require_once '../../config/config.php';
require_once '../../core/Database.php';
require_once '../../core/Helper.php';
require_once '../../core/Pagination.php';
require_once '../../models/Post.php';
require_once '../../models/PostCategory.php';

// Get category slug
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    redirect(BASE_URL . 'news/');
}

// Initialize models
$postModel = new Post();
$categoryModel = new PostCategory();

// Get category
$category = $categoryModel->findBy(['slug' => $slug]);

if (!$category) {
    redirect(BASE_URL . 'news/');
}

// Get posts
$page = $_GET['page'] ?? 1;
$filters = [
    'status' => 'published',
    'category_id' => $category['id']
];

$result = $postModel->getPaginated($page, 12, $filters);
$posts = $result['data'];

$pagination = new Pagination(
    $result['total'],
    $result['per_page'],
    $result['current_page'],
    BASE_URL . 'news/category.php?slug=' . $slug
);

$pageTitle = $category['name'];

include '../includes/header.php';
?>

<!-- Page Header -->
<div class="bg-primary text-white py-5">
    <div class="container">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb text-white">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>" class="text-white">Beranda</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>news/" class="text-white">Berita</a></li>
                <li class="breadcrumb-item active"><?= $category['name'] ?></li>
            </ol>
        </nav>
        <h1 class="fw-bold mb-2"><?= htmlspecialchars($category['name']) ?></h1>
        <?php if ($category['description']): ?>
            <p class="lead mb-0"><?= htmlspecialchars($category['description']) ?></p>
        <?php endif; ?>
        <p class="mb-0 mt-2"><?= formatNumber($result['total']) ?> berita</p>
    </div>
</div>

<!-- Content -->
<section class="py-5">
    <div class="container">
        <?php if (empty($posts)): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox display-1 text-muted"></i>
                <h4 class="mt-3">Belum ada berita dalam kategori ini</h4>
                <a href="<?= BASE_URL ?>news/" class="btn btn-primary mt-3">Lihat Semua Berita</a>
            </div>
        <?php else: ?>
            <div class="row g-4 mb-4">
                <?php foreach ($posts as $post): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm hover-card">
                            <?php if ($post['featured_image']): ?>
                                <img src="<?= uploadUrl($post['featured_image']) ?>" 
                                     class="card-img-top" style="height: 200px; object-fit: cover;">
                            <?php endif; ?>
                            <div class="card-body">
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
                                    <i class="bi bi-calendar"></i> <?= formatTanggal($post['published_at'], 'd M Y') ?>
                                </small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($result['total'] > $result['per_page']): ?>
                <?= $pagination->render() ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<style>
.hover-card {
    transition: all 0.3s;
}
.hover-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.15) !important;
}
</style>

<?php include '../includes/footer.php'; ?>
