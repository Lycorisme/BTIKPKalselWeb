<?php
/**
 * News Detail Page
 * Menampilkan detail berita lengkap
 */

require_once '../../config/config.php';
require_once '../../core/Database.php';
require_once '../../core/Helper.php';
require_once '../../models/Post.php';
require_once '../../models/Tag.php';

// Get slug from URL
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    redirect(BASE_URL . 'news/');
}

// Initialize models
$postModel = new Post();
$tagModel = new Tag();

// Get post by slug
$post = $postModel->getBySlug($slug);

if (!$post) {
    redirect(BASE_URL . 'news/');
}

// Increment view count
$postModel->incrementView($post['id']);

// Get post tags
$tags = $tagModel->getByPostId($post['id']);

// Get related posts (same category)
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("
    SELECT p.*, u.name as author_name
    FROM posts p
    JOIN users u ON p.author_id = u.id
    WHERE p.category_id = ? 
      AND p.id != ?
      AND p.status = 'published' 
      AND p.deleted_at IS NULL
    ORDER BY p.published_at DESC
    LIMIT 3
");
$stmt->execute([$post['category_id'], $post['id']]);
$relatedPosts = $stmt->fetchAll();

// SEO Meta
$pageTitle = $post['title'];
$metaDescription = $post['meta_description'] ?: truncateText($post['excerpt'], 160);

include '../includes/header.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="bg-light py-3">
    <div class="container">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Beranda</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>news/">Berita</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>news/category.php?slug=<?= $post['category_slug'] ?>"><?= $post['category_name'] ?></a></li>
            <li class="breadcrumb-item active"><?= truncateText($post['title'], 50) ?></li>
        </ol>
    </div>
</nav>

<!-- Main Content -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <!-- Article Content -->
            <div class="col-lg-8">
                <article class="mb-4">
                    <!-- Category Badge -->
                    <span class="badge bg-primary mb-3"><?= htmlspecialchars($post['category_name']) ?></span>
                    
                    <!-- Title -->
                    <h1 class="fw-bold mb-3"><?= htmlspecialchars($post['title']) ?></h1>
                    
                    <!-- Meta Info -->
                    <div class="d-flex align-items-center text-muted mb-4 pb-3 border-bottom">
                        <img src="<?= BASE_URL ?>public/assets/images/avatar-default.png" 
                             alt="Author" class="rounded-circle me-2" width="40" height="40"
                             onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($post['author_name']) ?>&background=0D6EFD&color=fff'">
                        <div>
                            <strong><?= htmlspecialchars($post['author_name']) ?></strong><br>
                            <small>
                                <i class="bi bi-calendar"></i> <?= formatTanggal($post['published_at'], 'd F Y H:i') ?> WITA |
                                <i class="bi bi-eye"></i> <?= formatNumber($post['view_count']) ?> views
                            </small>
                        </div>
                    </div>
                    
                    <!-- Featured Image -->
                    <?php if ($post['featured_image']): ?>
                        <figure class="mb-4">
                            <img src="<?= uploadUrl($post['featured_image']) ?>" 
                                 alt="<?= htmlspecialchars($post['title']) ?>"
                                 class="img-fluid rounded shadow-sm w-100">
                            <figcaption class="text-muted small mt-2 text-center">
                                <?= htmlspecialchars($post['title']) ?>
                            </figcaption>
                        </figure>
                    <?php endif; ?>
                    
                    <!-- Content -->
                    <div class="article-content mb-4">
                        <?= $post['content'] ?>
                    </div>
                    
                    <!-- Tags -->
                    <?php if (!empty($tags)): ?>
                        <div class="mb-4 pb-4 border-bottom">
                            <strong>Tags:</strong>
                            <?php foreach ($tags as $tag): ?>
                                <a href="<?= BASE_URL ?>news/tag.php?slug=<?= $tag['slug'] ?>" 
                                   class="badge bg-light text-dark border me-1">
                                    #<?= htmlspecialchars($tag['name']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Share Buttons -->
                    <div class="mb-4">
                        <strong class="d-block mb-2">Bagikan:</strong>
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode(BASE_URL . 'news/detail.php?slug=' . $post['slug']) ?>" 
                           target="_blank" class="btn btn-sm btn-primary me-2">
                            <i class="bi bi-facebook"></i> Facebook
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?= urlencode(BASE_URL . 'news/detail.php?slug=' . $post['slug']) ?>&text=<?= urlencode($post['title']) ?>" 
                           target="_blank" class="btn btn-sm btn-info me-2">
                            <i class="bi bi-twitter"></i> Twitter
                        </a>
                        <a href="https://wa.me/?text=<?= urlencode($post['title'] . ' ' . BASE_URL . 'news/detail.php?slug=' . $post['slug']) ?>" 
                           target="_blank" class="btn btn-sm btn-success me-2">
                            <i class="bi bi-whatsapp"></i> WhatsApp
                        </a>
                        <button class="btn btn-sm btn-secondary" onclick="navigator.clipboard.writeText('<?= BASE_URL . 'news/detail.php?slug=' . $post['slug'] ?>'); alert('Link berhasil disalin!')">
                            <i class="bi bi-link-45deg"></i> Salin Link
                        </button>
                    </div>
                </article>
                
                <!-- Related Posts -->
                <?php if (!empty($relatedPosts)): ?>
                    <div class="mt-5">
                        <h4 class="fw-bold mb-4">Berita Terkait</h4>
                        <div class="row g-3">
                            <?php foreach ($relatedPosts as $related): ?>
                                <div class="col-md-4">
                                    <div class="card h-100 border-0 shadow-sm">
                                        <?php if ($related['featured_image']): ?>
                                            <img src="<?= uploadUrl($related['featured_image']) ?>" 
                                                 class="card-img-top" alt="<?= htmlspecialchars($related['title']) ?>"
                                                 style="height: 150px; object-fit: cover;">
                                        <?php endif; ?>
                                        <div class="card-body">
                                            <h6 class="card-title">
                                                <a href="<?= BASE_URL ?>news/detail.php?slug=<?= $related['slug'] ?>" 
                                                   class="text-decoration-none text-dark">
                                                    <?= htmlspecialchars($related['title']) ?>
                                                </a>
                                            </h6>
                                            <small class="text-muted">
                                                <i class="bi bi-calendar"></i> <?= formatTanggal($related['published_at'], 'd M Y') ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Latest Posts -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Berita Terbaru</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php
                        $latestPosts = $postModel->getRecent(5);
                        foreach ($latestPosts as $latest):
                        ?>
                            <div class="border-bottom p-3">
                                <a href="<?= BASE_URL ?>news/detail.php?slug=<?= $latest['slug'] ?>" 
                                   class="text-decoration-none">
                                    <div class="d-flex">
                                        <?php if ($latest['featured_image']): ?>
                                            <img src="<?= uploadUrl($latest['featured_image']) ?>" 
                                                 class="me-2 rounded" width="80" height="60" style="object-fit: cover;">
                                        <?php endif; ?>
                                        <div>
                                            <h6 class="mb-1 text-dark"><?= truncateText($latest['title'], 60) ?></h6>
                                            <small class="text-muted">
                                                <i class="bi bi-calendar"></i> <?= formatTanggal($latest['published_at'], 'd M Y') ?>
                                            </small>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Popular Tags -->
                <?php
                $popularTags = $tagModel->getPopular(10);
                if (!empty($popularTags)):
                ?>
                    <div class="card mb-4 shadow-sm">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0">Tag Populer</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($popularTags as $popularTag): ?>
                                <a href="<?= BASE_URL ?>news/tag.php?slug=<?= $popularTag['slug'] ?>" 
                                   class="badge bg-light text-dark border me-1 mb-2">
                                    #<?= htmlspecialchars($popularTag['name']) ?> (<?= $popularTag['post_count'] ?>)
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- CTA Box -->
                <div class="card bg-primary text-white shadow-sm">
                    <div class="card-body text-center">
                        <h5 class="fw-bold">Butuh Bantuan?</h5>
                        <p class="mb-3">Hubungi kami untuk informasi lebih lanjut</p>
                        <a href="<?= BASE_URL ?>contact.php" class="btn btn-light">
                            <i class="bi bi-telephone"></i> Kontak Kami
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.article-content {
    font-size: 1.1rem;
    line-height: 1.8;
}

.article-content img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin: 20px 0;
}

.article-content p {
    margin-bottom: 1.2rem;
}

.article-content h2, .article-content h3 {
    margin-top: 2rem;
    margin-bottom: 1rem;
    font-weight: 600;
}

.article-content ul, .article-content ol {
    margin-bottom: 1.2rem;
    padding-left: 2rem;
}

.article-content blockquote {
    border-left: 4px solid #0d6efd;
    padding-left: 1rem;
    margin: 1.5rem 0;
    font-style: italic;
    color: #6c757d;
}
</style>

<?php include '../includes/footer.php'; ?>
