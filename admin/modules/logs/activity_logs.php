<?php
/**
 * Activity Logs Page
 * Complete with filters, search, export, and cleanup
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../core/Pagination.php';

$pageTitle = 'Activity Logs';

// Only admin can access
if (!hasRole(['super_admin', 'admin'])) {
    setAlert('danger', 'Anda tidak memiliki akses ke halaman ini');
    redirect(ADMIN_URL);
}

$db = Database::getInstance()->getConnection();

// Get items per page
$itemsPerPage = (int)getSetting('items_per_page', 25);

// Get filters
$userId = $_GET['user_id'] ?? '';
$actionType = $_GET['action_type'] ?? '';
$modelType = $_GET['model_type'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$search = $_GET['search'] ?? '';
$page = $_GET['page'] ?? 1;

// Export to CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Build query for export (without pagination)
    $sql = "SELECT 
                al.*,
                u.name as user_name,
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
    
    if ($search) {
        $sql .= " AND (al.description LIKE ? OR al.user_name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $sql .= " ORDER BY al.created_at DESC LIMIT 5000"; // Max 5000 records
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
    
    // Generate CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=activity_logs_' . date('Y-m-d_His') . '.csv');
    
    $output = fopen('php://output', 'w');
    
    // UTF-8 BOM for Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Header
    fputcsv($output, ['ID', 'User', 'Email', 'Action', 'Description', 'Model Type', 'Model ID', 'IP Address', 'Date Time']);
    
    // Data
    foreach ($logs as $log) {
        fputcsv($output, [
            $log['id'],
            $log['user_name'],
            $log['user_email'],
            $log['action_type'],
            $log['description'],
            $log['model_type'] ?? '-',
            $log['model_id'] ?? '-',
            $log['ip_address'] ?? '-',
            $log['created_at']
        ]);
    }
    
    fclose($output);
    exit;
}

// Handle cleanup
if (isset($_POST['cleanup_logs'])) {
    $cleanupDays = (int)$_POST['cleanup_days'];
    
    if ($cleanupDays > 0 && $cleanupDays <= 365) {
        try {
            $stmt = $db->prepare("
                DELETE FROM activity_logs 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$cleanupDays]);
            
            $deleted = $stmt->rowCount();
            
            logActivity('DELETE', "Cleanup activity logs ($deleted records older than $cleanupDays days)", 'activity_logs');
            
            setAlert('success', "Berhasil menghapus $deleted log lama");
            redirect(ADMIN_URL . 'modules/logs/activity_logs.php');
        } catch (PDOException $e) {
            error_log($e->getMessage());
            setAlert('danger', 'Gagal cleanup logs: ' . $e->getMessage());
        }
    }
}

// Build query with filters
$sql = "SELECT 
            al.*,
            COALESCE(u.name, al.user_name) as user_display_name,
            u.email as user_email,
            u.photo as user_photo
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

if ($search) {
    $sql .= " AND (al.description LIKE ? OR al.user_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Count total
$countSql = "SELECT COUNT(*) FROM (" . $sql . ") as filtered";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$total = $countStmt->fetchColumn();

// Add pagination
$offset = ($page - 1) * $itemsPerPage;
$sql .= " ORDER BY al.created_at DESC LIMIT ? OFFSET ?";
$params[] = $itemsPerPage;
$params[] = $offset;

// Get logs
$stmt = $db->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Initialize pagination
$pagination = new Pagination($total, $itemsPerPage, $page);

// Get all users for filter
$usersStmt = $db->query("SELECT id, name FROM users ORDER BY name");
$users = $usersStmt->fetchAll();

// Get distinct action types
$actionTypes = ['CREATE', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT', 'VIEW'];

// Get distinct model types
$modelStmt = $db->query("
    SELECT DISTINCT model_type 
    FROM activity_logs 
    WHERE model_type IS NOT NULL 
    ORDER BY model_type
");
$modelTypes = $modelStmt->fetchAll(PDO::FETCH_COLUMN);

// Get statistics
$statsStmt = $db->query("
    SELECT 
        COUNT(*) as total,
        COUNT(DISTINCT user_id) as unique_users,
        MAX(created_at) as last_activity
    FROM activity_logs
");
$stats = $statsStmt->fetch();

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
                        <li class="breadcrumb-item active">Activity Logs</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <?php if ($alert = getAlert()): ?>
            <div class="alert alert-<?= $alert['type'] ?> alert-dismissible fade show">
                <?= $alert['message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Statistics Cards -->
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Total Logs</h6>
                                <h3 class="mb-0"><?= formatNumber($stats['total']) ?></h3>
                            </div>
                            <div class="text-primary">
                                <i class="bi bi-file-text" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Unique Users</h6>
                                <h3 class="mb-0"><?= formatNumber($stats['unique_users']) ?></h3>
                            </div>
                            <div class="text-success">
                                <i class="bi bi-people" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Last Activity</h6>
                                <h6 class="mb-0"><?= formatTanggalRelatif($stats['last_activity']) ?></h6>
                                <small class="text-muted"><?= formatTanggal($stats['last_activity'], 'd M Y H:i') ?></small>
                            </div>
                            <div class="text-info">
                                <i class="bi bi-clock-history" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Card -->
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Activity Logs</h5>
                    <div class="d-flex gap-2">
                        <!-- Export Button -->
                        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#exportModal">
                            <i class="bi bi-download"></i> Export CSV
                        </button>
                        
                        <!-- Cleanup Button -->
                        <?php if (hasRole(['super_admin'])): ?>
                            <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cleanupModal">
                                <i class="bi bi-trash"></i> Cleanup
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="card-body">
                <!-- Filters -->
                <form method="GET" class="mb-4">
                    <div class="row g-3">
                        <!-- User Filter -->
                        <div class="col-md-2">
                            <select name="user_id" class="form-select form-select-sm">
                                <option value="">Semua User</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= $user['id'] ?>" <?= $userId == $user['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($user['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Action Type Filter -->
                        <div class="col-md-2">
                            <select name="action_type" class="form-select form-select-sm">
                                <option value="">Semua Action</option>
                                <?php foreach ($actionTypes as $type): ?>
                                    <option value="<?= $type ?>" <?= $actionType === $type ? 'selected' : '' ?>>
                                        <?= $type ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Model Type Filter -->
                        <div class="col-md-2">
                            <select name="model_type" class="form-select form-select-sm">
                                <option value="">Semua Module</option>
                                <?php foreach ($modelTypes as $type): ?>
                                    <option value="<?= $type ?>" <?= $modelType === $type ? 'selected' : '' ?>>
                                        <?= ucfirst($type) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Date From -->
                        <div class="col-md-2">
                            <input type="date" name="date_from" class="form-control form-control-sm" 
                                   value="<?= htmlspecialchars($dateFrom) ?>" placeholder="Dari Tanggal">
                        </div>
                        
                        <!-- Date To -->
                        <div class="col-md-2">
                            <input type="date" name="date_to" class="form-control form-control-sm" 
                                   value="<?= htmlspecialchars($dateTo) ?>" placeholder="Sampai Tanggal">
                        </div>
                        
                        <!-- Search -->
                        <div class="col-md-2">
                            <input type="text" name="search" class="form-control form-control-sm" 
                                   placeholder="Cari deskripsi..." 
                                   value="<?= htmlspecialchars($search) ?>">
                        </div>
                    </div>
                    
                    <div class="row mt-2">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-search"></i> Filter
                            </button>
                            <?php if ($userId || $actionType || $modelType || $dateFrom || $dateTo || $search): ?>
                                <a href="activity_logs.php" class="btn btn-secondary btn-sm">
                                    <i class="bi bi-x-circle"></i> Reset
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>

                <!-- Info -->
                <div class="alert alert-info mb-3">
                    <i class="bi bi-info-circle"></i>
                    Menampilkan <strong><?= count($logs) ?></strong> dari <strong><?= formatNumber($total) ?></strong> log
                </div>

                <!-- Logs Timeline -->
                <div class="table-responsive">
                    <?php if (empty($logs)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                            <p class="mt-2">Belum ada activity log</p>
                        </div>
                    <?php else: ?>
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="60">ID</th>
                                    <th width="200">User</th>
                                    <th width="100">Action</th>
                                    <th>Description</th>
                                    <th width="100">Module</th>
                                    <th width="120">IP Address</th>
                                    <th width="150">Timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?= $log['id'] ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if ($log['user_photo']): ?>
                                                    <img src="<?= uploadUrl($log['user_photo']) ?>" 
                                                         alt="<?= htmlspecialchars($log['user_name']) ?>" 
                                                         class="rounded-circle me-2" 
                                                         style="width: 30px; height: 30px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="rounded-circle bg-secondary text-white d-inline-flex align-items-center justify-content-center me-2" 
                                                         style="width: 30px; height: 30px; font-size: 0.75rem;">
                                                        <?= strtoupper(substr($log['user_name'], 0, 1)) ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <div class="fw-bold"><?= htmlspecialchars($log['user_display_name']) ?></div>
                                                    <small class="text-muted"><?= htmlspecialchars($log['user_email']) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= getActionColor($log['action_type']) ?>">
                                                <?= $log['action_type'] ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($log['description']) ?></td>
                                        <td>
                                            <?php if ($log['model_type']): ?>
                                                <span class="badge bg-secondary">
                                                    <?= ucfirst($log['model_type']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small class="font-monospace"><?= $log['ip_address'] ?? '-' ?></small>
                                        </td>
                                        <td>
                                            <div><?= formatTanggalRelatif($log['created_at']) ?></div>
                                            <small class="text-muted"><?= formatTanggal($log['created_at'], 'd M Y H:i') ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total > 0): ?>
                    <div class="mt-4 d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">
                                Halaman <?= $page ?> dari <?= ceil($total / $itemsPerPage) ?>
                            </small>
                        </div>
                        <?= $pagination->render() ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Export to CSV</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Export activity logs dengan filter yang sedang aktif.</p>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i>
                    <strong>Perhatian:</strong> Maksimal 5000 records akan di-export.
                </div>
                
                <?php if ($userId || $actionType || $modelType || $dateFrom || $dateTo || $search): ?>
                    <div class="alert alert-info">
                        <strong>Filter aktif:</strong>
                        <ul class="mb-0">
                            <?php if ($userId): ?>
                                <li>User: <?= htmlspecialchars($users[array_search($userId, array_column($users, 'id'))]['name'] ?? 'Unknown') ?></li>
                            <?php endif; ?>
                            <?php if ($actionType): ?>
                                <li>Action: <?= $actionType ?></li>
                            <?php endif; ?>
                            <?php if ($modelType): ?>
                                <li>Module: <?= ucfirst($modelType) ?></li>
                            <?php endif; ?>
                            <?php if ($dateFrom): ?>
                                <li>Dari: <?= formatTanggal($dateFrom, 'd M Y') ?></li>
                            <?php endif; ?>
                            <?php if ($dateTo): ?>
                                <li>Sampai: <?= formatTanggal($dateTo, 'd M Y') ?></li>
                            <?php endif; ?>
                            <?php if ($search): ?>
                                <li>Search: "<?= htmlspecialchars($search) ?>"</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <a href="?export=csv<?= $userId ? '&user_id='.$userId : '' ?><?= $actionType ? '&action_type='.$actionType : '' ?><?= $modelType ? '&model_type='.$modelType : '' ?><?= $dateFrom ? '&date_from='.$dateFrom : '' ?><?= $dateTo ? '&date_to='.$dateTo : '' ?><?= $search ? '&search='.$search : '' ?>" 
                   class="btn btn-success">
                    <i class="bi bi-download"></i> Download CSV
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Cleanup Modal -->
<?php if (hasRole(['super_admin'])): ?>
<div class="modal fade" id="cleanupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Cleanup Old Logs</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Perhatian:</strong> Aksi ini tidak bisa di-undo!
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Hapus log lebih dari:</label>
                        <select name="cleanup_days" class="form-select" required>
                            <option value="30">30 hari</option>
                            <option value="60">60 hari</option>
                            <option value="90" selected>90 hari</option>
                            <option value="180">180 hari (6 bulan)</option>
                            <option value="365">365 hari (1 tahun)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="cleanup_logs" class="btn btn-danger">
                        <i class="bi bi-trash"></i> Hapus Logs Lama
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>
