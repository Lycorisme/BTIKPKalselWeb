<?php
/**
 * Tag Page
 * Menampilkan berita berdasarkan tag
 */

require_once '../../config/config.php';
require_once '../../core/Database.php';
require_once '../../core/Helper.php';
require_once '../../core/Pagination.php';
require_once '../../models/Post.php';
require_once '../../models/Tag.php';

// Get tag slug
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    redirect(BASE_URL . 'news/');
}

// Initialize models
$postModel = new Post();
$tagModel = new Tag();

// Get tag
$tag = $tagModel->findBy(['slug' => $slug]);

if (!$tag) {
    redirect(BASE_URL . 'news/');
}

// Get posts with this tag
$page = $_GET['page'] ?? 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

$db = Database::getInstance()->getConnection();

// Count total posts with this tag
$stmt = $db->prepare("
    SELECT COUNT(DISTINCT p.id) 
    FROM posts p
    JOIN post_tags pt ON p.id = pt.post_id
    WHERE pt.tag_id = ? 
      AND p.status = 'published' 
      AND p.deleted_at IS NULL
");
$stmt->execute([$tag['id']]);
$total = $stmt->fetchColumn();

// Get posts
$stmt = $db->prepare("
    SELECT DISTINCT p.*, 
           pc.name as category_name,
           pc.slug as category_slug,
           u.name as author_name
    FROM posts p
    JOIN post_tags pt ON p.id = pt.post_id
    JOIN post_categories pc ON p.category_id = pc.id
    JOIN users u ON p.author_id = u.id
    WHERE pt.tag_id = ? 
      AND p.status = 'published' 
      AND p.deleted_at IS NULL
    ORDER BY p.published_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute([$tag['id']]);
$posts = $stmt->fetchAll();

// Pagination
$pagination = new Pagination(
    $total,
    $perPage,
    $page,
    BASE_URL . 'news/tag.php?slug=' . $slug
);

$pageTitle = 'Tag: ' . $tag['name'];

include '../includes/header.php';
?>

<!-- Page Header -->
<div class="bg-primary text-white py-5">
    <div class="container">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb text-white">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>" class="text-white">Beranda</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>news/" class="text-white">Berita</a></li>
                <li class="breadcrumb-item active">Tag: <?= htmlspecialchars($tag['name']) ?></li>
            </ol>
        </nav>
        <h1 class="fw-bold mb-2">
            <i class="bi bi-hash"></i> <?= htmlspecialchars($tag['name']) ?>
        </h1>
        <p class="mb-0"><?= formatNumber($total) ?> berita dengan tag ini</p>
    </div>
</div>

<!-- Content -->
<section class="py-5">
    <div class="container">
        <?php if (empty($posts)): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox display-1 text-muted"></i>
                <h4 class="mt-3">Belum ada berita dengan tag ini</h4>
                <a href="<?= BASE_URL ?>news/" class="btn btn-primary mt-3">Lihat Semua Berita</a>
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
                            <div class="card-body">
                                <span class="badge bg-primary mb-2">
                                    <?= htmlspecialchars($post['category_name']) ?>
                                </span>
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
            
            <?php if ($total > $perPage): ?>
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
