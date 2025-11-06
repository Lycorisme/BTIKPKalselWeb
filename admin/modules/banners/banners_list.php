<?php
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

$pageTitle = 'Manajemen Banner';
$currentPage = 'banners';

$db = Database::getInstance()->getConnection();

$where = ['deleted_at IS NULL'];
$params = [];

if (!empty($_GET['q'])) {
    $q = trim($_GET['q']);
    $where[] = "(title LIKE ? OR caption LIKE ?)";
    $params[] = "%{$q}%";
    $params[] = "%{$q}%";
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT * FROM banners {$whereSql} ORDER BY ordering ASC, id DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$banners = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
?>

<div class="page-heading">
  <div class="page-title">
    <div class="row align-items-center">
      <div class="col-12 col-md-7">
        <h3><i class=""></i><?= $pageTitle ?></h3>
      </div>
      <div class="col-12 col-md-5 text-end">
        <a href="banners_add.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah Banner</a>
      </div>
    </div>
  </div>

  <section class="section mt-3">
    <form method="get" class="mb-3 row g-2">
      <div class="col-md-7">
        <input type="text" class="form-control" name="q" placeholder="Cari judul atau caption..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
      </div>
      <div class="col-md-3">
        <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-search"></i> Cari</button>
      </div>
    </form>

    <div class="card">
      <div class="card-body">
        <?php if (empty($banners)): ?>
          <div class="alert alert-warning mb-0">Belum ada banner.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
              <thead>
                <tr>
                  <th style="width: 50px; text-align:center;">#</th>
                  <th style="width: 100px;">Gambar</th>
                  <th style="width: 250px;">Judul & Caption</th>
                  <th style="width: 200px;">Link</th>
                  <th style="width: 100px;">Status</th>
                  <th style="width: 90px;">Urutan</th>
                  <th style="width: 150px;">Dibuat</th>
                  <th style="width: 130px;" class="text-end">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($banners as $idx => $banner): ?>
                <tr>
                  <td><?= $idx + 1 ?></td>
                  <td>
                    <a href="#" class="thumbnail-preview-link" data-bs-toggle="modal" data-bs-target="#thumbPreviewModal" data-img="<?= bannerImageUrl($banner['image_path']) ?>">
                      <img src="<?= bannerImageUrl($banner['image_path']) ?>" alt="Banner" style="max-width: 90px; max-height:54px; border-radius:5px;">
                    </a>
                  </td>
                  <td>
                    <div class="fw-semibold"><?= htmlspecialchars($banner['title']) ?></div>
                    <div class="small text-muted"><?= htmlspecialchars($banner['caption']) ?></div>
                  </td>
                  <td>
                    <?php if ($banner['link_url']): ?>
                      <a href="<?= htmlspecialchars($banner['link_url']) ?>" target="_blank" class="text-info"><?= htmlspecialchars($banner['link_url']) ?></a>
                    <?php else: ?>
                      <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($banner['is_active']): ?>
                      <span class="badge bg-success">Aktif</span>
                    <?php else: ?>
                      <span class="badge bg-secondary">Nonaktif</span>
                    <?php endif; ?>
                  </td>
                  <td><?= (int)$banner['ordering'] ?></td>
                  <td><small><?= formatTanggal($banner['created_at']) ?></small></td>
                  <td class="text-end">
                    <a href="banners_edit.php?id=<?= $banner['id'] ?>" class="btn btn-outline-warning btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>
                    <a href="banners_delete.php?id=<?= $banner['id'] ?>" class="btn btn-outline-danger btn-sm" title="Hapus"
                       onclick="return confirm('Hapus banner ini?')"><i class="bi bi-trash"></i></a>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
</div>

<!-- Modal Preview -->
<div class="modal fade" id="thumbPreviewModal" tabindex="-1" aria-labelledby="thumbPreviewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <!-- PERUBAHAN: Menghapus class 'bg-dark text-light' -->
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="thumbPreviewModalLabel">Preview Banner</h5>
        <!-- PERUBAHAN: Menghapus class 'btn-close-white' -->
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center" id="modal-thumb-area">
        <span>Loading...</span>
      </div>
    </div>
  </div>
</div>

<script>
document.querySelectorAll(".thumbnail-preview-link").forEach(function(link) {
  link.addEventListener("click", function(e) {
    e.preventDefault();
    var url = this.dataset.img;
    document.getElementById("modal-thumb-area").innerHTML = `<img src="${url}" style="max-width:99%; max-height:75vh; border-radius:8px;" />`;
  });
});
</script>
<?php include '../../includes/footer.php'; ?>