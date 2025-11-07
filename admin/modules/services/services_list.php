<?php
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

$pageTitle = 'Daftar Layanan';
$currentPage = 'services';

$db = Database::getInstance()->getConnection();

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$search = trim($_GET['search'] ?? '');

$where = 'WHERE deleted_at IS NULL';
$params = [];

if ($search) {
    $where .= " AND (title LIKE ? OR slug LIKE ? OR description LIKE ?)";
    $likeSearch = "%{$search}%";
    $params = [$likeSearch, $likeSearch, $likeSearch];
}

$countSql = "SELECT COUNT(*) FROM services $where";
$stmtCount = $db->prepare($countSql);
$stmtCount->execute($params);
$totalItems = (int)$stmtCount->fetchColumn();
$totalPages = ceil($totalItems / $perPage);

$sql = "SELECT * FROM services $where ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$services = $stmt->fetchAll();

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row align-items-center">
            <div class="col-12 col-md-6">
                <h3>Daftar Layanan</h3>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-md-end">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Layanan</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

    <section class="section">
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-3">
                    <form method="get" class="d-flex">
                        <input type="text" name="search" placeholder="Cari layanan..." class="form-control me-2" value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn btn-primary">Cari</button>
                        <?php if ($search): ?>
                            <a href="services_list.php" class="btn btn-outline-secondary ms-2"><i class="bi bi-x"></i></a>
                        <?php endif; ?>
                    </form>
                    <a href="services_add.php" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i> Tambah Layanan
                    </a>
                </div>

                <?php if (empty($services)): ?>
                    <div class="alert alert-info text-center">Tidak ada layanan ditemukan.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Judul</th>
                                    <th>Slug</th>
                                    <th>Deskripsi</th>
                                    <th>Website</th>
                                    <th>Status</th>
                                    <th>Dibuat</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($services as $index => $service): ?>
                                <tr>
                                    <td><?= $offset + $index + 1 ?></td>
                                    <td><?= htmlspecialchars($service['title']) ?></td>
                                    <td><span class="badge bg-light text-dark"><?= htmlspecialchars($service['slug']) ?></span></td>
                                    <td><?= nl2br(htmlspecialchars(mb_strimwidth($service['description'], 0, 100, '...'))) ?></td>
                                    <td>
                                        <?php if (!empty($service['service_url'])): ?>
                                            <a href="<?= htmlspecialchars($service['service_url']) ?>" target="_blank" class="btn btn-sm btn-info" title="Kunjungi Website">
                                                <i class="bi bi-box-arrow-up-right"></i> Website
                                            </a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= $service['status'] === 'published' ? '<span class="badge bg-success">Published</span>' : '<span class="badge bg-secondary">Draft</span>' ?>
                                    </td>
                                    <td><?= isset($service['created_at']) ? formatTanggal($service['created_at']) : '-' ?></td>
                                    <td class="text-center">
                                        <a href="services_view.php?id=<?= $service['id'] ?>" class="btn btn-sm btn-primary" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="services_edit.php?id=<?= $service['id'] ?>" class="btn btn-sm btn-warning" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="services_delete.php?id=<?= $service['id'] ?>" class="btn btn-sm btn-danger"
                                           onclick="return confirm('Hapus layanan ini? Data akan masuk ke Trash.');" title="Hapus">
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
                            <?php for ($i=1; $i<=$totalPages; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?><?= $search ? '&search='.urlencode($search) : '' ?>"><?= $i ?></a>
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
