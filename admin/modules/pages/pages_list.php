<?php
/**
 * Pages - List
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

$pageTitle = 'Manajemen Halaman';
$currentPage = 'pages';

$db = Database::getInstance()->getConnection();

$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Query total count
if ($search) {
    $countStmt = $db->prepare("SELECT COUNT(*) as total FROM pages WHERE title LIKE ?");
    $countStmt->execute(["%$search%"]);
} else {
    $countStmt = $db->query("SELECT COUNT(*) as total FROM pages");
}
$totalItems = $countStmt->fetch()['total'];
$totalPages = ceil($totalItems / $perPage);

// Get pages
if ($search) {
    $stmt = $db->prepare("SELECT * FROM pages WHERE title LIKE ? ORDER BY display_order ASC, updated_at DESC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, "%$search%", PDO::PARAM_STR);
    $stmt->bindValue(2, $perPage, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
} else {
    $stmt = $db->prepare("SELECT * FROM pages ORDER BY display_order ASC, updated_at DESC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $perPage, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
}
$pages = $stmt->fetchAll();

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h3><?= $pageTitle ?></h3>
            </div>
            <div class="col-md-6 text-md-end">
                <a href="pages_add.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Tambah Halaman
                </a>
            </div>
        </div>
    </div>
    
    <section class="section">
        <div class="card">
            <div class="card-header">
                <form method="GET" class="d-flex align-items-center" role="search">
                    <input type="search" name="search" class="form-control me-2" placeholder="Cari halaman..." value="<?= htmlspecialchars($search) ?>">
                    <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i> Cari</button>
                    <?php if ($search): ?>
                    <a href="pages_list.php" class="btn btn-outline-secondary ms-2"><i class="bi bi-x-circle"></i> Reset</a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="card-body p-0">
                <?php if (empty($pages)): ?>
                <div class="alert alert-info m-3">
                    Tidak ada halaman ditemukan.
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table mb-0 table-hover">
                        <thead>
                            <tr>
                                <th>Judul</th>
                                <th>Status</th>
                                <th>Urutan</th>
                                <th>Terakhir Diupdate</th>
                                <th class="text-center" style="width:150px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pages as $pageItem): ?>
                            <tr>
                                <td><?= htmlspecialchars($pageItem['title']) ?></td>
                                <td>
                                    <?php if ($pageItem['status'] === 'published'): ?>
                                        <span class="badge bg-success">Published</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Draft</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= (int)$pageItem['display_order'] ?></td>
                                <td><?= formatTanggal($pageItem['updated_at'], 'd M Y H:i') ?></td>
                                <td class="text-center">
                                    <a href="pages_edit.php?id=<?= $pageItem['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit Halaman">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <a href="pages_delete.php?id=<?= $pageItem['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Yakin ingin menghapus halaman ini?')" title="Hapus Halaman">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation" class="mt-3">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                        <li class="page-item"><a class="page-link" href="?page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>">&laquo;</a></li>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>"><?= $i ?></a></li>
                        <?php endfor; ?>
                        <?php if ($page < $totalPages): ?>
                        <li class="page-item"><a class="page-link" href="?page=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>">&raquo;</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>
