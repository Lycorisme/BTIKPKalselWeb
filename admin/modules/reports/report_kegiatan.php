<?php
/**
 * Report: Kegiatan (Gallery Albums)
 * Laporan kegiatan beserta jumlah dokumentasi & export PDF
 */
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../vendor/autoload.php';

$pageTitle = 'Laporan Kegiatan';
$db = Database::getInstance()->getConnection();

$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$exportPdf = $_GET['export_pdf'] ?? '';

$sql = "
    SELECT 
        ga.id,
        ga.name AS title,
        ga.slug,
        ga.created_at as date_event,
        COUNT(gp.id) AS total_photos
    FROM gallery_albums ga
    LEFT JOIN gallery_photos gp 
        ON gp.album_id = ga.id AND gp.deleted_at IS NULL
    WHERE ga.deleted_at IS NULL
";
$params = [];
if ($dateFrom) {
    $sql .= " AND DATE(ga.created_at) >= ?";
    $params[] = $dateFrom;
}
if ($dateTo) {
    $sql .= " AND DATE(ga.created_at) <= ?";
    $params[] = $dateTo;
}
$sql .= "
    GROUP BY ga.id, ga.name, ga.slug, ga.created_at
    ORDER BY ga.created_at DESC
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$kegiatan = $stmt->fetchAll();

$totalKegiatan = count($kegiatan);
$totalFoto = 0;
foreach ($kegiatan as $item) $totalFoto += $item['total_photos'];

// PDF export
if ($exportPdf === '1') {
    $siteName = getSetting('site_name', 'BTIKP Kalimantan Selatan');
    $siteTagline = getSetting('site_tagline', '');
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
                <td width="70%" style="text-align: left;">
                    ' . htmlspecialchars($siteName) . '
                </td>
                <td width="30%" style="text-align: right;">
                    Halaman {PAGENO} dari {nbpg}
                </td>
            </tr>
        </table>';
    $mpdf->SetHTMLFooter($footer);

    ob_start();
    include __DIR__ . '/templates/laporan_kegiatan.php';
    $html = ob_get_clean();

    $mpdf->WriteHTML($html);
    $mpdf->Output('Laporan_Kegiatan_' . date('Ymd_His') . '.pdf', 'I');
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
                        <li class="breadcrumb-item active">Kegiatan</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-funnel"></i> Filter Laporan</h5>
            </div>
            <div class="card-body">
                <form method="GET">
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label">Dari Tanggal</label>
                            <input type="date" name="date_from" class="form-control" value="<?= $dateFrom ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Sampai Tanggal</label>
                            <input type="date" name="date_to" class="form-control" value="<?= $dateTo ?>">
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="bi bi-search"></i> Tampilkan
                            </button>
                            <a href="report_kegiatan.php" class="btn btn-secondary me-2">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset
                            </a>
                            <a href="?export_pdf=1<?= $dateFrom ? '&date_from='.$dateFrom : '' ?><?= $dateTo ? '&date_to='.$dateTo : '' ?>"
                               class="btn btn-danger" target="_blank">
                                <i class="bi bi-file-pdf"></i> Export PDF
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Total Kegiatan</h6>
                        <h3 class="mb-0"><?= formatNumber($totalKegiatan) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Total Dokumentasi</h6>
                        <h3 class="mb-0 text-danger"><?= formatNumber($totalFoto) ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel kegiatan -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Daftar Kegiatan</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Kegiatan</th>
                            <th>Slug</th>
                            <th>Tanggal Kegiatan</th>
                            <th>Total Dokumentasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($kegiatan)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">Tidak ada data kegiatan</td>
                            </tr>
                        <?php else: ?>
                            <?php $no=1; foreach($kegiatan as $item): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($item['title']) ?></td>
                                    <td><?= htmlspecialchars($item['slug']) ?></td>
                                    <td><?= formatTanggal($item['date_event'], 'd/m/Y') ?></td>
                                    <td><span class="badge bg-info"><?= $item['total_photos'] ?></span></td>
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
