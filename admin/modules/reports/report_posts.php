<?php
/**
 * Report: Posts/Articles
 * Main handler with filters and PDF generation
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

// Import mPDF
require_once '../../../vendor/autoload.php';

$pageTitle = 'Laporan Posts/Artikel';

$db = Database::getInstance()->getConnection();

// Get filters
$categoryId = $_GET['category_id'] ?? '';
$status = $_GET['status'] ?? '';
$authorId = $_GET['author_id'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$exportPdf = $_GET['export_pdf'] ?? '';

// Build query
$sql = "SELECT 
            p.*,
            c.name as category_name,
            u.name as author_name,
            (SELECT COUNT(*) FROM post_tags pt WHERE pt.post_id = p.id) as tag_count
        FROM posts p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN users u ON p.author_id = u.id
        WHERE p.deleted_at IS NULL";

$params = [];

if ($categoryId) {
    $sql .= " AND p.category_id = ?";
    $params[] = $categoryId;
}

if ($status) {
    $sql .= " AND p.status = ?";
    $params[] = $status;
}

if ($authorId) {
    $sql .= " AND p.author_id = ?";
    $params[] = $authorId;
}

if ($dateFrom) {
    $sql .= " AND DATE(p.created_at) >= ?";
    $params[] = $dateFrom;
}

if ($dateTo) {
    $sql .= " AND DATE(p.created_at) <= ?";
    $params[] = $dateTo;
}

$sql .= " ORDER BY p.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll();

// Statistics
$stats = [
    'total' => count($posts),
    'published' => 0,
    'draft' => 0,
    'archived' => 0,
    'total_views' => 0,
    'featured' => 0
];

foreach ($posts as $post) {
    $stats[$post['status']]++;
    $stats['total_views'] += $post['view_count'];
    if ($post['is_featured']) $stats['featured']++;
}

// Get posts by category
$categoryStatsStmt = $db->query("
    SELECT 
        c.name,
        COUNT(p.id) as total
    FROM categories c
    LEFT JOIN posts p ON c.id = p.category_id AND p.deleted_at IS NULL
    GROUP BY c.id, c.name
    ORDER BY total DESC
");
$categoryStats = $categoryStatsStmt->fetchAll();

// Get top authors
$authorStatsStmt = $db->query("
    SELECT 
        u.name,
        COUNT(p.id) as total
    FROM users u
    LEFT JOIN posts p ON u.id = p.author_id AND p.deleted_at IS NULL
    GROUP BY u.id, u.name
    ORDER BY total DESC
    LIMIT 10
");
$authorStats = $authorStatsStmt->fetchAll();

// Get categories for filter
$categoriesStmt = $db->query("SELECT * FROM categories ORDER BY name");
$categories = $categoriesStmt->fetchAll();

// Get authors for filter
$authorsStmt = $db->query("SELECT id, name FROM users ORDER BY name");
$authors = $authorsStmt->fetchAll();

// Export to PDF
if ($exportPdf === '1') {
    try {
        // Get site settings
        $siteName = getSetting('site_name', 'BTIKP Kalimantan Selatan');
        $siteTagline = getSetting('site_tagline', '');
        $contactPhone = getSetting('contact_phone', '');
        $contactEmail = getSetting('contact_email', '');
        $contactAddress = getSetting('contact_address', '');
        $siteLogo = getSetting('site_logo', '');
        
        // Initialize mPDF - Portrait A4
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 10,
            'margin_bottom' => 20,
            'margin_header' => 0,
            'margin_footer' => 10,
        ]);
        
        // Set default font
        $mpdf->SetDefaultFont('cambria');
        
        // Footer HTML
        $footer = '
        <table width="100%" style="border-top: 1px solid #000; padding-top: 5px; font-size: 9pt;">
            <tr>
                <td width="70%" style="text-align: left;">
                    ' . htmlspecialchars($siteName) . '
                </td>
                <td width="30%" style="text-align: right;">
                    Halaman {PAGENO} dari {nbpg}
                </td>
            </tr>
        </table>';
        
        $mpdf->SetHTMLFooter($footer);
        
        // Load template
        ob_start();
        include __DIR__ . '/templates/laporan_posts_pdf.php';
        $html = ob_get_clean();
        
        // Write HTML to PDF
        $mpdf->WriteHTML($html);
        
        // Output PDF
        $mpdf->Output('Laporan_Posts_' . date('Ymd_His') . '.pdf', 'I');
        exit;
        
    } catch (\Mpdf\MpdfException $e) {
        error_log($e->getMessage());
        setAlert('danger', 'Gagal generate PDF: ' . $e->getMessage());
    }
}

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6">
                <h3><?= $pageTitle ?></h3>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item">Laporan</li>
                        <li class="breadcrumb-item active">Posts</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <!-- Filter Card -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-funnel"></i> Filter Laporan
                </h5>
            </div>
            <div class="card-body">
                <form method="GET">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Kategori</label>
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
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">Semua Status</option>
                                <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
                                <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                                <option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>Archived</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Penulis</label>
                            <select name="author_id" class="form-select">
                                <option value="">Semua Penulis</option>
                                <?php foreach ($authors as $author): ?>
                                    <option value="<?= $author['id'] ?>" <?= $authorId == $author['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($author['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Dari Tanggal</label>
                            <input type="date" name="date_from" class="form-control" value="<?= $dateFrom ?>">
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Sampai Tanggal</label>
                            <input type="date" name="date_to" class="form-control" value="<?= $dateTo ?>">
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Tampilkan
                            </button>
                            <a href="report_posts.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset
                            </a>
                            <a href="?export_pdf=1<?= $categoryId ? '&category_id='.$categoryId : '' ?><?= $status ? '&status='.$status : '' ?><?= $authorId ? '&author_id='.$authorId : '' ?><?= $dateFrom ? '&date_from='.$dateFrom : '' ?><?= $dateTo ? '&date_to='.$dateTo : '' ?>" 
                               class="btn btn-danger" target="_blank">
                                <i class="bi bi-file-pdf"></i> Export PDF
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row mb-3">
            <div class="col-md-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Total Posts</h6>
                        <h3 class="mb-0"><?= formatNumber($stats['total']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Published</h6>
                        <h3 class="mb-0 text-success"><?= formatNumber($stats['published']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Draft</h6>
                        <h3 class="mb-0 text-secondary"><?= formatNumber($stats['draft']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Archived</h6>
                        <h3 class="mb-0 text-warning"><?= formatNumber($stats['archived']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Featured</h6>
                        <h3 class="mb-0 text-primary"><?= formatNumber($stats['featured']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Total Views</h6>
                        <h3 class="mb-0 text-info"><?= formatNumber($stats['total_views']) ?></h3>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Posts Table -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Daftar Posts</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Judul</th>
                                        <th>Kategori</th>
                                        <th>Penulis</th>
                                        <th>Status</th>
                                        <th>Views</th>
                                        <th>Tanggal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($posts)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">Tidak ada data</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php $no = 1; foreach ($posts as $post): ?>
                                            <tr>
                                                <td><?= $no++ ?></td>
                                                <td>
                                                    <?= htmlspecialchars($post['title']) ?>
                                                    <?php if ($post['is_featured']): ?>
                                                        <span class="badge bg-warning text-dark">Featured</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($post['category_name']) ?></td>
                                                <td><?= htmlspecialchars($post['author_name']) ?></td>
                                                <td><?= getStatusBadge($post['status']) ?></td>
                                                <td><?= formatNumber($post['view_count']) ?></td>
                                                <td><?= formatTanggal($post['created_at'], 'd M Y') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Side Stats -->
            <div class="col-lg-4">
                <!-- Posts by Category -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Posts per Kategori</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <tbody>
                                    <?php foreach ($categoryStats as $cat): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($cat['name']) ?></td>
                                            <td class="text-end"><strong><?= formatNumber($cat['total']) ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Top Authors -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Top Penulis</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <tbody>
                                    <?php foreach ($authorStats as $author): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($author['name']) ?></td>
                                            <td class="text-end"><strong><?= formatNumber($author['total']) ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>
