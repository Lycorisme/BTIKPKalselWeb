<?php
/**
 * Report: System Overview
 * Comprehensive system overview report with all statistics
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

// Import mPDF
require_once '../../../vendor/autoload.php';

$pageTitle = 'Laporan System Overview';

$db = Database::getInstance()->getConnection();

// Get export flag
$exportPdf = $_GET['export_pdf'] ?? '';

// =====================
// POSTS STATISTICS
// =====================
$postsStats = [
    'total' => 0,
    'published' => 0,
    'draft' => 0,
    'archived' => 0,
    'featured' => 0,
    'total_views' => 0
];

$postsStmt = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published,
        SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
        SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) as archived,
        SUM(CASE WHEN is_featured = 1 THEN 1 ELSE 0 END) as featured,
        SUM(view_count) as total_views
    FROM posts
    WHERE deleted_at IS NULL
");
$postsData = $postsStmt->fetch();
if ($postsData) {
    $postsStats = [
        'total' => (int)$postsData['total'],
        'published' => (int)$postsData['published'],
        'draft' => (int)$postsData['draft'],
        'archived' => (int)$postsData['archived'],
        'featured' => (int)$postsData['featured'],
        'total_views' => (int)$postsData['total_views']
    ];
}

// =====================
// SERVICES STATISTICS
// =====================
$servicesStats = [
    'total' => 0,
    'published' => 0,
    'draft' => 0,
    'archived' => 0
];

$servicesStmt = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published,
        SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
        SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) as archived
    FROM services
    WHERE deleted_at IS NULL
");
$servicesData = $servicesStmt->fetch();
if ($servicesData) {
    $servicesStats = [
        'total' => (int)$servicesData['total'],
        'published' => (int)$servicesData['published'],
        'draft' => (int)$servicesData['draft'],
        'archived' => (int)$servicesData['archived']
    ];
}

// =====================
// USERS STATISTICS
// =====================
$usersStats = [
    'total' => 0,
    'super_admin' => 0,
    'admin' => 0,
    'editor' => 0,
    'author' => 0
];

$usersStmt = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN role = 'super_admin' THEN 1 ELSE 0 END) as super_admin,
        SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin,
        SUM(CASE WHEN role = 'editor' THEN 1 ELSE 0 END) as editor,
        SUM(CASE WHEN role = 'author' THEN 1 ELSE 0 END) as author
    FROM users
    WHERE deleted_at IS NULL
");
$usersData = $usersStmt->fetch();
if ($usersData) {
    $usersStats = [
        'total' => (int)$usersData['total'],
        'super_admin' => (int)$usersData['super_admin'],
        'admin' => (int)$usersData['admin'],
        'editor' => (int)$usersData['editor'],
        'author' => (int)$usersData['author']
    ];
}

// =====================
// CATEGORIES & TAGS
// =====================
$categoriesStmt = $db->query("SELECT COUNT(*) as total FROM categories");
$categoriesData = $categoriesStmt->fetch();
$totalCategories = (int)$categoriesData['total'];

$tagsStmt = $db->query("SELECT COUNT(*) as total FROM tags");
$tagsData = $tagsStmt->fetch();
$totalTags = (int)$tagsData['total'];

// =====================
// ACTIVITY LOGS
// =====================
$activitiesStmt = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN action_type = 'CREATE' THEN 1 ELSE 0 END) as creates,
        SUM(CASE WHEN action_type = 'UPDATE' THEN 1 ELSE 0 END) as updates,
        SUM(CASE WHEN action_type = 'DELETE' THEN 1 ELSE 0 END) as deletes,
        SUM(CASE WHEN action_type = 'LOGIN' THEN 1 ELSE 0 END) as logins
    FROM activity_logs
");
$activitiesData = $activitiesStmt->fetch();
$activitiesStats = [
    'total' => (int)$activitiesData['total'],
    'creates' => (int)$activitiesData['creates'],
    'updates' => (int)$activitiesData['updates'],
    'deletes' => (int)$activitiesData['deletes'],
    'logins' => (int)$activitiesData['logins']
];

// =====================
// RECENT POSTS
// =====================
$recentPostsStmt = $db->query("
    SELECT p.title, p.created_at, u.name as author_name
    FROM posts p
    LEFT JOIN users u ON p.author_id = u.id
    WHERE p.deleted_at IS NULL
    ORDER BY p.created_at DESC
    LIMIT 5
");
$recentPosts = $recentPostsStmt->fetchAll();

// =====================
// TOP CATEGORIES
// =====================
$topCategoriesStmt = $db->query("
    SELECT c.name, COUNT(p.id) as post_count
    FROM categories c
    LEFT JOIN posts p ON c.id = p.category_id AND p.deleted_at IS NULL
    GROUP BY c.id, c.name
    ORDER BY post_count DESC
    LIMIT 5
");
$topCategories = $topCategoriesStmt->fetchAll();

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
        include __DIR__ . '/templates/laporan_overview_pdf.php';
        $html = ob_get_clean();
        
        // Write HTML to PDF
        $mpdf->WriteHTML($html);
        
        // Output PDF
        $mpdf->Output('Laporan_System_Overview_' . date('Ymd_His') . '.pdf', 'I');
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
                        <li class="breadcrumb-item active">System Overview</li>
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
        
        <!-- POSTS STATISTICS -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-newspaper"></i> Statistik Posts</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2">
                        <div class="text-center">
                            <h6 class="text-muted mb-2">Total</h6>
                            <h3 class="mb-0"><?= formatNumber($postsStats['total']) ?></h3>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-center">
                            <h6 class="text-muted mb-2">Published</h6>
                            <h3 class="mb-0 text-success"><?= formatNumber($postsStats['published']) ?></h3>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-center">
                            <h6 class="text-muted mb-2">Draft</h6>
                            <h3 class="mb-0 text-secondary"><?= formatNumber($postsStats['draft']) ?></h3>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-center">
                            <h6 class="text-muted mb-2">Archived</h6>
                            <h3 class="mb-0 text-warning"><?= formatNumber($postsStats['archived']) ?></h3>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-center">
                            <h6 class="text-muted mb-2">Featured</h6>
                            <h3 class="mb-0 text-primary"><?= formatNumber($postsStats['featured']) ?></h3>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-center">
                            <h6 class="text-muted mb-2">Total Views</h6>
                            <h3 class="mb-0 text-info"><?= formatNumber($postsStats['total_views']) ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- SERVICES & USERS -->
        <div class="row mb-3">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="bi bi-gear"></i> Statistik Layanan</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-3 text-center">
                                <h6 class="text-muted mb-2">Total</h6>
                                <h4><?= formatNumber($servicesStats['total']) ?></h4>
                            </div>
                            <div class="col-3 text-center">
                                <h6 class="text-muted mb-2">Published</h6>
                                <h4 class="text-success"><?= formatNumber($servicesStats['published']) ?></h4>
                            </div>
                            <div class="col-3 text-center">
                                <h6 class="text-muted mb-2">Draft</h6>
                                <h4 class="text-secondary"><?= formatNumber($servicesStats['draft']) ?></h4>
                            </div>
                            <div class="col-3 text-center">
                                <h6 class="text-muted mb-2">Archived</h6>
                                <h4 class="text-warning"><?= formatNumber($servicesStats['archived']) ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="bi bi-people"></i> Statistik Users</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col text-center">
                                <h6 class="text-muted mb-2">Total</h6>
                                <h4><?= formatNumber($usersStats['total']) ?></h4>
                            </div>
                            <div class="col text-center">
                                <h6 class="text-muted mb-2">S. Admin</h6>
                                <h4 class="text-danger"><?= formatNumber($usersStats['super_admin']) ?></h4>
                            </div>
                            <div class="col text-center">
                                <h6 class="text-muted mb-2">Admin</h6>
                                <h4 class="text-warning"><?= formatNumber($usersStats['admin']) ?></h4>
                            </div>
                            <div class="col text-center">
                                <h6 class="text-muted mb-2">Editor</h6>
                                <h4 class="text-info"><?= formatNumber($usersStats['editor']) ?></h4>
                            </div>
                            <div class="col text-center">
                                <h6 class="text-muted mb-2">Author</h6>
                                <h4 class="text-success"><?= formatNumber($usersStats['author']) ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- CATEGORIES, TAGS & ACTIVITIES -->
        <div class="row mb-3">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="bi bi-tag"></i> Kategori & Tags</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6 text-center">
                                <h6 class="text-muted mb-2">Kategori</h6>
                                <h3><?= formatNumber($totalCategories) ?></h3>
                            </div>
                            <div class="col-6 text-center">
                                <h6 class="text-muted mb-2">Tags</h6>
                                <h3><?= formatNumber($totalTags) ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="bi bi-clock-history"></i> Statistik Aktivitas</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col text-center">
                                <h6 class="text-muted mb-2">Total</h6>
                                <h4><?= formatNumber($activitiesStats['total']) ?></h4>
                            </div>
                            <div class="col text-center">
                                <h6 class="text-muted mb-2">Create</h6>
                                <h4 class="text-success"><?= formatNumber($activitiesStats['creates']) ?></h4>
                            </div>
                            <div class="col text-center">
                                <h6 class="text-muted mb-2">Update</h6>
                                <h4 class="text-info"><?= formatNumber($activitiesStats['updates']) ?></h4>
                            </div>
                            <div class="col text-center">
                                <h6 class="text-muted mb-2">Delete</h6>
                                <h4 class="text-danger"><?= formatNumber($activitiesStats['deletes']) ?></h4>
                            </div>
                            <div class="col text-center">
                                <h6 class="text-muted mb-2">Login</h6>
                                <h4 class="text-primary"><?= formatNumber($activitiesStats['logins']) ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- RECENT POSTS & TOP CATEGORIES -->
        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">5 Posts Terbaru</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <tbody>
                                    <?php foreach ($recentPosts as $post): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars(truncateText($post['title'], 40)) ?></strong><br>
                                                <small class="text-muted">by <?= htmlspecialchars($post['author_name']) ?> | <?= formatTanggal($post['created_at'], 'd M Y') ?></small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Top 5 Kategori</h5>
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
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>
