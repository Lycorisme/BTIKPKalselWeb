<?php
/**
 * Contact Messages - List (Inbox)
 * Simplified: No archive feature
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

 $pageTitle = 'Pesan Kontak';
 $currentPage = 'contact';

 $db = Database::getInstance()->getConnection();

// Get filter parameters
 $status = $_GET['status'] ?? '';
 $search = $_GET['search'] ?? '';

// Pagination
 $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
 $perPage = 15;
 $offset = ($page - 1) * $perPage;

// Build WHERE clause
 $whereConditions = [];
 $params = [];
 $countParams = [];

if ($status) {
    $whereConditions[] = "status = ?";
    $params[] = $status;
    $countParams[] = $status;
}

if ($search) {
    $whereConditions[] = "(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
    $searchParam = "%{$search}%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
    $countParams = array_merge($countParams, [$searchParam, $searchParam, $searchParam, $searchParam]);
}

 $whereClause = count($whereConditions) > 0 ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get total count
 $countSql = "SELECT COUNT(*) as total FROM contact_messages {$whereClause}";
 $countStmt = $db->prepare($countSql);
 $countStmt->execute($countParams);
 $totalItems = $countStmt->fetch()['total'];
 $totalPages = ceil($totalItems / $perPage);

// Get messages
 $sql = "SELECT * FROM contact_messages {$whereClause} ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}";
 $stmt = $db->prepare($sql);
 $stmt->execute($params);
 $messages = $stmt->fetchAll();

// Get status counts for filter tabs (only unread & read)
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
 $totalCount = $totalCountStmt->fetch()['total'];

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row align-items-center">
            <div class="col-12 col-md-6 mb-3 mb-md-0">
                <h3><i class=""></i><?= $pageTitle ?></h3>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-md-end">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active">Pesan Kontak</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card shadow-sm">
            <div class="card-header border-bottom">
                <ul class="nav nav-tabs nav-tabs-sm" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link <?= $status === '' ? 'active' : '' ?>" href="?">
                            <span class="d-none d-md-inline">Semua Pesan</span>
                            <span class="d-md-none">All</span>
                            <span class="badge bg-secondary ms-1"><?= $totalCount ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $status === 'unread' ? 'active' : '' ?>" href="?status=unread">
                            <i class="bi bi-envelope d-md-none"></i>
                            <span class="d-none d-md-inline">Belum Dibaca</span>
                            <span class="badge bg-danger ms-1"><?= $statusCounts['unread'] ?? 0 ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $status === 'read' ? 'active' : '' ?>" href="?status=read">
                            <i class="bi bi-envelope-open d-md-none"></i>
                            <span class="d-none d-md-inline">Sudah Dibaca</span>
                            <span class="badge bg-primary ms-1"><?= $statusCounts['read'] ?? 0 ?></span>
                        </a>
                    </li>
                </ul>
            </div>
            
            <div class="card-body p-0">
                <div class="p-3 p-md-4 border-bottom">
                    <form method="GET">
                        <?php if ($status): ?>
                            <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
                        <?php endif; ?>
                        <div class="row g-2">
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
                                    <a href="?<?= $status ? 'status=' . $status : '' ?>" class="btn btn-outline-secondary w-100">
                                        <i class="bi bi-x"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>

                <?php if (empty($messages)): ?>
                    <div class="p-4 text-center">
                        <div class="alert alert-info mx-auto" style="max-width: 600px;">
                            <i class="bi bi-info-circle me-2"></i> 
                            <?php if ($search): ?>
                                Tidak ada pesan yang cocok dengan pencarian "<strong><?= htmlspecialchars($search) ?></strong>"
                            <?php else: ?>
                                Belum ada pesan <?= $status ? 'dengan status ini' : '' ?>.
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    
                    <div class="table-responsive d-none d-lg-block">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Pengirim</th>
                                    <th>Subject & Pesan</th>
                                    <th>Tanggal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($messages as $message): ?>
                                    <tr>
                                        <td>
                                            <?php
                                            $statusBadges = [
                                                'unread' => '<span class="badge bg-danger"><i class="bi bi-envelope-fill me-1"></i> Belum Dibaca</span>',
                                                'read' => '<span class="badge bg-primary"><i class="bi bi-envelope-open me-1"></i> Sudah Dibaca</span>'
                                            ];
                                            echo $statusBadges[$message['status']] ?? '';
                                            ?>
                                        </td>
                                        <td>
                                            <div class="fw-bold <?= $message['status'] === 'unread' ? 'text-primary' : '' ?>">
                                                <?= htmlspecialchars($message['name']) ?>
                                            </div>
                                            <small class="text-muted d-block">
                                                <i class="bi bi-envelope me-1"></i> <?= htmlspecialchars($message['email']) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <a href="messages_view.php?id=<?= $message['id'] ?>" 
                                               class="text-decoration-none d-block <?= $message['status'] === 'unread' ? 'fw-bold' : '' ?>">
                                                <?= htmlspecialchars(truncateText($message['subject'], 50)) ?>
                                            </a>
                                            <small class="text-muted d-block mt-1">
                                                <?= htmlspecialchars(truncateText($message['message'], 80)) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <small class="text-nowrap">
                                                <?= formatTanggal($message['created_at'], 'd M Y') ?><br>
                                                <span class="text-muted"><?= formatTanggal($message['created_at'], 'H:i') ?></span>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="messages_view.php?id=<?= $message['id'] ?>" 
                                                   class="btn btn-sm btn-outline-primary" 
                                                   title="Lihat">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="messages_delete.php?id=<?= $message['id'] ?>" 
                                                   class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('Yakin ingin menghapus pesan ini?')"
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

                    <div class="d-lg-none p-3">
                        <?php foreach ($messages as $message): ?>
                            <div class="card mb-3 shadow-sm <?= $message['status'] === 'unread' ? 'border-primary' : '' ?>">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1 <?= $message['status'] === 'unread' ? 'fw-bold text-primary' : '' ?>">
                                                <?= htmlspecialchars($message['name']) ?>
                                            </h6>
                                            <small class="text-muted d-block mb-1">
                                                <i class="bi bi-envelope me-1"></i> <?= htmlspecialchars($message['email']) ?>
                                            </small>
                                        </div>
                                        <div class="ms-2">
                                            <?php
                                            $statusIcons = [
                                                'unread' => '<span class="badge bg-danger"><i class="bi bi-envelope-fill"></i></span>',
                                                'read' => '<span class="badge bg-primary"><i class="bi bi-envelope-open"></i></span>'
                                            ];
                                            echo $statusIcons[$message['status']] ?? '';
                                            ?>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <h6 class="small fw-bold mb-1"><?= htmlspecialchars($message['subject']) ?></h6>
                                        <p class="text-muted small mb-0">
                                            <?= htmlspecialchars(truncateText($message['message'], 100)) ?>
                                        </p>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="bi bi-clock me-1"></i> <?= formatTanggal($message['created_at'], 'd M Y H:i') ?>
                                        </small>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="messages_view.php?id=<?= $message['id'] ?>" 
                                               class="btn btn-outline-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="messages_delete.php?id=<?= $message['id'] ?>" 
                                               class="btn btn-outline-danger"
                                               onclick="return confirm('Yakin ingin menghapus?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($totalPages > 1): ?>
                        <div class="p-3 border-top">
                            <nav aria-label="Page navigation">
                                <ul class="pagination pagination-sm justify-content-center flex-wrap mb-0">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $page - 1 ?><?= $status ? '&status=' . $status : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                                <i class="bi bi-chevron-left"></i>
                                                <span class="d-none d-md-inline ms-1">Sebelumnya</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php
                                    $startPage = max(1, $page - 2);
                                    $endPage = min($totalPages, $page + 2);
                                    
                                    for ($i = $startPage; $i <= $endPage; $i++):
                                    ?>
                                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?><?= $status ? '&status=' . $status : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $page + 1 ?><?= $status ? '&status=' . $status : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                                <span class="d-none d-md-inline me-1">Selanjutnya</span>
                                                <i class="bi bi-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>