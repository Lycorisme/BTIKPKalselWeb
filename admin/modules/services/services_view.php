<?php
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    setAlert('danger', 'Layanan tidak ditemukan.');
    redirect(ADMIN_URL . 'modules/services/services_list.php');
}

$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("SELECT * FROM services WHERE id = ? AND deleted_at IS NULL");
$stmt->execute([$id]);
$service = $stmt->fetch();

if (!$service) {
    setAlert('danger', 'Layanan tidak ditemukan atau sudah dihapus.');
    redirect(ADMIN_URL . 'modules/services/services_list.php');
}

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row align-items-center">
            <div class="col-12 col-md-6">
                <h3>Detail Layanan</h3>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-md-end">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="services_list.php">Layanan</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Detail</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

    <section class="section">
        <div class="card shadow-sm">
            <div class="card-body">
                <h4><?= htmlspecialchars($service['title']) ?></h4>
                <?php if ($service['image_path']): ?>
                    <img src="<?= BASE_URL . $service['image_path'] ?>" alt="Gambar Layanan" class="img-fluid mb-3" style="max-height:300px;">
                <?php endif; ?>
                <p><strong>URL Website:</strong> 
                   <?php if ($service['service_url']): ?>
                       <a href="<?= htmlspecialchars($service['service_url']) ?>" target="_blank"><?= htmlspecialchars($service['service_url']) ?></a>
                   <?php else: ?>
                       (Tidak ada URL)
                   <?php endif; ?>
                </p>
                <p><strong>Deskripsi:</strong><br><?= nl2br(htmlspecialchars($service['description'])) ?></p>
                <p><strong>Status:</strong> <?= $service['status'] === 'published' ? 'Published' : 'Draft' ?></p>
                <a href="services_edit.php?id=<?= $service['id'] ?>" class="btn btn-warning me-2"><i class="bi bi-pencil"></i> Edit Layanan</a>
                <a href="services_list.php" class="btn btn-secondary"><i class="bi bi-list"></i> Kembali ke Daftar</a>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>
