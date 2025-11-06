<?php
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

$pageTitle = 'Edit Banner';
$currentPage = 'banners';

$db = Database::getInstance()->getConnection();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM banners WHERE id = ? AND deleted_at IS NULL LIMIT 1");
$stmt->execute([$id]);
$banner = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$banner) {
    setAlert('danger', 'Banner tidak ditemukan!');
    header("Location: banners_list.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $caption = trim($_POST['caption'] ?? '');
    $link_url = trim($_POST['link_url'] ?? '');
    $is_active = (int)($_POST['is_active'] ?? 1);
    $ordering = (int)($_POST['ordering'] ?? 0);

    $image_path = $banner['image_path'];

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $file = $_FILES['image'];
        $basename = time() . '-' . preg_replace('/[^a-zA-Z0-9-_\.]/', '_', basename($file['name']));
        $destPath = 'uploads/banners/' . $basename;

        if (move_uploaded_file($file['tmp_name'], '../../../public/' . $destPath)) {
            if (file_exists('../../../public/' . $banner['image_path'])) {
                unlink('../../../public/' . $banner['image_path']);
            }
            $image_path = $destPath;
        } else {
            $error = 'Upload gambar gagal!';
        }
    }

    if (!$error) {
        $stmt = $db->prepare("UPDATE banners SET title=?, caption=?, image_path=?, link_url=?, is_active=?, ordering=?, updated_at=NOW() WHERE id=?");
        $stmt->execute([$title, $caption, $image_path, $link_url, $is_active, $ordering, $id]);
        setAlert('success', 'Perubahan banner sudah disimpan.');
        header("Location: banners_list.php");
        exit;
    }
}

include '../../includes/header.php';
?>
<div class="page-heading"><div class="page-title"><h3><?= $pageTitle ?></h3></div>
<section class="section mt-3">
    <!-- 
      PERUBAHAN: Menghapus class 'bg-dark text-light border-secondary' 
      Mazer akan otomatis mengatur warna card berdasarkan tema.
    -->
    <div class="card">
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif ?>
            <form method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Judul</label>
                    <input type="text" class="form-control" name="title" value="<?= htmlspecialchars($banner['title']) ?>" maxlength="255" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Caption (opsional)</label>
                    <input type="text" class="form-control" name="caption" value="<?= htmlspecialchars($banner['caption']) ?>" maxlength="255">
                </div>
                <div class="mb-3">
                    <label class="form-label">Link URL (opsional)</label>
                    <input type="url" class="form-control" name="link_url" value="<?= htmlspecialchars($banner['link_url']) ?>" maxlength="255">
                </div>
                <div class="mb-3">
                    <label class="form-label">Gambar Banner</label><br>
                    <img src="<?= bannerImageUrl($banner['image_path']) ?>" alt="Banner" class="mb-2" style="max-width: 160px; border-radius: 6px;"><br>
                    <input type="file" class="form-control" name="image" accept="image/*">
                    <small class="text-muted">Abaikan jika tidak ingin ganti gambar.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="is_active" required>
                      <option value="1" <?= $banner['is_active']?'selected':''; ?>>Aktif</option>
                      <option value="0" <?= !$banner['is_active']?'selected':''; ?>>Nonaktif</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Urutan tampil</label>
                    <input type="number" class="form-control" name="ordering" value="<?= (int) $banner['ordering'] ?>">
                </div>
                <button type="submit" class="btn btn-warning"><i class="bi bi-save"></i> Simpan</button>
                <a href="banners_list.php" class="btn btn-secondary">Kembali</a>
            </form>
        </div>
    </div>
</section>
</div>
<?php include '../../includes/footer.php'; ?>