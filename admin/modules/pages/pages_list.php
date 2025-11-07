<?php
/**
 * Pages - Daftar Semua Halaman
 */
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

$pageTitle = 'Daftar Halaman';
$currentPage = 'pages';

$db = Database::getInstance()->getConnection();

// Pagination settings
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Filter/search
$search = trim($_GET['search'] ?? '');

$where = 'WHERE deleted_at IS NULL';
$params = [];

if ($search) {
    $where .= " AND (title LIKE ? OR slug LIKE ? OR content LIKE ?)";
    $searchParam = "%{$search}%";
    $params = [$searchParam, $searchParam, $searchParam];
}

// Count for pagination
$countSql = "SELECT COUNT(*) FROM pages $where";
$stmtCount = $db->prepare($countSql);
$stmtCount->execute($params);
$totalItems = (int)$stmtCount->fetchColumn();
$totalPages = ceil($totalItems / $perPage);

// Fetch data
$sql = "SELECT * FROM pages $where ORDER BY display_order ASC, created_at DESC LIMIT $perPage OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$pages = $stmt->fetchAll();

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <h3><?= $pageTitle ?></h3>
    </div>
    <section class="section">
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-3">
                    <form class="d-flex" method="get">
                        <input type="text" name="search" class="form-control me-2"
                               placeholder="Cari halaman..." value="<?= htmlspecialchars($search) ?>">
                        <button class="btn btn-outline-primary" type="submit">Cari</button>
                        <?php if ($search): ?>
                            <a href="pages_list.php" class="btn btn-outline-secondary ms-2"><i class="bi bi-x"></i></a>
                        <?php endif; ?>
                    </form>
                    <a href="pages_add.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah Halaman</a>
                </div>

                <?php if (empty($pages)): ?>
                    <div class="alert alert-info text-center mb-0">
                        Tidak ada halaman ditemukan.
                    </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Judul</th>
                            <th>Slug</th>
                            <th>Status</th>
                            <th>Urutan</th>
                            <th>Dibuat</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($pages as $no => $row): ?>
                            <tr>
                                <td><?= $offset + $no + 1 ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($row['title']) ?></strong>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark"><?= htmlspecialchars($row['slug']) ?></span>
                                </td>
                                <td>
                                    <?php if ($row['status'] === 'published'): ?>
                                        <span class="badge bg-success">Published</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Draft</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= (int)$row['display_order'] ?></td>
                                <td>
                                    <small><?= isset($row['created_at']) ? formatTanggal($row['created_at']) : '-' ?></small>
                                </td>
                                <td class="text-center">
                                    <a href="pages_edit.php?id=<?= $row['id'] ?>" class="btn btn-primary btn-sm" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="pages_delete.php?id=<?= $row['id'] ?>"
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Hapus halaman ini? Data pindah ke trash, bisa direstore!')"
                                       title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <?php if ($totalPages > 1): ?>
                <nav>
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>
<?php include '../../includes/footer.php'; ?>
