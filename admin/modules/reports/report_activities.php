<?php
/**
 * Report: Activity Logs
 * Activity report with advanced filters and PDF export
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

// Import mPDF
require_once '../../../vendor/autoload.php';

$pageTitle = 'Laporan Aktivitas';

$db = Database::getInstance()->getConnection();

// Get filters
$userId = $_GET['user_id'] ?? '';
$actionType = $_GET['action_type'] ?? '';
$modelType = $_GET['model_type'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$exportPdf = $_GET['export_pdf'] ?? '';

// Build query
$sql = "SELECT 
            al.*,
            COALESCE(u.name, al.user_name) as display_name,
            u.email as user_email
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id
        WHERE 1=1";

$params = [];

if ($userId) {
    $sql .= " AND al.user_id = ?";
    $params[] = $userId;
}

if ($actionType) {
    $sql .= " AND al.action_type = ?";
    $params[] = $actionType;
}

if ($modelType) {
    $sql .= " AND al.model_type = ?";
    $params[] = $modelType;
}

if ($dateFrom) {
    $sql .= " AND DATE(al.created_at) >= ?";
    $params[] = $dateFrom;
}

if ($dateTo) {
    $sql .= " AND DATE(al.created_at) <= ?";
    $params[] = $dateTo;
}

$sql .= " ORDER BY al.created_at DESC LIMIT 1000";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$activities = $stmt->fetchAll();

// Statistics
$stats = [
    'total' => count($activities),
    'create' => 0,
    'update' => 0,
    'delete' => 0,
    'login' => 0,
    'logout' => 0,
    'view' => 0,
    'unique_users' => 0
];

$userIds = [];
foreach ($activities as $activity) {
    if (isset($stats[strtolower($activity['action_type'])])) {
        $stats[strtolower($activity['action_type'])]++;
    }
    if ($activity['user_id']) {
        $userIds[$activity['user_id']] = true;
    }
}
$stats['unique_users'] = count($userIds);

// Get activities by user
$userStatsStmt = $db->query("
    SELECT 
        COALESCE(u.name, al.user_name) as name,
        COUNT(*) as total
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    GROUP BY al.user_id, COALESCE(u.name, al.user_name)
    ORDER BY total DESC
    LIMIT 10
");
$userStats = $userStatsStmt->fetchAll();

// Get activities by action type
$actionStatsStmt = $db->query("
    SELECT 
        action_type,
        COUNT(*) as total
    FROM activity_logs
    GROUP BY action_type
    ORDER BY total DESC
");
$actionStats = $actionStatsStmt->fetchAll();

// Get activities by module
$moduleStatsStmt = $db->query("
    SELECT 
        model_type,
        COUNT(*) as total
    FROM activity_logs
    WHERE model_type IS NOT NULL
    GROUP BY model_type
    ORDER BY total DESC
");
$moduleStats = $moduleStatsStmt->fetchAll();

// Get users for filter
$usersStmt = $db->query("SELECT id, name FROM users ORDER BY name");
$users = $usersStmt->fetchAll();

// Get action types
$actionTypes = ['CREATE', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT', 'VIEW'];

// Get model types
$modelTypesStmt = $db->query("
    SELECT DISTINCT model_type 
    FROM activity_logs 
    WHERE model_type IS NOT NULL 
    ORDER BY model_type
");
$modelTypes = $modelTypesStmt->fetchAll(PDO::FETCH_COLUMN);

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
        include __DIR__ . '/templates/laporan_activities_pdf.php';
        $html = ob_get_clean();
        
        // Write HTML to PDF
        $mpdf->WriteHTML($html);
        
        // Output PDF
        $mpdf->Output('Laporan_Aktivitas_' . date('Ymd_His') . '.pdf', 'I');
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
                        <li class="breadcrumb-item active">Aktivitas</li>
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
                        <div class="col-md-3">
                            <label class="form-label">User</label>
                            <select name="user_id" class="form-select">
                                <option value="">Semua User</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= $user['id'] ?>" <?= $userId == $user['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($user['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Action Type</label>
                            <select name="action_type" class="form-select">
                                <option value="">Semua Action</option>
                                <?php foreach ($actionTypes as $type): ?>
                                    <option value="<?= $type ?>" <?= $actionType === $type ? 'selected' : '' ?>>
                                        <?= $type ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Module</label>
                            <select name="model_type" class="form-select">
                                <option value="">Semua Module</option>
                                <?php foreach ($modelTypes as $type): ?>
                                    <option value="<?= $type ?>" <?= $modelType === $type ? 'selected' : '' ?>>
                                        <?= ucfirst($type) ?>
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
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Tampilkan
                            </button>
                            <a href="report_activities.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset
                            </a>
                            <a href="?export_pdf=1<?= $userId ? '&user_id='.$userId : '' ?><?= $actionType ? '&action_type='.$actionType : '' ?><?= $modelType ? '&model_type='.$modelType : '' ?><?= $dateFrom ? '&date_from='.$dateFrom : '' ?><?= $dateTo ? '&date_to='.$dateTo : '' ?>" 
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
            <div class="col-md-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Total Aktivitas</h6>
                        <h3 class="mb-0"><?= formatNumber($stats['total']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">CREATE</h6>
                        <h3 class="mb-0 text-success"><?= formatNumber($stats['create']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">UPDATE</h6>
                        <h3 class="mb-0 text-info"><?= formatNumber($stats['update']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">DELETE</h6>
                        <h3 class="mb-0 text-danger"><?= formatNumber($stats['delete']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">LOGIN</h6>
                        <h3 class="mb-0 text-primary"><?= formatNumber($stats['login']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Unique Users</h6>
                        <h3 class="mb-0 text-warning"><?= formatNumber($stats['unique_users']) ?></h3>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Activities Table -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Daftar Aktivitas (Max 1000 records)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>User</th>
                                        <th>Action</th>
                                        <th>Description</th>
                                        <th>Module</th>
                                        <th>Tanggal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($activities)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">Tidak ada data</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php $no = 1; foreach ($activities as $activity): ?>
                                            <tr>
                                                <td><?= $no++ ?></td>
                                                <td><?= htmlspecialchars($activity['display_name']) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= getActionColor($activity['action_type']) ?>">
                                                        <?= $activity['action_type'] ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($activity['description']) ?></td>
                                                <td>
                                                    <?php if ($activity['model_type']): ?>
                                                        <span class="badge bg-secondary"><?= ucfirst($activity['model_type']) ?></span>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                                <td style="font-size: 0.85rem;">
                                                    <?= formatTanggal($activity['created_at'], 'd/m/Y H:i') ?>
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
            
            <!-- Side Stats -->
            <div class="col-lg-4">
                <!-- Top Users -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Top 10 Users</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <tbody>
                                    <?php foreach ($userStats as $user): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($user['name']) ?></td>
                                            <td class="text-end"><strong><?= formatNumber($user['total']) ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Action Stats -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">By Action Type</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <tbody>
                                    <?php foreach ($actionStats as $action): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-<?= getActionColor($action['action_type']) ?>">
                                                    <?= $action['action_type'] ?>
                                                </span>
                                            </td>
                                            <td class="text-end"><strong><?= formatNumber($action['total']) ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Module Stats -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">By Module</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <tbody>
                                    <?php foreach ($moduleStats as $module): ?>
                                        <tr>
                                            <td><?= ucfirst($module['model_type']) ?></td>
                                            <td class="text-end"><strong><?= formatNumber($module['total']) ?></strong></td>
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
