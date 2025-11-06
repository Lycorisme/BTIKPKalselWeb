<?php
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

$pageTitle = 'Tambah Banner';
$currentPage = 'banners';

$error = '';
$db = Database::getInstance()->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $caption = trim($_POST['caption'] ?? '');
    $link_url = trim($_POST['link_url'] ?? '');
    $is_active = (int)($_POST['is_active'] ?? 1);
    $ordering = (int)($_POST['ordering'] ?? 0);

    if (!$title) $error = 'Judul wajib diisi.';
    elseif (!isset($_FILES['image']) || $_FILES['image']['error'] != 0) $error = 'Upload gambar banner.';
    else {
        $file = $_FILES['image'];
        $basename = time() . '-' . preg_replace('/[^a-zA-Z0-9-_\.]/', '_', basename($file['name']));
        $destPath = 'uploads/banners/' . $basename;

        if (!move_uploaded_file($file['tmp_name'], '../../../public/' . $destPath)) {
            $error = 'Upload gambar gagal.';
        } else {
            $stmt = $db->prepare("INSERT INTO banners (title, caption, image_path, link_url, is_active, ordering) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$title, $caption, $destPath, $link_url, $is_active, $ordering]);
            setAlert('success', 'Banner berhasil ditambahkan.');
            header("Location: banners_list.php");
            exit;
        }
    }
}

include '../../includes/header.php';
?>
<div class="page-heading">
    <div class="page-title"><h3><?= $pageTitle ?></h3></div>
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
                        <input type="text" class="form-control" name="title" maxlength="255" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Caption (opsional)</label>
                        <input type="text" class="form-control" name="caption" maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Link URL (opsional)</label>
                        <input type="url" class="form-control" name="link_url" maxlength="255" placeholder="https://">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gambar Banner</label>
                        <input type="file" accept="image/*" class="form-control" name="image" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="is_active" class="form-select" required>
                            <option value="1" selected>Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Urutan tampil</label>
                        <input type="number" class="form-control" name="ordering" value="0">
                    </div>
                    <button class="btn btn-primary" type="submit"><i class="bi bi-save"></i> Simpan</button>
                    <a href="banners_list.php" class="btn btn-secondary">Kembali</a>
                </form>
            </div>
        </div>
    </section>
</div>
<?php include '../../includes/footer.php'; ?>