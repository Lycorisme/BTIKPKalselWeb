<?php
/**
 * Report: Users
 * User report with role statistics and PDF export
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

// Import mPDF
require_once '../../../vendor/autoload.php';

$pageTitle = 'Laporan Users';

$db = Database::getInstance()->getConnection();

// Get filters
$role = $_GET['role'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$exportPdf = $_GET['export_pdf'] ?? '';

// Build query
$sql = "SELECT * FROM users WHERE deleted_at IS NULL";
$params = [];

if ($role) {
    $sql .= " AND role = ?";
    $params[] = $role;
}

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
$users = $stmt->fetchAll();

// Statistics
$stats = [
    'total' => count($users),
    'super_admin' => 0,
    'admin' => 0,
    'editor' => 0,
    'author' => 0
];

foreach ($users as $user) {
    if (isset($stats[$user['role']])) {
        $stats[$user['role']]++;
    }
}

// Get users by role
$roleStatsStmt = $db->query("
    SELECT 
        role,
        COUNT(*) as total
    FROM users
    WHERE deleted_at IS NULL
    GROUP BY role
    ORDER BY total DESC
");
$roleStats = $roleStatsStmt->fetchAll();

// Get recent registrations (last 30 days)
$recentStmt = $db->query("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as total
    FROM users
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    AND deleted_at IS NULL
    GROUP BY DATE(created_at)
    ORDER BY date DESC
    LIMIT 10
");
$recentRegistrations = $recentStmt->fetchAll();

// Get user activity stats
$activityStmt = $db->query("
    SELECT 
        u.id,
        u.name,
        u.email,
        u.role,
        COUNT(al.id) as activity_count
    FROM users u
    LEFT JOIN activity_logs al ON u.id = al.user_id
    WHERE u.deleted_at IS NULL
    GROUP BY u.id, u.name, u.email, u.role
    ORDER BY activity_count DESC
    LIMIT 10
");
$topActiveUsers = $activityStmt->fetchAll();

// Roles list
$roles = ['super_admin', 'admin', 'editor', 'author'];

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
        include __DIR__ . '/templates/laporan_users_pdf.php';
        $html = ob_get_clean();
        
        // Write HTML to PDF
        $mpdf->WriteHTML($html);
        
        // Output PDF
        $mpdf->Output('Laporan_Users_' . date('Ymd_His') . '.pdf', 'I');
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
                        <li class="breadcrumb-item active">Users</li>
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
                            <label class="form-label">Role</label>
                            <select name="role" class="form-select">
                                <option value="">Semua Role</option>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= $r ?>" <?= $role === $r ? 'selected' : '' ?>>
                                        <?= getRoleName($r) ?>
                                    </option>
                                <?php endforeach; ?>
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
                            <a href="report_users.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset
                            </a>
                            <a href="?export_pdf=1<?= $role ? '&role='.$role : '' ?><?= $dateFrom ? '&date_from='.$dateFrom : '' ?><?= $dateTo ? '&date_to='.$dateTo : '' ?>" 
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
                        <h6 class="text-muted mb-2">Total Users</h6>
                        <h3 class="mb-0"><?= formatNumber($stats['total']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Super Admin</h6>
                        <h3 class="mb-0 text-danger"><?= formatNumber($stats['super_admin']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Admin</h6>
                        <h3 class="mb-0 text-warning"><?= formatNumber($stats['admin']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Editor</h6>
                        <h3 class="mb-0 text-info"><?= formatNumber($stats['editor']) ?></h3>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Users Table -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Daftar Users</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Terdaftar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($users)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">Tidak ada data</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php $no = 1; foreach ($users as $user): ?>
                                            <tr>
                                                <td><?= $no++ ?></td>
                                                <td><?= htmlspecialchars($user['name']) ?></td>
                                                <td><?= htmlspecialchars($user['email']) ?></td>
                                                <td><?= getRoleBadge($user['role']) ?></td>
                                                <td><?= formatTanggal($user['created_at'], 'd M Y') ?></td>
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
                <!-- Users by Role -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Users per Role</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <tbody>
                                    <?php foreach ($roleStats as $roleStat): ?>
                                        <tr>
                                            <td><?= getRoleName($roleStat['role']) ?></td>
                                            <td class="text-end"><strong><?= formatNumber($roleStat['total']) ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Registrations -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Pendaftaran Terakhir</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <tbody>
                                    <?php foreach ($recentRegistrations as $reg): ?>
                                        <tr>
                                            <td><?= formatTanggal($reg['date'], 'd M Y') ?></td>
                                            <td class="text-end"><strong><?= formatNumber($reg['total']) ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Top Active Users -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">User Paling Aktif</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <tbody>
                                    <?php foreach ($topActiveUsers as $activeUser): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($activeUser['name']) ?></td>
                                            <td class="text-end">
                                                <strong><?= formatNumber($activeUser['activity_count']) ?></strong>
                                                <small class="text-muted">aktivitas</small>
                                            </td>
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
