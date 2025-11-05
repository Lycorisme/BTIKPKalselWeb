<?php
/**
 * Homepage - Portal BTIKP Kalimantan Selatan
 */

require_once '../config/config.php';
require_once '../core/Database.php';
require_once '../core/Helper.php';
require_once '../models/Post.php';

$pageTitle = 'Beranda';

// Initialize models
$postModel = new Post();

// Get featured posts for slider
$featuredPosts = $postModel->getFeatured(5);

// Get recent posts
$recentPosts = $postModel->getRecent(6);

// Get posts by category
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT * FROM post_categories WHERE is_active = 1 ORDER BY name LIMIT 4");
$categories = $stmt->fetchAll();

$postsByCategory = [];
foreach ($categories as $category) {
    $stmt = $db->prepare("
        SELECT p.*, u.name as author_name
        FROM posts p
        JOIN users u ON p.author_id = u.id
        WHERE p.category_id = ? 
          AND p.status = 'published' 
          AND p.deleted_at IS NULL
        ORDER BY p.published_at DESC
        LIMIT 3
    ");
    $stmt->execute([$category['id']]);
    $postsByCategory[$category['id']] = $stmt->fetchAll();
}

include 'includes/header.php';
?>

<!-- Hero Section / Slider -->
<section class="hero-section">
    <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-indicators">
            <?php foreach ($featuredPosts as $index => $post): ?>
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="<?= $index ?>" 
                    <?= $index === 0 ? 'class="active" aria-current="true"' : '' ?> 
                    aria-label="Slide <?= $index + 1 ?>"></button>
            <?php endforeach; ?>
        </div>
        
        <div class="carousel-inner">
            <?php foreach ($featuredPosts as $index => $post): ?>
                <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                    <img src="<?= uploadUrl($post['featured_image']) ?>" 
                         class="d-block w-100" alt="<?= htmlspecialchars($post['title']) ?>"
                         style="height: 500px; object-fit: cover;">
                    <div class="carousel-caption d-none d-md-block" style="background: rgba(0,0,0,0.6); padding: 20px; border-radius: 10px;">
                        <span class="badge bg-primary mb-2"><?= $post['category_name'] ?></span>
                        <h2><?= htmlspecialchars($post['title']) ?></h2>
                        <p><?= truncateText($post['excerpt'], 150) ?></p>
                        <a href="<?= BASE_URL ?>news/detail.php?slug=<?= $post['slug'] ?>" 
                           class="btn btn-light">Baca Selengkapnya</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
    </div>
</section>

<!-- Recent News -->
<section class="py-5">
    <div class="container">
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="fw-bold">Berita Terbaru</h2>
                <div class="border-bottom border-3 border-primary" style="width: 80px;"></div>
            </div>
        </div>
        
        <div class="row g-4">
            <?php foreach ($recentPosts as $post): ?>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm hover-shadow">
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
                        </div>
                        <div class="card-footer bg-transparent border-top-0">
                            <small class="text-muted">
                                <i class="bi bi-calendar"></i> <?= formatTanggal($post['published_at'], 'd M Y') ?>
                                <i class="bi bi-eye ms-2"></i> <?= formatNumber($post['view_count']) ?>
                            </small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="row mt-4">
            <div class="col-12 text-center">
                <a href="<?= BASE_URL ?>news/" class="btn btn-primary">
                    Lihat Semua Berita <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Categories -->
<?php foreach ($categories as $category): ?>
    <?php if (!empty($postsByCategory[$category['id']])): ?>
        <section class="py-5 bg-light">
            <div class="container">
                <div class="row mb-4">
                    <div class="col-12">
                        <h3 class="fw-bold"><?= htmlspecialchars($category['name']) ?></h3>
                        <div class="border-bottom border-3 border-primary" style="width: 60px;"></div>
                    </div>
                </div>
                
                <div class="row g-4">
                    <?php foreach ($postsByCategory[$category['id']] as $post): ?>
                        <div class="col-md-4">
                            <div class="card h-100 border-0 shadow-sm">
                                <?php if ($post['featured_image']): ?>
                                    <img src="<?= uploadUrl($post['featured_image']) ?>" 
                                         class="card-img-top" style="height: 180px; object-fit: cover;">
                                <?php endif; ?>
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <a href="<?= BASE_URL ?>news/detail.php?slug=<?= $post['slug'] ?>" 
                                           class="text-decoration-none text-dark">
                                            <?= htmlspecialchars($post['title']) ?>
                                        </a>
                                    </h6>
                                    <p class="card-text text-muted small">
                                        <?= truncateText($post['excerpt'], 80) ?>
                                    </p>
                                    <small class="text-muted">
                                        <i class="bi bi-calendar"></i> <?= formatTanggal($post['published_at'], 'd M Y') ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
<?php endforeach; ?>

<style>
.hover-shadow {
    transition: all 0.3s ease;
}
.hover-shadow:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.15) !important;
}
</style>

<?php include 'includes/footer.php'; ?>
