<?php
/**
 * Contact Messages - List (Inbox) - Full Mazer Design
 * Simplified: No archive feature, status tabs, responsive layout
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

$pageTitle = 'Pesan Kontak';
$currentPage = 'contact';

$db = Database::getInstance()->getConnection();

// Get filter parameters
$status = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Build WHERE clause
$whereConditions = [];
$params = [];

if ($status && in_array($status, ['unread', 'read'])) {
    $whereConditions[] = "status = ?";
    $params[] = $status;
}

if ($search) {
    $whereConditions[] = "(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
    $searchParam = "%{$search}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$whereClause = count($whereConditions) > 0 ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get total count
$countSql = "SELECT COUNT(*) as total FROM contact_messages {$whereClause}";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalItems = (int)$countStmt->fetch()['total'];
$totalPages = max(1, ceil($totalItems / $perPage));

// Ensure page is valid
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

// Get messages
$sql = "SELECT * FROM contact_messages {$whereClause} ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;

$stmt = $db->prepare($sql);
$stmt->execute($params);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get status counts for filter tabs
$countsStmt = $db->query("
    SELECT status, COUNT(*) as count
    FROM contact_messages
    WHERE status IN ('unread', 'read')
    GROUP BY status
");
$statusCounts = [];
foreach ($countsStmt->fetchAll() as $row) {
    $statusCounts[$row['status']] = $row['count'];
}

// Total all messages
$totalCountStmt = $db->query("SELECT COUNT(*) as total FROM contact_messages WHERE status IN ('unread', 'read')");
$totalCount = (int)$totalCountStmt->fetch()['total'];

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6">
                <h3><?= $pageTitle ?></h3>
                <p class="text-subtitle text-muted">Kelola semua pesan masuk dari kontak</p>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active">Pesan Kontak</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card shadow">
            <!-- Status Tabs -->
            <div class="card-header border-bottom">
                <ul class="nav nav-tabs nav-tabs-sm" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link <?= $status === '' ? 'active' : '' ?>" href="messages_list.php">
                            <i class="bi bi-inbox me-1"></i>
                            <span class="d-none d-md-inline">Semua Pesan</span>
                            <span class="d-md-none">All</span>
                            <span class="badge bg-secondary ms-1"><?= $totalCount ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $status === 'unread' ? 'active' : '' ?>" href="?status=unread">
                            <i class="bi bi-envelope me-1"></i>
                            <span class="d-none d-md-inline">Belum Dibaca</span>
                            <span class="d-md-none">Unread</span>
                            <span class="badge bg-danger ms-1"><?= $statusCounts['unread'] ?? 0 ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $status === 'read' ? 'active' : '' ?>" href="?status=read">
                            <i class="bi bi-envelope-open me-1"></i>
                            <span class="d-none d-md-inline">Sudah Dibaca</span>
                            <span class="d-md-none">Read</span>
                            <span class="badge bg-primary ms-1"><?= $statusCounts['read'] ?? 0 ?></span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="card-body p-0">
                <!-- Search Form -->
                <div class="p-3 p-md-4 border-bottom">
                    <form method="GET" class="row g-2 align-items-center">
                        <?php if ($status): ?>
                            <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
                        <?php endif; ?>
                        <div class="col-12 col-md-9">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" 
                                       class="form-control" 
                                       name="search" 
                                       placeholder="Cari nama, email, atau subject..." 
                                       value="<?= htmlspecialchars($search) ?>">
                            </div>
                        </div>
                        <div class="col-6 col-md-2">
                            <button class="btn btn-primary w-100" type="submit">
                                <i class="bi bi-search"></i>
                                <span class="d-none d-md-inline ms-1">Cari</span>
                            </button>
                        </div>
                        <div class="col-6 col-md-1">
                            <?php if ($search): ?>
                                <a href="?<?= $status ? 'status=' . urlencode($status) : '' ?>" class="btn btn-outline-secondary w-100">
                                    <i class="bi bi-x"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <?php if (empty($messages)): ?>
                    <div class="p-5 text-center">
                        <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                        <h5 class="text-muted mb-2">
                            <?php if ($search): ?>
                                Tidak ada pesan yang cocok
                            <?php elseif ($status): ?>
                                Tidak ada pesan dengan status ini
                            <?php else: ?>
                                Belum ada pesan masuk
                            <?php endif; ?>
                        </h5>
                        <p class="text-muted small">
                            <?php if ($search): ?>
                                Coba ubah pencarian Anda atau hapus filter
                            <?php else: ?>
                                Pesan kontak dari website akan muncul di sini
                            <?php endif; ?>
                        </p>
                    </div>
                <?php else: ?>
                    
                    <!-- Desktop Table View -->
                    <div class="table-responsive d-none d-lg-block">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:120px;">Status</th>
                                    <th>Pengirim</th>
                                    <th>Subject & Pesan</th>
                                    <th style="width:140px;">Tanggal</th>
                                    <th style="width:120px;" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($messages as $message): ?>
                                    <tr class="<?= $message['status'] === 'unread' ? 'table-light' : '' ?>">
                                        <td>
                                            <?php if ($message['status'] === 'unread'): ?>
                                                <span class="badge bg-danger">
                                                    <i class="bi bi-envelope-fill me-1"></i> Belum Dibaca
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-primary">
                                                    <i class="bi bi-envelope-open me-1"></i> Sudah Dibaca
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="fw-bold <?= $message['status'] === 'unread' ? 'text-dark' : 'text-muted' ?>">
                                                <?= htmlspecialchars($message['name']) ?>
                                            </div>
                                            <small class="text-muted d-block">
                                                <i class="bi bi-envelope me-1"></i> <?= htmlspecialchars($message['email']) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <a href="messages_view.php?id=<?= $message['id'] ?>" 
                                               class="text-decoration-none <?= $message['status'] === 'unread' ? 'fw-bold text-dark' : 'text-muted' ?>">
                                                <?= htmlspecialchars(truncateText($message['subject'], 50)) ?>
                                            </a>
                                            <small class="text-muted d-block mt-1">
                                                <?= htmlspecialchars(truncateText($message['message'], 80)) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <small class="text-nowrap">
                                                <div><?= formatTanggal($message['created_at'], 'd M Y') ?></div>
                                                <div class="text-muted"><?= formatTanggal($message['created_at'], 'H:i') ?></div>
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="messages_view.php?id=<?= $message['id'] ?>" 
                                                   class="btn btn-outline-primary" 
                                                   title="Lihat Lengkap">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="messages_delete.php?id=<?= $message['id'] ?>" 
                                                   class="btn btn-outline-danger"
                                                   data-confirm-delete
                                                   data-title="Hapus Pesan"
                                                   data-message="Yakin ingin menghapus pesan ini? Aksi ini tidak bisa dibatalkan."
                                                   data-loading-text="Menghapus..."
                                                   title="Hapus">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Card View -->
                    <div class="d-lg-none p-3">
                        <?php foreach ($messages as $message): ?>
                            <div class="card mb-3 shadow-sm <?= $message['status'] === 'unread' ? 'border-primary border-2' : '' ?>">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1 <?= $message['status'] === 'unread' ? 'fw-bold' : '' ?>">
                                                <?= htmlspecialchars($message['name']) ?>
                                            </h6>
                                            <small class="text-muted d-block mb-2">
                                                <i class="bi bi-envelope me-1"></i> <?= htmlspecialchars($message['email']) ?>
                                            </small>
                                        </div>
                                        <?php if ($message['status'] === 'unread'): ?>
                                            <span class="badge bg-danger flex-shrink-0 ms-2">
                                                <i class="bi bi-envelope-fill"></i>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-primary flex-shrink-0 ms-2">
                                                <i class="bi bi-envelope-open"></i>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <h6 class="small fw-bold mb-1">
                                            <?= htmlspecialchars($message['subject']) ?>
                                        </h6>
                                        <p class="text-muted small mb-0">
                                            <?= htmlspecialchars(truncateText($message['message'], 100)) ?>
                                        </p>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="bi bi-clock me-1"></i> 
                                            <?= formatTanggal($message['created_at'], 'd M Y H:i') ?>
                                        </small>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="messages_view.php?id=<?= $message['id'] ?>" 
                                               class="btn btn-outline-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="messages_delete.php?id=<?= $message['id'] ?>" 
                                               class="btn btn-outline-danger"
                                               data-confirm-delete
                                               data-title="Hapus Pesan"
                                               data-message="Yakin ingin menghapus pesan ini?"
                                               data-loading-text="Menghapus...">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="p-3 border-top">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <small class="text-muted">
                                    Halaman <?= $page ?> dari <?= $totalPages ?> · Menampilkan <?= count($messages) ?> dari <?= $totalItems ?> pesan
                                </small>
                                <nav aria-label="Page navigation">
                                    <ul class="pagination pagination-sm mb-0">
                                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?page=<?= $page - 1 ?><?= $status ? '&status=' . urlencode($status) : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                                <i class="bi bi-chevron-left"></i>
                                            </a>
                                        </li>

                                        <?php
                                        $startPage = max(1, $page - 2);
                                        $endPage = min($totalPages, $page + 2);
                                        
                                        for ($i = $startPage; $i <= $endPage; $i++):
                                        ?>
                                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                                <a class="page-link" href="?page=<?= $i ?><?= $status ? '&status=' . urlencode($status) : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                                    <?= $i ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>

                                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?page=<?= $page + 1 ?><?= $status ? '&status=' . urlencode($status) : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                                <i class="bi bi-chevron-right"></i>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>
