<?php
/**
 * Search Results Page
 * Full-text search dengan filter & sorting
 */

require_once 'config.php';

// Get search query
$query = trim($_GET['q'] ?? '');
$category_filter = $_GET['category'] ?? '';
$sort = $_GET['sort'] ?? 'relevance'; // relevance, date, views
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Sanitize query
$query = htmlspecialchars($query, ENT_QUOTES, 'UTF-8');

// Build search query
$where_conditions = ["p.status = 'published'", "p.deleted_at IS NULL"];
$params = [];

// Search in title, content, and excerpt
if (!empty($query)) {
    $search_term = "%{$query}%";
    $where_conditions[] = "(p.title LIKE ? OR p.content LIKE ? OR p.excerpt LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

// Category filter
if (!empty($category_filter) && is_numeric($category_filter)) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_filter;
}

// Build WHERE clause
$where_sql = implode(' AND ', $where_conditions);

// Sorting
$order_by = match($sort) {
    'date' => 'p.created_at DESC',
    'views' => 'p.view_count DESC',
    'title' => 'p.title ASC',
    default => 'relevance DESC, p.created_at DESC' // Relevance = keyword match count
};

// Get total count
try {
    $count_sql = "
        SELECT COUNT(*) as total
        FROM posts p
        WHERE {$where_sql}
    ";
    $stmt = $db->prepare($count_sql);
    $stmt->execute($params);
    $total_results = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_results / $per_page);
} catch (Exception $e) {
    error_log($e->getMessage());
    $total_results = 0;
    $total_pages = 0;
}

// Get search results
try {
    // Calculate relevance score if searching
    if (!empty($query)) {
        $relevance_sql = "
            (
                (CASE WHEN p.title LIKE ? THEN 3 ELSE 0 END) +
                (CASE WHEN p.excerpt LIKE ? THEN 2 ELSE 0 END) +
                (CASE WHEN p.content LIKE ? THEN 1 ELSE 0 END)
            ) as relevance
        ";
        $relevance_params = [$search_term, $search_term, $search_term];
    } else {
        $relevance_sql = "0 as relevance";
        $relevance_params = [];
    }
    
    $sql = "
        SELECT p.*,
               c.name as category_name,
               c.slug as category_slug,
               u.name as author_name,
               {$relevance_sql}
        FROM posts p
        LEFT JOIN post_categories c ON p.category_id = c.id
        LEFT JOIN users u ON p.author_id = u.id
        WHERE {$where_sql}
        ORDER BY {$order_by}
        LIMIT {$per_page} OFFSET {$offset}
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute(array_merge($relevance_params, $params));
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log($e->getMessage());
    $results = [];
}

// Get categories for filter
try {
    $stmt = $db->query("
        SELECT id, name, slug 
        FROM post_categories 
        WHERE deleted_at IS NULL 
        ORDER BY name ASC
    ");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $categories = [];
}

// Page variables
$pageNamespace = 'search';
$pageTitle = !empty($query) ? "Hasil Pencarian: {$query}" : "Pencarian";
$pageDescription = "Hasil pencarian untuk: {$query}";

include 'templates/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-gray-100 py-4">
    <div class="container mx-auto px-4">
        <nav class="flex items-center gap-2 text-sm">
            <a href="<?= BASE_URL ?>" class="text-blue-600 hover:text-blue-700">
                <i class="fas fa-home"></i> Beranda
            </a>
            <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
            <span class="text-gray-600">Pencarian</span>
            <?php if (!empty($query)): ?>
            <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
            <span class="text-gray-600">"<?= htmlspecialchars($query) ?>"</span>
            <?php endif; ?>
        </nav>
    </div>
</div>

<!-- Search Section -->
<section class="py-12 bg-gray-50">
    <div class="container mx-auto px-4">
        
        <!-- Search Box -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <form action="<?= BASE_URL ?>search.php" method="GET" class="space-y-4">
                <div class="flex flex-col md:flex-row gap-4">
                    <!-- Search Input -->
                    <div class="flex-1">
                        <div class="relative">
                            <input type="text" 
                                   name="q" 
                                   value="<?= htmlspecialchars($query) ?>" 
                                   placeholder="Cari artikel, berita, informasi..." 
                                   class="w-full px-4 py-3 pl-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   required>
                            <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        </div>
                    </div>
                    
                    <!-- Category Filter -->
                    <div class="md:w-48">
                        <select name="category" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Semua Kategori</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $category_filter == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Search Button -->
                    <button type="submit" class="px-8 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                        <i class="fas fa-search mr-2"></i>Cari
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Results Info & Sorting -->
        <?php if (!empty($query)): ?>
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    <?php if ($total_results > 0): ?>
                        Ditemukan <?= number_format($total_results) ?> hasil
                    <?php else: ?>
                        Tidak ada hasil
                    <?php endif; ?>
                </h1>
                <p class="text-gray-600 mt-1">
                    untuk pencarian: <strong>"<?= htmlspecialchars($query) ?>"</strong>
                </p>
            </div>
            
            <!-- Sort Options -->
            <?php if ($total_results > 0): ?>
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-600">Urutkan:</span>
                <select onchange="window.location.href=this.value" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="?q=<?= urlencode($query) ?>&category=<?= $category_filter ?>&sort=relevance" <?= $sort === 'relevance' ? 'selected' : '' ?>>
                        Relevansi
                    </option>
                    <option value="?q=<?= urlencode($query) ?>&category=<?= $category_filter ?>&sort=date" <?= $sort === 'date' ? 'selected' : '' ?>>
                        Terbaru
                    </option>
                    <option value="?q=<?= urlencode($query) ?>&category=<?= $category_filter ?>&sort=views" <?= $sort === 'views' ? 'selected' : '' ?>>
                        Terpopuler
                    </option>
                    <option value="?q=<?= urlencode($query) ?>&category=<?= $category_filter ?>&sort=title" <?= $sort === 'title' ? 'selected' : '' ?>>
                        Judul (A-Z)
                    </option>
                </select>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Results List (2/3 width) -->
            <div class="lg:col-span-2">
                <?php if (empty($query)): ?>
                
                <!-- Empty State: No Search Yet -->
                <div class="bg-white rounded-lg shadow-md p-12 text-center">
                    <i class="fas fa-search text-6xl text-gray-300 mb-4"></i>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Cari Artikel</h2>
                    <p class="text-gray-600">Gunakan form di atas untuk mencari artikel, berita, atau informasi</p>
                </div>
                
                <?php elseif (empty($results)): ?>
                
                <!-- Empty State: No Results -->
                <div class="bg-white rounded-lg shadow-md p-12 text-center">
                    <i class="fas fa-search-minus text-6xl text-gray-300 mb-4"></i>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Tidak Ada Hasil</h2>
                    <p class="text-gray-600 mb-4">
                        Tidak ditemukan hasil untuk "<strong><?= htmlspecialchars($query) ?></strong>"
                    </p>
                    <div class="space-y-2 text-sm text-gray-600">
                        <p>Saran:</p>
                        <ul class="list-disc list-inside">
                            <li>Periksa ejaan kata kunci</li>
                            <li>Gunakan kata kunci yang lebih umum</li>
                            <li>Coba kata kunci yang berbeda</li>
                            <li>Kurangi jumlah kata kunci</li>
                        </ul>
                    </div>
                </div>
                
                <?php else: ?>
                
                <!-- Results Grid -->
                <div class="space-y-6">
                    <?php foreach ($results as $post): ?>
                    <article class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow">
                        <div class="md:flex">
                            <!-- Thumbnail -->
                            <div class="md:w-64 md:flex-shrink-0">
                                <a href="<?= BASE_URL ?>post.php?slug=<?= $post['slug'] ?>" class="block h-48 md:h-full overflow-hidden">
                                    <img src="<?= get_featured_image($post['featured_image']) ?>" 
                                         alt="<?= htmlspecialchars($post['title']) ?>"
                                         class="w-full h-full object-cover transform hover:scale-110 transition-transform duration-300">
                                </a>
                            </div>
                            
                            <!-- Content -->
                            <div class="p-6 flex-1">
                                <!-- Category Badge -->
                                <?php if (!empty($post['category_name'])): ?>
                                <a href="<?= BASE_URL ?>category.php?slug=<?= $post['category_slug'] ?>" 
                                   class="inline-block bg-blue-100 text-blue-600 text-xs font-medium px-2 py-1 rounded mb-2">
                                    <?= htmlspecialchars($post['category_name']) ?>
                                </a>
                                <?php endif; ?>
                                
                                <!-- Title with highlight -->
                                <h2 class="text-xl font-bold text-gray-900 mb-2 hover:text-blue-600 transition">
                                    <a href="<?= BASE_URL ?>post.php?slug=<?= $post['slug'] ?>">
                                        <?php
                                        // Highlight search term in title
                                        $title = htmlspecialchars($post['title']);
                                        if (!empty($query)) {
                                            $title = preg_replace(
                                                '/(' . preg_quote($query, '/') . ')/i',
                                                '<mark class="bg-yellow-200 px-1">$1</mark>',
                                                $title
                                            );
                                        }
                                        echo $title;
                                        ?>
                                    </a>
                                </h2>
                                
                                <!-- Excerpt -->
                                <p class="text-gray-700 mb-4 line-clamp-2">
                                    <?php
                                    $excerpt = htmlspecialchars(truncateText($post['excerpt'] ?? strip_tags($post['content']), 150));
                                    if (!empty($query)) {
                                        $excerpt = preg_replace(
                                            '/(' . preg_quote($query, '/') . ')/i',
                                            '<mark class="bg-yellow-200 px-1">$1</mark>',
                                            $excerpt
                                        );
                                    }
                                    echo $excerpt;
                                    ?>
                                </p>
                                
                                <!-- Meta Info -->
                                <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500">
                                    <span>
                                        <i class="far fa-user mr-1"></i>
                                        <?= htmlspecialchars($post['author_name'] ?? 'Admin') ?>
                                    </span>
                                    <span>
                                        <i class="far fa-calendar mr-1"></i>
                                        <?= formatTanggal($post['created_at'], 'd M Y') ?>
                                    </span>
                                    <span>
                                        <i class="far fa-eye mr-1"></i>
                                        <?= number_format($post['view_count']) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="mt-8 flex justify-center">
                    <nav class="flex gap-2">
                        <?php if ($page > 1): ?>
                        <a href="?q=<?= urlencode($query) ?>&category=<?= $category_filter ?>&sort=<?= $sort ?>&page=<?= $page - 1 ?>" 
                           class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                        <a href="?q=<?= urlencode($query) ?>&category=<?= $category_filter ?>&sort=<?= $sort ?>&page=<?= $i ?>" 
                           class="px-4 py-2 <?= $i === $page ? 'bg-blue-600 text-white' : 'bg-white border border-gray-300 hover:bg-gray-50' ?> rounded-lg transition">
                            <?= $i ?>
                        </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <a href="?q=<?= urlencode($query) ?>&category=<?= $category_filter ?>&sort=<?= $sort ?>&page=<?= $page + 1 ?>" 
                           class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <?php endif; ?>
                    </nav>
                </div>
                <?php endif; ?>
                
                <?php endif; ?>
            </div>
            
            <!-- Sidebar (1/3 width) -->
            <div class="lg:col-span-1">
                <?php include 'templates/sidebar.php'; ?>
            </div>
            
        </div>
    </div>
</section>

<?php include 'templates/footer.php'; ?>
