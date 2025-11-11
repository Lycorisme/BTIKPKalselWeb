<?php
/**
 * Overview Report - Summary of All Modules
 * FIXED VERSION - Using post_categories instead of categories
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

if (!hasRole(['super_admin', 'admin'])) {
    setAlert('danger', 'Anda tidak memiliki akses ke halaman ini');
    redirect(ADMIN_URL);
}

$pageTitle = 'Laporan Overview';
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
        COALESCE(SUM(view_count), 0) as total_views
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
$servicesStmt = $db->query("SELECT COUNT(*) as total FROM services WHERE deleted_at IS NULL");
$servicesData = $servicesStmt->fetch();
$totalServices = (int)$servicesData['total'];

// =====================
// GALLERY STATISTICS
// =====================
$albumsStmt = $db->query("SELECT COUNT(*) as total FROM gallery_albums WHERE deleted_at IS NULL");
$albumsData = $albumsStmt->fetch();
$totalAlbums = (int)$albumsData['total'];

$photosStmt = $db->query("SELECT COUNT(*) as total FROM gallery_photos WHERE deleted_at IS NULL");
$photosData = $photosStmt->fetch();
$totalPhotos = (int)$photosData['total'];

// =====================
// FILES STATISTICS
// =====================
$filesStmt = $db->query("
    SELECT 
        COUNT(*) as total,
        COALESCE(SUM(download_count), 0) as total_downloads
    FROM downloadable_files 
    WHERE deleted_at IS NULL
");
$filesData = $filesStmt->fetch();
$totalFiles = (int)$filesData['total'];
$totalDownloads = (int)$filesData['total_downloads'];

// =====================
// BANNERS STATISTICS
// =====================
$bannersStmt = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active
    FROM banners
");
$bannersData = $bannersStmt->fetch();
$totalBanners = (int)$bannersData['total'];
$activeBanners = (int)$bannersData['active'];

// =====================
// CATEGORIES & TAGS - FIXED: Changed 'categories' to 'post_categories'
// =====================
$categoriesStmt = $db->query("SELECT COUNT(*) as total FROM post_categories");
$categoriesData = $categoriesStmt->fetch();
$totalCategories = (int)$categoriesData['total'];

$tagsStmt = $db->query("SELECT COUNT(*) as total FROM tags");
$tagsData = $tagsStmt->fetch();
$totalTags = (int)$tagsData['total'];

// =====================
// USERS STATISTICS
// =====================
$usersStmt = $db->query("SELECT COUNT(*) as total FROM users WHERE deleted_at IS NULL");
$usersData = $usersStmt->fetch();
$totalUsers = (int)$usersData['total'];

// =====================
// CONTACT MESSAGES
// =====================
$messagesStmt = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'unread' THEN 1 ELSE 0 END) as unread
    FROM contact_messages
");
$messagesData = $messagesStmt->fetch();
$totalMessages = (int)$messagesData['total'];
$unreadMessages = (int)$messagesData['unread'];

// =====================
// TOP CATEGORIES - FIXED: Changed 'categories' to 'post_categories'
// =====================
$topCategoriesStmt = $db->query("
    SELECT c.name, COUNT(p.id) as post_count
    FROM post_categories c
    LEFT JOIN posts p ON c.id = p.category_id AND p.deleted_at IS NULL
    GROUP BY c.id, c.name
    ORDER BY post_count DESC
    LIMIT 5
");
$topCategories = $topCategoriesStmt->fetchAll();

// =====================
// RECENT POSTS
// =====================
$recentPostsStmt = $db->query("
    SELECT p.title, p.status, p.view_count, p.created_at, u.name as author_name
    FROM posts p
    LEFT JOIN users u ON p.author_id = u.id
    WHERE p.deleted_at IS NULL
    ORDER BY p.created_at DESC
    LIMIT 5
");
$recentPosts = $recentPostsStmt->fetchAll();

// =====================
// MOST VIEWED POSTS
// =====================
$popularPostsStmt = $db->query("
    SELECT title, view_count, created_at
    FROM posts
    WHERE deleted_at IS NULL AND status = 'published'
    ORDER BY view_count DESC
    LIMIT 5
");
$popularPosts = $popularPostsStmt->fetchAll();

// =====================
// RECENT ACTIVITIES
// =====================
$activitiesStmt = $db->query("
    SELECT a.*, u.name as user_name
    FROM activity_logs a
    LEFT JOIN users u ON a.user_id = u.id
    ORDER BY a.created_at DESC
    LIMIT 10
");
$recentActivities = $activitiesStmt->fetchAll();

// Export PDF
if ($exportPdf) {
    require_once '../../../vendor/tecnickcom/tcpdf/tcpdf.php';
    
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
    $pdf->SetCreator('BTIKP Kalsel');
    $pdf->SetAuthor('Admin BTIKP');
    $pdf->SetTitle('Laporan Overview');
    
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->AddPage();
    
    $pdf->SetFont('helvetica', 'B', 18);
    $pdf->Cell(0, 10, 'LAPORAN OVERVIEW', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, 'Portal BTIKP Kalimantan Selatan', 0, 1, 'C');
    $pdf->Cell(0, 5, 'Tanggal: ' . date('d/m/Y H:i'), 0, 1, 'C');
    $pdf->Ln(8);
    
    // Summary Statistics
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 7, 'Ringkasan Statistik', 0, 1);
    $pdf->Ln(2);
    
    $pdf->SetFont('helvetica', '', 10);
    $html = '<table border="1" cellpadding="5">
        <tr style="background-color:#f0f0f0;">
            <th width="50%"><b>Module</b></th>
            <th width="50%" align="center"><b>Total</b></th>
        </tr>
        <tr>
            <td>Total Posts</td>
            <td align="center">' . number_format($postsStats['total']) . '</td>
        </tr>
        <tr>
            <td>Published Posts</td>
            <td align="center">' . number_format($postsStats['published']) . '</td>
        </tr>
        <tr>
            <td>Total Views</td>
            <td align="center">' . number_format($postsStats['total_views']) . '</td>
        </tr>
        <tr>
            <td>Categories</td>
            <td align="center">' . number_format($totalCategories) . '</td>
        </tr>
        <tr>
            <td>Tags</td>
            <td align="center">' . number_format($totalTags) . '</td>
        </tr>
        <tr>
            <td>Services</td>
            <td align="center">' . number_format($totalServices) . '</td>
        </tr>
        <tr>
            <td>Gallery Albums</td>
            <td align="center">' . number_format($totalAlbums) . '</td>
        </tr>
        <tr>
            <td>Gallery Photos</td>
            <td align="center">' . number_format($totalPhotos) . '</td>
        </tr>
        <tr>
            <td>Files</td>
            <td align="center">' . number_format($totalFiles) . '</td>
        </tr>
        <tr>
            <td>Total Downloads</td>
            <td align="center">' . number_format($totalDownloads) . '</td>
        </tr>
        <tr>
            <td>Banners</td>
            <td align="center">' . number_format($totalBanners) . '</td>
        </tr>
        <tr>
            <td>Users</td>
            <td align="center">' . number_format($totalUsers) . '</td>
        </tr>
        <tr>
            <td>Contact Messages</td>
            <td align="center">' . number_format($totalMessages) . '</td>
        </tr>
    </table>';
    
    $pdf->writeHTML($html, true, false, true, false, '');
    
    $pdf->Output('laporan_overview_' . date('YmdHis') . '.pdf', 'D');
    exit;
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
                        <li class="breadcrumb-item active">Laporan Overview</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <!-- Action Buttons -->
        <div class="mb-3">
            <a href="?export_pdf=1" class="btn btn-danger">
                <i class="bi bi-file-pdf"></i> Export PDF
            </a>
        </div>

        <!-- Main Statistics Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Total Posts</h6>
                                <h3 class="mb-0"><?= number_format($postsStats['total']) ?></h3>
                                <small class="text-success"><?= $postsStats['published'] ?> published</small>
                            </div>
                            <div class="text-primary">
                                <i class="bi bi-newspaper" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Total Views</h6>
                                <h3 class="mb-0"><?= number_format($postsStats['total_views']) ?></h3>
                                <small class="text-muted">All posts</small>
                            </div>
                            <div class="text-info">
                                <i class="bi bi-eye" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Services</h6>
                                <h3 class="mb-0"><?= number_format($totalServices) ?></h3>
                                <small class="text-muted">Active services</small>
                            </div>
                            <div class="text-success">
                                <i class="bi bi-gear-fill" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Users</h6>
                                <h3 class="mb-0"><?= number_format($totalUsers) ?></h3>
                                <small class="text-muted">Registered</small>
                            </div>
                            <div class="text-warning">
                                <i class="bi bi-people-fill" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Statistics -->
        <div class="row">
            <div class="col-md-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Categories</h6>
                        <h4 class="mb-0"><?= number_format($totalCategories) ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Tags</h6>
                        <h4 class="mb-0"><?= number_format($totalTags) ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Albums</h6>
                        <h4 class="mb-0"><?= number_format($totalAlbums) ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Photos</h6>
                        <h4 class="mb-0"><?= number_format($totalPhotos) ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Files</h6>
                        <h4 class="mb-0"><?= number_format($totalFiles) ?></h4>
                        <small><?= number_format($totalDownloads) ?> DL</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Messages</h6>
                        <h4 class="mb-0"><?= number_format($totalMessages) ?></h4>
                        <small><?= $unreadMessages ?> unread</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Tables -->
        <div class="row">
            <!-- Top Categories -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Top 5 Kategori</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Kategori</th>
                                    <th class="text-end">Posts</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($topCategories)): ?>
                                    <tr><td colspan="2" class="text-center">Tidak ada data</td></tr>
                                <?php else: ?>
                                    <?php foreach ($topCategories as $cat): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($cat['name']) ?></td>
                                            <td class="text-end">
                                                <span class="badge bg-primary"><?= $cat['post_count'] ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Most Viewed Posts -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Post Terpopuler</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Judul</th>
                                    <th class="text-end">Views</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($popularPosts)): ?>
                                    <tr><td colspan="2" class="text-center">Tidak ada data</td></tr>
                                <?php else: ?>
                                    <?php foreach ($popularPosts as $post): ?>
                                        <tr>
                                            <td><?= htmlspecialchars(substr($post['title'], 0, 40)) ?><?= strlen($post['title']) > 40 ? '...' : '' ?></td>
                                            <td class="text-end">
                                                <span class="badge bg-info"><?= number_format($post['view_count']) ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Posts -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Post Terbaru</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Judul</th>
                                <th>Penulis</th>
                                <th>Status</th>
                                <th>Views</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentPosts)): ?>
                                <tr><td colspan="5" class="text-center">Tidak ada data</td></tr>
                            <?php else: ?>
                                <?php foreach ($recentPosts as $post): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($post['title']) ?></td>
                                        <td><?= htmlspecialchars($post['author_name'] ?? '-') ?></td>
                                        <td>
                                            <?php if ($post['status'] === 'published'): ?>
                                                <span class="badge bg-success">Published</span>
                                            <?php elseif ($post['status'] === 'draft'): ?>
                                                <span class="badge bg-warning">Draft</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Archived</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= number_format($post['view_count']) ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($post['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Aktivitas Terbaru</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Aksi</th>
                                <th>Deskripsi</th>
                                <th>Waktu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentActivities)): ?>
                                <tr><td colspan="4" class="text-center">Tidak ada data</td></tr>
                            <?php else: ?>
                                <?php foreach ($recentActivities as $activity): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($activity['user_name'] ?? 'System') ?></td>
                                        <td>
                                            <?php
                                            $actionBadge = [
                                                'CREATE' => 'success',
                                                'UPDATE' => 'info',
                                                'DELETE' => 'danger',
                                                'LOGIN' => 'primary'
                                            ];
                                            $badgeClass = $actionBadge[$activity['action']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $badgeClass ?>"><?= $activity['action'] ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($activity['description']) ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($activity['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>
