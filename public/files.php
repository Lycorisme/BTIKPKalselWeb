<?php
/**
 * Files/Downloads Page
 * File listing dengan categories, download counter, dan file type icons
 */

require_once 'config.php';

// Get category filter
$category_id = $_GET['category'] ?? null;
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Build query conditions
$where_conditions = ["df.is_active = 1", "df.deleted_at IS NULL"];
$params = [];

if (!empty($category_id) && is_numeric($category_id)) {
    $where_conditions[] = "df.category_id = ?";
    $params[] = $category_id;
}

$where_sql = implode(' AND ', $where_conditions);

// Get total count
try {
    $count_sql = "SELECT COUNT(*) as total FROM downloadable_files df WHERE {$where_sql}";
    $stmt = $db->prepare($count_sql);
    $stmt->execute($params);
    $total_files = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_files / $per_page);
} catch (Exception $e) {
    error_log($e->getMessage());
    $total_files = 0;
    $total_pages = 0;
}

// Get files
try {
    $stmt = $db->prepare("
        SELECT df.*, fc.name as category_name, fc.slug as category_slug, u.name as uploader_name
        FROM downloadable_files df
        LEFT JOIN file_categories fc ON df.category_id = fc.id
        LEFT JOIN users u ON df.uploaded_by = u.id
        WHERE {$where_sql}
        ORDER BY df.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute(array_merge($params, [$per_page, $offset]));
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log($e->getMessage());
    $files = [];
}

// Get categories
try {
    $stmt = $db->query("
        SELECT fc.*, COUNT(df.id) as file_count
        FROM file_categories fc
        LEFT JOIN downloadable_files df ON df.category_id = fc.id AND df.is_active = 1 AND df.deleted_at IS NULL
        WHERE fc.is_active = 1
        GROUP BY fc.id
        ORDER BY fc.display_order ASC, fc.name ASC
    ");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $categories = [];
}

// Page variables
$pageNamespace = 'files';
$pageTitle = 'Unduhan File - ' . getSetting('site_name');
$pageDescription = 'Download file, dokumen, dan materi pembelajaran';

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
            <span class="text-gray-600">Unduhan</span>
        </nav>
    </div>
</div>

<!-- Files Section -->
<section class="py-12 bg-gray-50">
    <div class="container mx-auto px-4">
        
        <!-- Header -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">
                <i class="fas fa-download text-blue-600 mr-2"></i>
                Unduhan File
            </h1>
            <p class="text-gray-600 text-lg max-w-2xl mx-auto">
                Download file, dokumen, panduan, dan materi pembelajaran
            </p>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            
            <!-- Sidebar - Categories (1/4 width) -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-folder text-blue-600 mr-2"></i>
                        Kategori
                    </h3>
                    
                    <!-- All Files -->
                    <a href="<?= BASE_URL ?>files.php" 
                       class="flex justify-between items-center py-3 px-4 mb-2 rounded-lg <?= empty($category_id) ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-50' ?> transition">
                        <span class="flex items-center">
                            <i class="fas fa-folder-open mr-2"></i>
                            Semua File
                        </span>
                        <span class="<?= empty($category_id) ? 'bg-white text-blue-600' : 'bg-gray-200 text-gray-700' ?> text-xs px-2 py-1 rounded">
                            <?= $total_files ?>
                        </span>
                    </a>
                    
                    <!-- Categories List -->
                    <div class="space-y-1">
                        <?php foreach ($categories as $cat): ?>
                        <a href="?category=<?= $cat['id'] ?>" 
                           class="flex justify-between items-center py-3 px-4 rounded-lg <?= $category_id == $cat['id'] ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-50' ?> transition group">
                            <span class="flex items-center">
                                <i class="<?= $cat['icon'] ?? 'fas fa-folder' ?> mr-2"></i>
                                <span class="truncate"><?= htmlspecialchars($cat['name']) ?></span>
                            </span>
                            <span class="<?= $category_id == $cat['id'] ? 'bg-white text-blue-600' : 'bg-gray-200 text-gray-700' ?> text-xs px-2 py-1 rounded flex-shrink-0 ml-2">
                                <?= $cat['file_count'] ?>
                            </span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Files List (3/4 width) -->
            <div class="lg:col-span-3">
                
                <?php if (empty($files)): ?>
                
                <!-- Empty State -->
                <div class="bg-white rounded-lg shadow-md p-12 text-center">
                    <i class="fas fa-folder-open text-6xl text-gray-300 mb-4"></i>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Belum Ada File</h2>
                    <p class="text-gray-600">
                        <?= !empty($category_id) ? 'Tidak ada file dalam kategori ini' : 'File unduhan akan ditampilkan di sini' ?>
                    </p>
                </div>
                
                <?php else: ?>
                
                <!-- Files Grid -->
                <div class="grid grid-cols-1 gap-4">
                    <?php foreach ($files as $file): 
                        $fileIcon = getFileIcon($file['file_type']);
                    ?>
                    <article class="bg-white rounded-lg shadow-md hover:shadow-xl transition-shadow overflow-hidden">
                        <div class="flex flex-col md:flex-row">
                            
                            <!-- File Icon -->
                            <div class="md:w-32 flex items-center justify-center bg-gray-50 p-6">
                                <i class="fas <?= $fileIcon['icon'] ?> text-6xl <?= $fileIcon['color'] ?>"></i>
                            </div>
                            
                            <!-- File Info -->
                            <div class="flex-1 p-6">
                                <!-- Category Badge -->
                                <?php if (!empty($file['category_name'])): ?>
                                <span class="inline-block bg-blue-100 text-blue-600 text-xs font-medium px-2 py-1 rounded mb-2">
                                    <i class="fas fa-folder mr-1"></i>
                                    <?= htmlspecialchars($file['category_name']) ?>
                                </span>
                                <?php endif; ?>
                                
                                <!-- Title -->
                                <h3 class="text-xl font-bold text-gray-900 mb-2">
                                    <?= htmlspecialchars($file['title']) ?>
                                </h3>
                                
                                <!-- Description -->
                                <?php if (!empty($file['description'])): ?>
                                <p class="text-gray-600 mb-4 line-clamp-2">
                                    <?= htmlspecialchars($file['description']) ?>
                                </p>
                                <?php endif; ?>
                                
                                <!-- Meta Info -->
                                <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500 mb-4">
                                    <span class="flex items-center">
                                        <i class="fas fa-file mr-1"></i>
                                        <?= strtoupper($file['file_type']) ?>
                                    </span>
                                    <span class="flex items-center">
                                        <i class="fas fa-hdd mr-1"></i>
                                        <?= formatFileSize($file['file_size']) ?>
                                    </span>
                                    <span class="flex items-center">
                                        <i class="fas fa-download mr-1"></i>
                                        <?= number_format($file['download_count']) ?> unduhan
                                    </span>
                                    <span class="flex items-center">
                                        <i class="far fa-calendar mr-1"></i>
                                        <?= formatTanggal($file['created_at'], 'd M Y') ?>
                                    </span>
                                </div>
                                
                                <!-- Download Button -->
                                <a href="<?= BASE_URL ?>api/download.php?id=<?= $file['id'] ?>" 
                                   class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                                    <i class="fas fa-download mr-2"></i>
                                    Download File
                                </a>
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
                        <a href="?category=<?= $category_id ?>&page=<?= $page - 1 ?>" 
                           class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                        <a href="?category=<?= $category_id ?>&page=<?= $i ?>" 
                           class="px-4 py-2 <?= $i === $page ? 'bg-blue-600 text-white' : 'bg-white border border-gray-300 hover:bg-gray-50' ?> rounded-lg transition">
                            <?= $i ?>
                        </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <a href="?category=<?= $category_id ?>&page=<?= $page + 1 ?>" 
                           class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <?php endif; ?>
                    </nav>
                </div>
                <?php endif; ?>
                
                <?php endif; ?>
                
            </div>
        </div>
    </div>
</section>

<?php include 'templates/footer.php'; ?>
