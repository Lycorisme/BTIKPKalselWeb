<?php
/**
 * Report: Services
 * Services report with statistics and PDF export (No view_count, no is_featured)
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

// Import mPDF
require_once '../../../vendor/autoload.php';

$pageTitle = 'Laporan Layanan';

$db = Database::getInstance()->getConnection();

// Get filters
$status = $_GET['status'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$exportPdf = $_GET['export_pdf'] ?? '';

// Build query
$sql = "SELECT 
            s.*,
            u.name as author_name
        FROM services s
        LEFT JOIN users u ON s.author_id = u.id
        WHERE s.deleted_at IS NULL";

$params = [];

if ($status) {
    $sql .= " AND s.status = ?";
    $params[] = $status;
}

if ($dateFrom) {
    $sql .= " AND DATE(s.created_at) >= ?";
    $params[] = $dateFrom;
}

if ($dateTo) {
    $sql .= " AND DATE(s.created_at) <= ?";
    $params[] = $dateTo;
}

$sql .= " ORDER BY s.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$services = $stmt->fetchAll();

// Statistics
$stats = [
    'total' => count($services),
    'published' => 0,
    'draft' => 0,
    'archived' => 0
];

foreach ($services as $service) {
    if (isset($stats[$service['status']])) {
        $stats[$service['status']]++;
    }
}

// Get services by author
$authorStatsStmt = $db->query("
    SELECT 
        u.name,
        COUNT(s.id) as total
    FROM users u
    LEFT JOIN services s ON u.id = s.author_id AND s.deleted_at IS NULL
    WHERE u.deleted_at IS NULL
    GROUP BY u.id, u.name
    HAVING total > 0
    ORDER BY total DESC
    LIMIT 10
");
$authorStats = $authorStatsStmt->fetchAll();

// Get recent services
$recentStmt = $db->query("
    SELECT title, created_at
    FROM services
    WHERE deleted_at IS NULL
    ORDER BY created_at DESC
    LIMIT 10
");
$recentServices = $recentStmt->fetchAll();

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
        include __DIR__ . '/templates/laporan_services_pdf.php';
        $html = ob_get_clean();
        
        // Write HTML to PDF
        $mpdf->WriteHTML($html);
        
        // Output PDF
        $mpdf->Output('Laporan_Layanan_' . date('Ymd_His') . '.pdf', 'I');
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
                        <li class="breadcrumb-item active">Layanan</li>
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
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">Semua Status</option>
                                <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
                                <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                                <option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>Archived</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Dari Tanggal</label>
                            <input type="date" name="date_from" class="form-control" value="<?= $dateFrom ?>">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Sampai Tanggal</label>
                            <input type="date" name="date_to" class="form-control" value="<?= $dateTo ?>">
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Tampilkan
                            </button>
                            <a href="report_services.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset
                            </a>
                            <a href="?export_pdf=1<?= $status ? '&status='.$status : '' ?><?= $dateFrom ? '&date_from='.$dateFrom : '' ?><?= $dateTo ? '&date_to='.$dateTo : '' ?>" 
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
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Total Layanan</h6>
                        <h3 class="mb-0"><?= formatNumber($stats['total']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Published</h6>
                        <h3 class="mb-0 text-success"><?= formatNumber($stats['published']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Draft</h6>
                        <h3 class="mb-0 text-secondary"><?= formatNumber($stats['draft']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Archived</h6>
                        <h3 class="mb-0 text-warning"><?= formatNumber($stats['archived']) ?></h3>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Services Table -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Daftar Layanan</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Judul</th>
                                        <th>Penulis</th>
                                        <th>Status</th>
                                        <th>Tanggal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($services)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">Tidak ada data</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php $no = 1; foreach ($services as $service): ?>
                                            <tr>
                                                <td><?= $no++ ?></td>
                                                <td><?= htmlspecialchars($service['title']) ?></td>
                                                <td><?= htmlspecialchars($service['author_name']) ?></td>
                                                <td><?= getStatusBadge($service['status']) ?></td>
                                                <td><?= formatTanggal($service['created_at'], 'd M Y') ?></td>
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
                <!-- Top Authors -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Top Penulis</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <tbody>
                                    <?php if (empty($authorStats)): ?>
                                        <tr>
                                            <td colspan="2" class="text-center text-muted">Tidak ada data</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($authorStats as $author): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($author['name']) ?></td>
                                                <td class="text-end"><strong><?= formatNumber($author['total']) ?></strong></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Services -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Layanan Terbaru</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <tbody>
                                    <?php if (empty($recentServices)): ?>
                                        <tr>
                                            <td colspan="2" class="text-center text-muted">Tidak ada data</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recentServices as $recent): ?>
                                            <tr>
                                                <td><?= htmlspecialchars(truncateText($recent['title'], 35)) ?></td>
                                                <td class="text-end text-muted" style="font-size: 0.85rem;">
                                                    <?= formatTanggal($recent['created_at'], 'd M Y') ?>
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
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>
