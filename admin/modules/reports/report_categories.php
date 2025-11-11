<?php
/**
 * Categories & Tags Report
 * FIXED VERSION - Using post_categories instead of categories
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

if (!hasRole(['super_admin', 'admin', 'editor'])) {
    setAlert('danger', 'Anda tidak memiliki akses ke halaman ini');
    redirect(ADMIN_URL);
}

$pageTitle = 'Laporan Kategori & Tag';
$db = Database::getInstance()->getConnection();

// Get export flag
$exportPdf = $_GET['export_pdf'] ?? '';

// Get all categories with post count - FIXED: Changed 'categories' to 'post_categories'
$categoriesStmt = $db->query("
    SELECT c.*, COUNT(p.id) as post_count
    FROM post_categories c
    LEFT JOIN posts p ON c.id = p.category_id AND p.deleted_at IS NULL
    GROUP BY c.id
    ORDER BY post_count DESC, c.name ASC
");
$categories = $categoriesStmt->fetchAll();

// Get all tags with post count
$tagsStmt = $db->query("
    SELECT t.*, COUNT(pt.post_id) as post_count
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

// Export PDF
if ($exportPdf) {
    require_once '../../../vendor/tecnickcom/tcpdf/tcpdf.php';
    
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
    $pdf->SetCreator('BTIKP Kalsel');
    $pdf->SetAuthor('Admin BTIKP');
    $pdf->SetTitle('Laporan Kategori & Tag');
    
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->AddPage();
    
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'LAPORAN KATEGORI & TAG', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, 'Tanggal: ' . date('d/m/Y H:i'), 0, 1, 'C');
    $pdf->Ln(5);
    
    // Statistics
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 7, 'Statistik', 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    
    $html = '<table border="1" cellpadding="4">
        <tr style="background-color:#f0f0f0;">
            <th>Total Kategori</th>
            <th>Kategori Aktif</th>
            <th>Total Tag</th>
            <th>Tag Aktif</th>
            <th>Total Post</th>
        </tr>
        <tr>
            <td align="center">' . $stats['total_categories'] . '</td>
            <td align="center">' . $stats['categories_with_posts'] . '</td>
            <td align="center">' . $stats['total_tags'] . '</td>
            <td align="center">' . $stats['tags_with_posts'] . '</td>
            <td align="center">' . $stats['total_posts'] . '</td>
        </tr>
    </table>';
    
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Ln(5);
    
    // Categories table
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 7, 'Daftar Kategori', 0, 1);
    $pdf->SetFont('helvetica', '', 9);
    
    $html = '<table border="1" cellpadding="3">
        <thead>
            <tr style="background-color:#f0f0f0;">
                <th width="10%">No</th>
                <th width="50%">Nama Kategori</th>
                <th width="20%">Slug</th>
                <th width="20%">Jumlah Post</th>
            </tr>
        </thead>
        <tbody>';
    
    $no = 1;
    foreach ($categories as $cat) {
        $html .= '<tr>
            <td align="center">' . $no++ . '</td>
            <td>' . htmlspecialchars($cat['name']) . '</td>
            <td>' . htmlspecialchars($cat['slug']) . '</td>
            <td align="center">' . $cat['post_count'] . '</td>
        </tr>';
    }
    
    $html .= '</tbody></table>';
    $pdf->writeHTML($html, true, false, true, false, '');
    
    $pdf->Output('laporan_categories_' . date('YmdHis') . '.pdf', 'D');
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
                        <li class="breadcrumb-item active">Laporan Kategori & Tag</li>
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

        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Total Kategori</h6>
                        <h3 class="mb-0"><?= number_format($stats['total_categories']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Kategori Aktif</h6>
                        <h3 class="mb-0 text-success"><?= number_format($stats['categories_with_posts']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Total Tag</h6>
                        <h3 class="mb-0"><?= number_format($stats['total_tags']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Tag Aktif</h6>
                        <h3 class="mb-0 text-success"><?= number_format($stats['tags_with_posts']) ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Categories Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Daftar Kategori</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>Slug</th>
                                <th>Deskripsi</th>
                                <th>Status</th>
                                <th class="text-center">Jumlah Post</th>
                                <th>Dibuat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categories)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">Tidak ada data</td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1; foreach ($categories as $cat): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($cat['name']) ?></strong>
                                        </td>
                                        <td><code><?= htmlspecialchars($cat['slug']) ?></code></td>
                                        <td><?= htmlspecialchars(substr($cat['description'] ?? '', 0, 50)) ?><?= strlen($cat['description'] ?? '') > 50 ? '...' : '' ?></td>
                                        <td>
                                            <?php if ($cat['is_active']): ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Nonaktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary"><?= $cat['post_count'] ?></span>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($cat['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tags Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Daftar Tag</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>Slug</th>
                                <th class="text-center">Jumlah Post</th>
                                <th>Dibuat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($tags)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">Tidak ada data</td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1; foreach ($tags as $tag): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td>
                                            <span class="badge bg-secondary">#<?= htmlspecialchars($tag['name']) ?></span>
                                        </td>
                                        <td><code><?= htmlspecialchars($tag['slug']) ?></code></td>
                                        <td class="text-center">
                                            <span class="badge bg-primary"><?= $tag['post_count'] ?></span>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($tag['created_at'])) ?></td>
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
