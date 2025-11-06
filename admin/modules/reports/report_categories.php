<?php
/**
 * Report: Categories & Tags
 * Categories and tags report with statistics and PDF export
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

// Import mPDF
require_once '../../../vendor/autoload.php';

$pageTitle = 'Laporan Kategori & Tags';

$db = Database::getInstance()->getConnection();

// Get export flag
$exportPdf = $_GET['export_pdf'] ?? '';

// Get all categories with post count
$categoriesStmt = $db->query("
    SELECT 
        c.*,
        COUNT(p.id) as post_count
    FROM categories c
    LEFT JOIN posts p ON c.id = p.category_id AND p.deleted_at IS NULL
    GROUP BY c.id
    ORDER BY post_count DESC, c.name ASC
");
$categories = $categoriesStmt->fetchAll();

// Get all tags with post count
$tagsStmt = $db->query("
    SELECT 
        t.*,
        COUNT(pt.post_id) as post_count
    FROM tags t
    LEFT JOIN post_tags pt ON t.id = pt.tag_id
    LEFT JOIN posts p ON pt.post_id = p.id AND p.deleted_at IS NULL
    GROUP BY t.id
    ORDER BY post_count DESC, t.name ASC
");
$tags = $tagsStmt->fetchAll();

// Statistics
$stats = [
    'total_categories' => count($categories),
    'categories_with_posts' => 0,
    'total_tags' => count($tags),
    'tags_with_posts' => 0,
    'total_posts' => 0
];

foreach ($categories as $cat) {
    if ($cat['post_count'] > 0) {
        $stats['categories_with_posts']++;
        $stats['total_posts'] += $cat['post_count'];
    }
}

foreach ($tags as $tag) {
    if ($tag['post_count'] > 0) {
        $stats['tags_with_posts']++;
    }
}

// Get top 10 categories
$topCategories = array_slice($categories, 0, 10);

// Get top 10 tags
$topTags = array_slice($tags, 0, 10);

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
        include __DIR__ . '/templates/laporan_categories_pdf.php';
        $html = ob_get_clean();
        
        // Write HTML to PDF
        $mpdf->WriteHTML($html);
        
        // Output PDF
        $mpdf->Output('Laporan_Kategori_Tags_' . date('Ymd_His') . '.pdf', 'I');
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
                        <li class="breadcrumb-item active">Kategori & Tags</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <!-- Action Buttons -->
        <div class="card mb-3">
            <div class="card-body">
                <a href="?export_pdf=1" class="btn btn-danger" target="_blank">
                    <i class="bi bi-file-pdf"></i> Export PDF
                </a>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Total Kategori</h6>
                        <h3 class="mb-0"><?= formatNumber($stats['total_categories']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Kategori Terpakai</h6>
                        <h3 class="mb-0 text-success"><?= formatNumber($stats['categories_with_posts']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Total Tags</h6>
                        <h3 class="mb-0"><?= formatNumber($stats['total_tags']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Tags Terpakai</h6>
                        <h3 class="mb-0 text-info"><?= formatNumber($stats['tags_with_posts']) ?></h3>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Categories Table -->
            <div class="col-lg-6">
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Semua Kategori</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Kategori</th>
                                        <th>Jumlah Post</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($categories)): ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">Tidak ada data</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php $no = 1; foreach ($categories as $category): ?>
                                            <tr>
                                                <td><?= $no++ ?></td>
                                                <td><?= htmlspecialchars($category['name']) ?></td>
                                                <td><strong><?= formatNumber($category['post_count']) ?></strong></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Top Categories -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Top 10 Kategori</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <tbody>
                                    <?php foreach ($topCategories as $cat): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($cat['name']) ?></td>
                                            <td class="text-end"><strong><?= formatNumber($cat['post_count']) ?></strong> posts</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tags Table -->
            <div class="col-lg-6">
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Semua Tags</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Tag</th>
                                        <th>Jumlah Post</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($tags)): ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">Tidak ada data</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php $no = 1; foreach ($tags as $tag): ?>
                                            <tr>
                                                <td><?= $no++ ?></td>
                                                <td><?= htmlspecialchars($tag['name']) ?></td>
                                                <td><strong><?= formatNumber($tag['post_count']) ?></strong></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Top Tags -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Top 10 Tags</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <tbody>
                                    <?php foreach ($topTags as $tag): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($tag['name']) ?></td>
                                            <td class="text-end"><strong><?= formatNumber($tag['post_count']) ?></strong> posts</td>
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
