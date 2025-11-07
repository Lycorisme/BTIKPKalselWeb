<?php
/**
 * Report: File Download
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../vendor/autoload.php'; // mPDF

$pageTitle = 'Laporan File Download';
$db = Database::getInstance()->getConnection();

// Get filters
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$exportPdf = $_GET['export_pdf'] ?? '';

// Build query
$sql = "SELECT * FROM downloadable_files WHERE deleted_at IS NULL";
$params = [];

if ($dateFrom) {
    $sql .= " AND DATE(created_at) >= ?";
    $params[] = $dateFrom;
}

if ($dateTo) {
    $sql .= " AND DATE(created_at) <= ?";
    $params[] = $dateTo;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$files = $stmt->fetchAll();

$totalFiles = count($files);

// Export PDF
if ($exportPdf === '1') {
    $siteName = getSetting('site_name', 'BTIKP Kalimantan Selatan');
    $contactPhone = getSetting('contact_phone', '');
    $contactEmail = getSetting('contact_email', '');
    $contactAddress = getSetting('contact_address', '');
    $siteLogo = getSetting('site_logo', '');

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
    $mpdf->SetDefaultFont('cambria');

    $footer = '
        <table width="100%" style="border-top: 1px solid #000; padding-top: 5px; font-size: 9pt;">
            <tr>
                <td width="70%" style="text-align: left;">' . htmlspecialchars($siteName) . '</td>
                <td width="30%" style="text-align: right;">Halaman {PAGENO} dari {nbpg}</td>
            </tr>
        </table>';
    $mpdf->SetHTMLFooter($footer);

    ob_start();
    include __DIR__ . '/templates/laporan_files_pdf.php';
    $html = ob_get_clean();

    $mpdf->WriteHTML($html);
    $mpdf->Output('Laporan_FileDownload_' . date('Ymd_His') . '.pdf', 'I');
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
                        <li class="breadcrumb-item">Laporan</li>
                        <li class="breadcrumb-item active">File Download</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <!-- Filter Card -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-funnel"></i> Filter Laporan</h5>
            </div>
            <div class="card-body">
                <form method="GET">
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-label">Dari Tanggal</label>
                            <input type="date" name="date_from" class="form-control" value="<?= $dateFrom ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Sampai Tanggal</label>
                            <input type="date" name="date_to" class="form-control" value="<?= $dateTo ?>">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="bi bi-search"></i> Tampilkan
                            </button>
                            <a href="report_files.php" class="btn btn-secondary me-2">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset
                            </a>
                            <a href="?export_pdf=1<?= $dateFrom ? '&date_from='.$dateFrom : '' ?><?= $dateTo ? '&date_to='.$dateTo : '' ?>" class="btn btn-danger" target="_blank">
                                <i class="bi bi-file-pdf"></i> Export PDF
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Files Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Daftar File Download</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Judul File</th>
                                <th>Deskripsi</th>
                                <th>Tanggal Upload</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($files)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Tidak ada data</td>
                                </tr>
                            <?php else: ?>
                                <?php $no=1; foreach ($files as $file): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($file['title'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars(truncateText($file['description'] ?? '', 50)) ?></td>
                                        <td><?= formatTanggal($file['created_at'] ?? '', 'd/m/Y') ?></td>
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
