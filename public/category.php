<?php
/**
 * Category Page - Posts by Category
 * Features:
 * - List posts filtered by category
 * - Category info
 * - Pagination
 */

require_once 'config.php';

// Get category slug from URL
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('Location: ' . BASE_URL . 'posts.php');
    exit;
}

// Get category details
$stmt = $db->prepare("
    SELECT c.*, COUNT(p.id) as post_count
    FROM post_categories c
    LEFT JOIN posts p ON p.category_id = c.id AND p.status = 'published' AND p.deleted_at IS NULL
    WHERE c.slug = ? AND c.deleted_at IS NULL
    GROUP BY c.id
");
$stmt->execute([$slug]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);

// 404 if category not found
if (!$category) {
    header('HTTP/1.0 404 Not Found');
    include '404.php';
    exit;
}

// Pagination settings
$perPage = 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);
$offset = ($page - 1) * $perPage;

// Get total posts in this category
$totalPosts = $category['post_count'];
$totalPages = ceil($totalPosts / $perPage);

// Get posts in this category
$stmt = $db->prepare("
    SELECT p.*, 
           c.name as category_name, 
           c.slug as category_slug,
           u.name as author_name
    FROM posts p
    LEFT JOIN post_categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.author_id = u.id
    WHERE p.category_id = ? AND p.status = 'published' AND p.deleted_at IS NULL
    ORDER BY p.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$category['id'], $perPage, $offset]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Page namespace
$pageNamespace = 'category';

// SEO Meta
$pageTitle = $category['name'] . ' - Berita & Artikel - ' . getSetting('site_name');
$pageDescription = $category['description'] ?? 'Artikel dalam kategori ' . $category['name'];
$pageKeywords = $category['name'] . ', berita, artikel, btikp kalsel';

// Include header
include 'templates/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-gray-100 py-4">
    <div class="container mx-auto px-4">
        <nav class="flex items-center space-x-2 text-sm">
            <a href="<?= BASE_URL ?>" class="text-blue-600 hover:text-blue-700">
                <i class="fas fa-home"></i> Beranda
            </a>
            <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
            <a href="<?= BASE_URL ?>posts.php" class="text-blue-600 hover:text-blue-700">Berita</a>
            <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
            <span class="text-gray-600"><?= htmlspecialchars($category['name']) ?></span>
        </nav>
    </div>
</div>

<!-- Main Content -->
<section class="py-12">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Posts Grid -->
            <div class="lg:col-span-2">
                <!-- Category Header -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-8" data-aos="fade-up">
                    <div class="flex items-center space-x-4">
                        <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-folder text-3xl text-blue-600"></i>
                        </div>
                        <div class="flex-1">
                            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                                <?= htmlspecialchars($category['name']) ?>
                            </h1>
                            <?php if (!empty($category['description'])): ?>
                            <p class="text-gray-600"><?= htmlspecialchars($category['description']) ?></p>
                            <?php endif; ?>
                            <div class="text-sm text-gray-500 mt-2">
                                <i class="fas fa-newspaper mr-1"></i>
                                <?= number_format($totalPosts) ?> artikel
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Posts Grid -->
                <?php if (!empty($posts)): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <?php foreach ($posts as $index => $post): ?>
                    <article class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300" 
                             data-aos="fade-up" 
                             data-aos-delay="<?= $index * 50 ?>">
                        <!-- Featured Image -->
                        <a href="<?= BASE_URL ?>post.php?slug=<?= $post['slug'] ?>" class="block overflow-hidden">
                            <img src="<?= get_featured_image($post['featured_image']) ?>" 
                                 alt="<?= htmlspecialchars($post['title']) ?>"
                                 class="w-full h-48 object-cover transform hover:scale-110 transition-transform duration-300">
                        </a>
                        
                        <!-- Content -->
                        <div class="p-6">
                            <!-- Date -->
                            <div class="mb-3">
                                <span class="text-xs text-gray-500">
                                    <i class="far fa-calendar mr-1"></i>
                                    <?= formatTanggal($post['created_at'], 'd M Y') ?>
                                </span>
                            </div>
                            
                            <!-- Title -->
                            <h3 class="text-xl font-bold mb-2 line-clamp-2">
                                <a href="<?= BASE_URL ?>post.php?slug=<?= $post['slug'] ?>" 
                                   class="text-gray-900 hover:text-blue-600 transition">
                                    <?= htmlspecialchars($post['title']) ?>
                                </a>
                            </h3>
                            
                            <!-- Excerpt -->
                            <p class="text-gray-600 mb-4 line-clamp-3 text-sm">
                                <?= truncateText($post['excerpt'], 120) ?>
                            </p>
                            
                            <!-- Meta Footer -->
                            <div class="flex items-center justify-between text-sm text-gray-500 pt-4 border-t">
                                <span class="flex items-center">
                                    <i class="far fa-user mr-1"></i>
                                    <?= htmlspecialchars($post['author_name'] ?? 'Admin') ?>
                                </span>
                                <span class="flex items-center">
                                    <i class="far fa-eye mr-1"></i>
                                    <?= number_format($post['view_count']) ?>
                                </span>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="flex justify-center" data-aos="fade-up">
                    <nav class="flex items-center space-x-2">
                        <!-- Previous -->
                        <?php if ($page > 1): ?>
                        <a href="?slug=<?= $slug ?>&page=<?= $page - 1 ?>" 
                           class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <?php endif; ?>
                        
                        <!-- Page Numbers -->
                        <?php
                        $start = max(1, $page - 2);
                        $end = min($totalPages, $page + 2);
                        
                        for ($i = $start; $i <= $end; $i++):
                            $active = ($i === $page) ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50';
                        ?>
                        <a href="?slug=<?= $slug ?>&page=<?= $i ?>" 
                           class="px-4 py-2 border border-gray-300 rounded-lg transition <?= $active ?>">
                            <?= $i ?>
                        </a>
                        <?php endfor; ?>
                        
                        <!-- Next -->
                        <?php if ($page < $totalPages): ?>
                        <a href="?slug=<?= $slug ?>&page=<?= $page + 1 ?>" 
                           class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <?php endif; ?>
                    </nav>
                </div>
                <?php endif; ?>
                
                <?php else: ?>
                <!-- No Posts -->
                <div class="bg-white rounded-lg shadow-md p-12 text-center">
                    <i class="fas fa-folder-open text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-bold text-gray-700 mb-2">Belum Ada Artikel</h3>
                    <p class="text-gray-500 mb-6">Kategori ini belum memiliki artikel</p>
                    <a href="<?= BASE_URL ?>posts.php" class="inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-arrow-left mr-2"></i>Lihat Semua Artikel
                    </a>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <?php include 'templates/sidebar.php'; ?>
            </div>
            
        </div>
    </div>
</section>

<?php
// Include footer
include 'templates/footer.php';
?>
