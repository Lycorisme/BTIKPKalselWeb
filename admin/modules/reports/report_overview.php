<?php
/**
 * Report: Laporan Overview (Ringkasan Sistem)
 * Menampilkan status kesehatan seluruh modul dalam satu tampilan.
 * Desain: Mazer Dashboard (Konsisten dengan report_posts.php)
 * * FIX: Menghapus 'view_count' dari query tabel 'pages' untuk mencegah error SQL.
 */
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../vendor/autoload.php';

if (!hasRole(['super_admin', 'admin'])) {
    setAlert('danger', 'Anda tidak memiliki akses ke halaman ini');
    redirect(ADMIN_URL);
}

$pageTitle = 'Laporan Overview';
$db = Database::getInstance()->getConnection();
$exportPdf = $_GET['export_pdf'] ?? '';

// ==============================================================================================
// 1. DATA GATHERING (Mengumpulkan Data Statistik)
// ==============================================================================================

// --- Posts (Berita) ---
// Tabel posts biasanya memiliki kolom view_count
$postsStmt = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as pending,
        COALESCE(SUM(view_count), 0) as metric_val
    FROM posts WHERE deleted_at IS NULL
");
$postsData = $postsStmt->fetch();

// --- Pages (Halaman Statis) ---
// FIX: view_count dihapus/diset 0 karena kolom tidak ada di tabel pages
$pagesStmt = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as pending,
        0 as metric_val 
    FROM pages WHERE deleted_at IS NULL
");
$pagesData = $pagesStmt->fetch();

// --- Services (Layanan) ---
$servicesStmt = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as active,
        0 as pending,
        0 as metric_val
    FROM services WHERE deleted_at IS NULL
");
$servicesData = $servicesStmt->fetch();

// --- Gallery ---
$albumStmt = $db->query("SELECT COUNT(*) as total FROM gallery_albums WHERE deleted_at IS NULL");
$albumTotal = $albumStmt->fetchColumn();
$photoStmt = $db->query("SELECT COUNT(*) as total FROM gallery_photos WHERE deleted_at IS NULL");
$photoTotal = $photoStmt->fetchColumn();

// --- Files ---
$filesStmt = $db->query("
    SELECT 
        COUNT(*) as total,
        COALESCE(SUM(download_count), 0) as metric_val
    FROM downloadable_files WHERE deleted_at IS NULL
");
$filesData = $filesStmt->fetch();

// --- Users ---
$usersStmt = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as pending
    FROM users WHERE deleted_at IS NULL
");
$usersData = $usersStmt->fetch();

// --- Messages ---
$msgStmt = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'unread' THEN 1 ELSE 0 END) as pending
    FROM contact_messages
");
$msgData = $msgStmt->fetch();

// --- Categories & Tags ---
$catCount = $db->query("SELECT COUNT(*) FROM post_categories WHERE deleted_at IS NULL")->fetchColumn();
$tagCount = $db->query("SELECT COUNT(*) FROM tags")->fetchColumn();

// ==============================================================================================
// 2. SUMMARY CARDS CALCULATION
// ==============================================================================================
$summaryStats = [
    'total_content' => (int)$postsData['total'] + (int)$pagesData['total'] + (int)$servicesData['total'],
    'total_views'   => (int)$postsData['metric_val'], // Hanya view dari post
    'total_media'   => (int)$albumTotal + (int)$photoTotal + (int)$filesData['total'],
    'active_users'  => (int)$usersData['active']
];

// ==============================================================================================
// 3. CONSTRUCT MAIN TABLE DATA
// ==============================================================================================
$overviewData = [
    [
        'module' => 'Berita & Artikel',
        'icon' => 'bi bi-newspaper',
        'total' => (int)$postsData['total'],
        'active' => (int)$postsData['active'],
        'pending' => (int)$postsData['pending'],
        'metric_label' => 'Views',
        'metric_value' => number_format((int)$postsData['metric_val'])
    ],
    [
        'module' => 'Halaman Statis',
        'icon' => 'bi bi-file-earmark-text',
        'total' => (int)$pagesData['total'],
        'active' => (int)$pagesData['active'],
        'pending' => (int)$pagesData['pending'],
        'metric_label' => '-', // Tidak ada metric views untuk pages
        'metric_value' => '-'
    ],
    [
        'module' => 'Layanan',
        'icon' => 'bi bi-gear-fill',
        'total' => (int)$servicesData['total'],
        'active' => (int)$servicesData['active'],
        'pending' => '-',
        'metric_label' => '-',
        'metric_value' => '-'
    ],
    [
        'module' => 'Galeri Foto',
        'icon' => 'bi bi-images',
        'total' => $albumTotal . ' Album',
        'active' => $photoTotal . ' Foto',
        'pending' => '-',
        'metric_label' => '-',
        'metric_value' => '-'
    ],
    [
        'module' => 'File Download',
        'icon' => 'bi bi-cloud-download-fill',
        'total' => (int)$filesData['total'],
        'active' => '-',
        'pending' => '-',
        'metric_label' => 'Unduhan',
        'metric_value' => number_format((int)$filesData['metric_val'])
    ],
    [
        'module' => 'Pengguna',
        'icon' => 'bi bi-people-fill',
        'total' => (int)$usersData['total'],
        'active' => (int)$usersData['active'],
        'pending' => (int)$usersData['pending'],
        'metric_label' => '-',
        'metric_value' => '-'
    ],
    [
        'module' => 'Pesan Kontak',
        'icon' => 'bi bi-envelope-fill',
        'total' => (int)$msgData['total'],
        'active' => '-',
        'pending' => (int)$msgData['pending'] . ' Baru',
        'metric_label' => '-',
        'metric_value' => '-'
    ],
    [
        'module' => 'Kategori & Tags',
        'icon' => 'bi bi-tags-fill',
        'total' => $catCount . ' Kategori',
        'active' => $tagCount . ' Tag',
        'pending' => '-',
        'metric_label' => '-',
        'metric_value' => '-'
    ],
];

// ==============================================================================================
// 4. EXPORT PDF LOGIC
// ==============================================================================================
if ($exportPdf === '1') {
    $siteName = getSetting('site_name', 'BTIKP Kalimantan Selatan');
    
    // Initialize mPDF
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'orientation' => 'P',
        'margin_left' => 15,
        'margin_right' => 15,
        'margin_top' => 10,
        'margin_bottom' => 15,
    ]);
    
    $mpdf->SetDefaultFont('cambria');
    $mpdf->SetTitle('Laporan Overview Sistem');
    $mpdf->SetAuthor($siteName);

    // Footer
    $footer = '
        <table width="100%" style="border-top: 1px solid #000; padding-top: 5px; font-size: 9pt;">
            <tr>
                <td width="70%" style="text-align: left;">
                    ' . htmlspecialchars($siteName) . ' - Laporan Overview
                </td>
                <td width="30%" style="text-align: right;">
                    Halaman {PAGENO} dari {nbpg}
                </td>
            </tr>
        </table>';
    $mpdf->SetHTMLFooter($footer);

    // Load Template
    ob_start();
    include dirname(__FILE__) . '/templates/laporan_overview_pdf.php';
    $html = ob_get_clean();

    $mpdf->WriteHTML($html);
    $mpdf->Output('Laporan_Overview_' . date('Ymd_His') . '.pdf', 'I');
    exit;
}

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6">
                <h3><?= $pageTitle ?></h3>
                <p class="text-subtitle text-muted">Ringkasan kesehatan dan status seluruh modul sistem.</p>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item">Laporan</li>
                        <li class="breadcrumb-item active"><?= $pageTitle ?></li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="row mb-3">
            <div class="col-6 col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2"><i class="bi bi-collection-fill"></i> Total Konten</h6>
                        <h2 class="mb-0"><?= formatNumber($summaryStats['total_content']) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2"><i class="bi bi-eye-fill"></i> Total Views</h6>
                        <h2 class="mb-0"><?= formatNumber($summaryStats['total_views']) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2"><i class="bi bi-images"></i> Total Media</h6>
                        <h2 class="mb-0"><?= formatNumber($summaryStats['total_media']) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2"><i class="bi bi-person-check-fill"></i> User Aktif</h6>
                        <h2 class="mb-0"><?= formatNumber($summaryStats['active_users']) ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-column flex-md-row gap-2">
                    <div class="flex-grow-1">
                        <h6 class="mb-1">Data Overview Sistem</h6>
                        <small class="text-muted">Tanggal Generate: <?= date('d F Y, H:i') ?> WIB</small>
                    </div>

                    <a href="?export_pdf=1" class="btn btn-danger flex-shrink-0" target="_blank">
                        <i class="bi bi-file-pdf"></i> Export PDF
                    </a>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header border-bottom-0">
                <h5 class="card-title mb-0">
                    Ringkasan Modul Sistem
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Modul</th>
                                <th class="text-center">Total Data</th>
                                <th class="text-center">Status Aktif</th>
                                <th class="text-center">Status Pending/Draft</th>
                                <th class="text-end">Metrik Lain</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($overviewData as $row): ?>
                            <tr>
                                <td class="fw-bold">
                                    <i class="<?= $row['icon'] ?> text-primary me-2 fs-5" style="vertical-align: middle;"></i>
                                    <?= htmlspecialchars($row['module']) ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light-primary text-primary font-bold fs-6">
                                        <?= $row['total'] ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if ($row['active'] !== '-'): ?>
                                        <span class="badge bg-success">
                                            <?= $row['active'] ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($row['pending'] !== '-' && (int)$row['pending'] > 0): ?>
                                        <span class="badge bg-warning text-dark">
                                            <?= $row['pending'] ?>
                                        </span>
                                    <?php elseif ($row['pending'] === 0 || $row['pending'] === '0 (Unread)'): ?>
                                        <span class="text-muted">0</span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($row['metric_value'] !== '-'): ?>
                                        <small class="text-muted d-block" style="font-size: 0.75rem;"><?= $row['metric_label'] ?></small>
                                        <span class="fw-bold text-dark"><?= $row['metric_value'] ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>