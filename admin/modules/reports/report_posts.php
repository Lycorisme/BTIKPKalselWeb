<?php
/**
 * Posts Report
 * FIXED VERSION - Using post_categories instead of categories
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

if (!hasRole(['super_admin', 'admin', 'editor'])) {
    setAlert('danger', 'Anda tidak memiliki akses ke halaman ini');
    redirect(ADMIN_URL);
}

$pageTitle = 'Laporan Post';
$db = Database::getInstance()->getConnection();

// Get filters
$categoryId = $_GET['category_id'] ?? '';
$status = $_GET['status'] ?? '';
$authorId = $_GET['author_id'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$exportPdf = $_GET['export_pdf'] ?? '';

// Build query - FIXED: Changed 'categories' to 'post_categories'
$sql = "SELECT p.*, 
        c.name as category_name, 
        u.name as author_name,
        (SELECT COUNT(*) FROM post_tags pt WHERE pt.post_id = p.id) as tag_count
    FROM posts p
    LEFT JOIN post_categories c ON p.category_id = c.id
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

// Get statistics
$stats = [
    'total' => count($posts),
    'published' => 0,
    'draft' => 0,
    'archived' => 0,
    'featured' => 0,
    'total_views' => 0
];

foreach ($posts as $post) {
    if ($post['status'] === 'published') $stats['published']++;
    if ($post['status'] === 'draft') $stats['draft']++;
    if ($post['status'] === 'archived') $stats['archived']++;
    if ($post['is_featured']) $stats['featured']++;
    $stats['total_views'] += $post['view_count'];
}

// Get category statistics - FIXED: Changed 'categories' to 'post_categories'
$categoryStatsStmt = $db->query("
    SELECT c.name, COUNT(p.id) as total
    FROM post_categories c
    LEFT JOIN posts p ON c.id = p.category_id AND p.deleted_at IS NULL
    GROUP BY c.id, c.name
    ORDER BY total DESC
");
$categoryStats = $categoryStatsStmt->fetchAll();

// Get author statistics
$authorStatsStmt = $db->query("
    SELECT u.name, COUNT(p.id) as total
    FROM users u
    LEFT JOIN posts p ON u.id = p.author_id AND p.deleted_at IS NULL
    GROUP BY u.id, u.name
    ORDER BY total DESC
");
$authorStats = $authorStatsStmt->fetchAll();

// Get data for filters - FIXED: Changed 'categories' to 'post_categories'
$categoriesStmt = $db->query("SELECT * FROM post_categories ORDER BY name");
$categories = $categoriesStmt->fetchAll();

$authorsStmt = $db->query("SELECT id, name FROM users ORDER BY name");
$authors = $authorsStmt->fetchAll();

// Export PDF
if ($exportPdf) {
    require_once '../../../vendor/tecnickcom/tcpdf/tcpdf.php';
    
    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8');
    $pdf->SetCreator('BTIKP Kalsel');
    $pdf->SetAuthor('Admin BTIKP');
    $pdf->SetTitle('Laporan Post');
    
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->AddPage();
    
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'LAPORAN POST', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, 'Tanggal: ' . date('d/m/Y H:i'), 0, 1, 'C');
    $pdf->Ln(5);
    
    // Statistics
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 7, 'Statistik Post', 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    
    $html = '<table border="1" cellpadding="4">
        <tr style="background-color:#f0f0f0;">
            <th>Total Post</th>
            <th>Published</th>
            <th>Draft</th>
            <th>Archived</th>
            <th>Featured</th>
            <th>Total Views</th>
        </tr>
        <tr>
            <td align="center">' . $stats['total'] . '</td>
            <td align="center">' . $stats['published'] . '</td>
            <td align="center">' . $stats['draft'] . '</td>
            <td align="center">' . $stats['archived'] . '</td>
            <td align="center">' . $stats['featured'] . '</td>
            <td align="center">' . number_format($stats['total_views']) . '</td>
        </tr>
    </table>';
    
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Ln(5);
    
    // Posts table
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 7, 'Daftar Post', 0, 1);
    $pdf->SetFont('helvetica', '', 8);
    
    $html = '<table border="1" cellpadding="3">
        <thead>
            <tr style="background-color:#f0f0f0;">
                <th width="5%">No</th>
                <th width="30%">Judul</th>
                <th width="15%">Kategori</th>
                <th width="15%">Penulis</th>
                <th width="10%">Status</th>
                <th width="10%">Views</th>
                <th width="15%">Tanggal</th>
            </tr>
        </thead>
        <tbody>';
    
    $no = 1;
    foreach ($posts as $post) {
        $statusLabel = [
            'published' => 'Published',
            'draft' => 'Draft',
            'archived' => 'Archived'
        ];
        
        $html .= '<tr>
            <td align="center">' . $no++ . '</td>
            <td>' . htmlspecialchars($post['title']) . '</td>
            <td>' . htmlspecialchars($post['category_name'] ?? '-') . '</td>
            <td>' . htmlspecialchars($post['author_name'] ?? '-') . '</td>
            <td>' . $statusLabel[$post['status']] . '</td>
            <td align="center">' . number_format($post['view_count']) . '</td>
            <td>' . date('d/m/Y', strtotime($post['created_at'])) . '</td>
        </tr>';
    }
    
    $html .= '</tbody></table>';
    $pdf->writeHTML($html, true, false, true, false, '');
    
    $pdf->Output('laporan_posts_' . date('YmdHis') . '.pdf', 'D');
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
                        <li class="breadcrumb-item active">Laporan Post</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <!-- Filter Card -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Filter Laporan</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
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
                            <option value="published" <?= $status == 'published' ? 'selected' : '' ?>>Published</option>
                            <option value="draft" <?= $status == 'draft' ? 'selected' : '' ?>>Draft</option>
                            <option value="archived" <?= $status == 'archived' ? 'selected' : '' ?>>Archived</option>
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
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Filter
                        </button>
                        <a href="report_posts.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Reset
                        </a>
                        <a href="?export_pdf=1<?= $categoryId ? '&category_id=' . $categoryId : '' ?><?= $status ? '&status=' . $status : '' ?><?= $authorId ? '&author_id=' . $authorId : '' ?><?= $dateFrom ? '&date_from=' . $dateFrom : '' ?><?= $dateTo ? '&date_to=' . $dateTo : '' ?>" 
                           class="btn btn-danger">
                            <i class="bi bi-file-pdf"></i> Export PDF
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-md-2">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Total Post</h6>
                        <h3 class="mb-0"><?= number_format($stats['total']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Published</h6>
                        <h3 class="mb-0 text-success"><?= number_format($stats['published']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Draft</h6>
                        <h3 class="mb-0 text-warning"><?= number_format($stats['draft']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Archived</h6>
                        <h3 class="mb-0 text-secondary"><?= number_format($stats['archived']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Featured</h6>
                        <h3 class="mb-0 text-primary"><?= number_format($stats['featured']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Total Views</h6>
                        <h3 class="mb-0"><?= number_format($stats['total_views']) ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Posts Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Daftar Post</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Judul</th>
                                <th>Kategori</th>
                                <th>Penulis</th>
                                <th>Status</th>
                                <th>Featured</th>
                                <th>Views</th>
                                <th>Tags</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($posts)): ?>
                                <tr>
                                    <td colspan="9" class="text-center">Tidak ada data</td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1; foreach ($posts as $post): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($post['title']) ?></strong>
                                        </td>
                                        <td><?= htmlspecialchars($post['category_name'] ?? '-') ?></td>
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
                                        <td>
                                            <?php if ($post['is_featured']): ?>
                                                <i class="bi bi-star-fill text-warning"></i>
                                            <?php else: ?>
                                                <i class="bi bi-star text-muted"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= number_format($post['view_count']) ?></td>
                                        <td><?= $post['tag_count'] ?> tags</td>
                                        <td><?= date('d/m/Y', strtotime($post['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Category & Author Stats -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Post per Kategori</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Kategori</th>
                                    <th class="text-end">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categoryStats as $cat): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($cat['name']) ?></td>
                                        <td class="text-end"><strong><?= $cat['total'] ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Post per Penulis</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Penulis</th>
                                    <th class="text-end">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($authorStats as $author): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($author['name']) ?></td>
                                        <td class="text-end"><strong><?= $author['total'] ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>
